<?php
/**
 * Sabin Mathew LMS - Course Reviews Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Reviews {

    public static function init() {
        // Create Reviews Database Table on Activation / Init
        add_action('init', [__CLASS__, 'create_reviews_table']);

        // AJAX Handlers
        add_action('wp_ajax_smlms_submit_review', [__CLASS__, 'ajax_submit_review']);
        add_action('wp_ajax_smlms_vote_helpful', [__CLASS__, 'ajax_vote_helpful']);
    }

    public static function create_reviews_table() {
        global $wpdb;
        $table_name      = $wpdb->prefix . 'smlms_reviews';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            course_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            rating int(1) NOT NULL DEFAULT 5,
            headline varchar(255) NOT NULL DEFAULT '',
            review_text text NOT NULL,
            helpful_count int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY course_id (course_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Check if a user has completed all course steps and is eligible to review
     */
    public static function is_user_eligible_to_review($user_id, $course_id) {
        $user_id   = intval($user_id);
        $course_id = intval($course_id);

        if (!$user_id || !$course_id) {
            return false;
        }

        // Administrators can always test and write reviews
        if (current_user_can('manage_options')) {
            return true;
        }

        // Must be enrolled in the course
        if (!SMLMS_DB::is_user_enrolled($user_id, $course_id)) {
            return false;
        }

        // Calculate total valid course steps
        $hierarchy        = SMLMS_DB::get_course_hierarchy($course_id, $user_id);
        $valid_step_ids   = [];
        $total_steps      = 0;

        if (!empty($hierarchy)) {
            foreach ($hierarchy as $l_item) {
                $l_id    = $l_item['lesson_id'];
                $l_video = get_post_meta($l_id, '_smlms_video_id', true) ?: get_post_meta($l_id, '_smlms_media_embed', true);

                if (!empty(trim((string)$l_video))) {
                    $total_steps++;
                    $valid_step_ids[] = $l_id;
                }

                if (!empty($l_item['topics'])) {
                    foreach ($l_item['topics'] as $t_item) {
                        $total_steps++;
                        $valid_step_ids[] = $t_item['id'];
                    }
                }
            }
        }

        if ($total_steps === 0) {
            return false;
        }

        // Verify completed steps count
        $completed_ids   = SMLMS_DB::get_user_completed_steps($user_id, $course_id);
        $completed_count = 0;

        foreach ($valid_step_ids as $v_id) {
            if (in_array($v_id, $completed_ids)) {
                $completed_count++;
            }
        }

        return ($completed_count >= $total_steps);
    }

    /**
     * Get Course Rating Summary (Average rating, total count, star percentage breakdown)
     */
    public static function get_rating_summary($course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';
        $course_id  = intval($course_id);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT rating, COUNT(*) as count FROM {$table_name} WHERE course_id = %d GROUP BY rating",
            $course_id
        ));

        $total_reviews = 0;
        $sum_rating    = 0;
        $star_counts   = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        foreach ($results as $row) {
            $r = intval($row->rating);
            $c = intval($row->count);
            if (isset($star_counts[$r])) {
                $star_counts[$r] = $c;
            }
            $total_reviews += $c;
            $sum_rating    += ($r * $c);
        }

        $avg_rating = ($total_reviews > 0) ? round($sum_rating / $total_reviews, 1) : 0;

        $star_percentages = [];
        foreach ($star_counts as $star => $count) {
            $star_percentages[$star] = ($total_reviews > 0) ? round(($count / $total_reviews) * 100) : 0;
        }

        return [
            'avg_rating'  => $avg_rating,
            'total_count' => $total_reviews,
            'breakdown'   => $star_percentages,
            'star_counts' => $star_counts
        ];
    }

    /**
     * Fetch Reviews for a Course
     */
    public static function get_course_reviews($course_id, $limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE course_id = %d ORDER BY created_at DESC LIMIT %d",
            intval($course_id),
            intval($limit)
        ));
    }

    /**
     * AJAX Review Submission Handler
     */
    public static function ajax_submit_review() {
        check_ajax_referer('smlms_review_nonce', '_wpnonce');

        $user_id   = get_current_user_id();
        $course_id = intval($_POST['course_id'] ?? 0);
        $rating    = intval($_POST['rating'] ?? 5);
        $headline  = sanitize_text_field($_POST['headline'] ?? '');
        $review    = sanitize_textarea_field($_POST['review_text'] ?? '');

        if (!$user_id || !$course_id) {
            wp_send_json_error(['message' => 'Invalid parameters. Please log in.']);
        }

        if (!self::is_user_eligible_to_review($user_id, $course_id)) {
            wp_send_json_error(['message' => 'You have not completed lessons required to submit a review for this course.']);
        }

        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => 'Please select a valid rating between 1 and 5 stars.']);
        }

        if (empty($review)) {
            wp_send_json_error(['message' => 'Please enter your review comments.']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';

        // Check if user already reviewed
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE course_id = %d AND user_id = %d",
            $course_id,
            $user_id
        ));

        if ($existing_id) {
            $wpdb->update(
                $table_name,
                [
                    'rating'      => $rating,
                    'headline'    => $headline,
                    'review_text' => $review,
                    'created_at'  => current_time('mysql'),
                ],
                ['id' => $existing_id],
                ['%d', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'course_id'   => $course_id,
                    'user_id'     => $user_id,
                    'rating'      => $rating,
                    'headline'    => $headline,
                    'review_text' => $review,
                    'created_at'  => current_time('mysql'),
                ],
                ['%d', '%d', '%d', '%s', '%s', '%s']
            );
        }

        wp_send_json_success(['message' => 'Thank you! Your review has been submitted successfully.']);
    }

    /**
     * AJAX Vote Helpful Handler
     */
    public static function ajax_vote_helpful() {
        check_ajax_referer('smlms_review_nonce', '_wpnonce');

        $review_id = intval($_POST['review_id'] ?? 0);
        if (!$review_id) {
            wp_send_json_error(['message' => 'Invalid review ID']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET helpful_count = helpful_count + 1 WHERE id = %d",
            $review_id
        ));

        $new_count = $wpdb->get_var($wpdb->prepare("SELECT helpful_count FROM {$table_name} WHERE id = %d", $review_id));

        wp_send_json_success(['new_count' => intval($new_count)]);
    }
}

SMLMS_Reviews::init();