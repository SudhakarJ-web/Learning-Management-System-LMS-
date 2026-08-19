<?php
/**
 * Sabin Mathew LMS - Course Reviews & Helpful Votes Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Reviews {

    public static function init() {
        add_action('init', [__CLASS__, 'create_tables']);
        add_action('wp_ajax_smlms_submit_review', [__CLASS__, 'ajax_submit_review']);
        add_action('wp_ajax_smlms_vote_review', [__CLASS__, 'ajax_vote_review']);
        add_action('wp_ajax_nopriv_smlms_vote_review', [__CLASS__, 'ajax_vote_review_nopriv']);
    }

    /**
     * Create reviews and votes database tables if not existing
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $reviews_table = $wpdb->prefix . 'smlms_reviews';
        $votes_table   = $wpdb->prefix . 'smlms_review_votes';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_reviews = "CREATE TABLE IF NOT EXISTS {$reviews_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            course_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            rating tinyint(1) NOT NULL DEFAULT 5,
            headline varchar(255) NOT NULL,
            review_text text NOT NULL,
            helpful_count int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'approved',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY course_id (course_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        $sql_votes = "CREATE TABLE IF NOT EXISTS {$votes_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            review_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            vote_type varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY review_user (review_id, user_id)
        ) {$charset_collate};";

        dbDelta($sql_reviews);
        dbDelta($sql_votes);
    }

    /**
     * Get user eligibility to submit a review
     */
    public static function is_user_eligible_to_review($user_id, $course_id) {
        $user_id   = intval($user_id);
        $course_id = intval($course_id);

        if (!$user_id || !$course_id) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        return SMLMS_DB::is_user_enrolled($user_id, $course_id);
    }

    /**
     * Get aggregate rating summary for a course
     */
    public static function get_rating_summary($course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT rating FROM {$table_name} WHERE course_id = %d AND status = 'approved'",
            intval($course_id)
        ));

        $total_count = count($rows);
        if ($total_count === 0) {
            return [
                'avg_rating'  => 0,
                'total_count' => 0,
                'breakdown'   => [5=>0, 4=>0, 3=>0, 2=>0, 1=>0]
            ];
        }

        $sum = 0;
        $counts = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];

        foreach ($rows as $r) {
            $val = intval($r->rating);
            $sum += $val;
            if (isset($counts[$val])) {
                $counts[$val]++;
            }
        }

        $avg_rating = round($sum / $total_count, 1);
        $breakdown = [];

        foreach ($counts as $star => $cnt) {
            $breakdown[$star] = round(($cnt / $total_count) * 100);
        }

        return [
            'avg_rating'  => $avg_rating,
            'total_count' => $total_count,
            'breakdown'   => $breakdown
        ];
    }

    /**
     * Get published reviews list for a course
     */
    public static function get_course_reviews($course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE course_id = %d AND status = 'approved' ORDER BY created_at DESC",
            intval($course_id)
        ));
    }

    /**
     * Fetch user's votes for all reviews in a course
     */
    public static function get_user_course_review_votes($user_id, $course_id) {
        global $wpdb;
        $votes_table   = $wpdb->prefix . 'smlms_review_votes';
        $reviews_table = $wpdb->prefix . 'smlms_reviews';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT v.review_id, v.vote_type 
             FROM {$votes_table} v 
             INNER JOIN {$reviews_table} r ON v.review_id = r.id 
             WHERE v.user_id = %d AND r.course_id = %d",
            intval($user_id),
            intval($course_id)
        ));

        $votes_map = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $votes_map[$row->review_id] = $row->vote_type;
            }
        }

        return $votes_map;
    }

    /**
     * AJAX Submit New Review
     */
    public static function ajax_submit_review() {
        check_ajax_referer('smlms_review_nonce', '_wpnonce');

        $user_id   = get_current_user_id();
        $course_id = intval($_POST['course_id'] ?? 0);
        $rating    = intval($_POST['rating'] ?? 5);
        $headline  = sanitize_text_field($_POST['headline'] ?? '');
        $review_txt= sanitize_textarea_field($_POST['review_text'] ?? '');

        if (!$user_id) {
            wp_send_json_error(['message' => 'You must be logged in to post a review.']);
        }

        if (!self::is_user_eligible_to_review($user_id, $course_id)) {
            wp_send_json_error(['message' => 'You are not eligible to review this course.']);
        }

        if (empty($headline) || empty($review_txt)) {
            wp_send_json_error(['message' => 'Please fill in all required fields.']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';

        $inserted = $wpdb->insert(
            $table_name,
            [
                'course_id'     => $course_id,
                'user_id'       => $user_id,
                'rating'        => max(1, min(5, $rating)),
                'headline'      => $headline,
                'review_text'   => $review_txt,
                'helpful_count' => 0,
                'status'        => 'approved',
                'created_at'    => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s']
        );

        if ($inserted) {
            wp_send_json_success(['message' => 'Thank you! Your review has been submitted.']);
        } else {
            wp_send_json_error(['message' => 'Failed to submit review. Please try again.']);
        }
    }

    /**
     * AJAX Vote Review (Helpful / Not Helpful)
     */
    public static function ajax_vote_review() {
        check_ajax_referer('smlms_review_nonce', '_wpnonce');

        $user_id   = get_current_user_id();
        $review_id = intval($_POST['review_id'] ?? 0);
        $vote_type = sanitize_text_field($_POST['vote_type'] ?? '');

        if (!$user_id) {
            wp_send_json_error(['message' => 'Please log in to vote on reviews.']);
        }

        if (!$review_id || !in_array($vote_type, ['helpful', 'not_helpful'])) {
            wp_send_json_error(['message' => 'Invalid vote data.']);
        }

        global $wpdb;
        $votes_table   = $wpdb->prefix . 'smlms_review_votes';
        $reviews_table = $wpdb->prefix . 'smlms_reviews';

        // Check if existing vote exists for this user and review
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$votes_table} WHERE review_id = %d AND user_id = %d",
            $review_id,
            $user_id
        ));

        $new_user_vote = '';

        if ($existing) {
            if ($existing->vote_type === $vote_type) {
                // Clicking the same option again removes the vote (toggles off)
                $wpdb->delete($votes_table, ['id' => $existing->id], ['%d']);
                $new_user_vote = '';
            } else {
                // Switching vote from Helpful to Not Helpful or vice-versa
                $wpdb->update(
                    $votes_table,
                    ['vote_type' => $vote_type, 'created_at' => current_time('mysql')],
                    ['id' => $existing->id],
                    ['%s', '%s'],
                    ['%d']
                );
                $new_user_vote = $vote_type;
            }
        } else {
            // New vote insertion
            $wpdb->insert(
                $votes_table,
                [
                    'review_id'  => $review_id,
                    'user_id'    => $user_id,
                    'vote_type'  => $vote_type,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s']
            );
            $new_user_vote = $vote_type;
        }

        // Recalculate Helpful count for the review
        $helpful_count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$votes_table} WHERE review_id = %d AND vote_type = 'helpful'",
            $review_id
        )));

        // Update review record
        $wpdb->update(
            $reviews_table,
            ['helpful_count' => $helpful_count],
            ['id' => $review_id],
            ['%d'],
            ['%d']
        );

        wp_send_json_success([
            'review_id'     => $review_id,
            'helpful_count' => $helpful_count,
            'user_vote'     => $new_user_vote,
            'message'       => 'Feedback saved.'
        ]);
    }

    public static function ajax_vote_review_nopriv() {
        wp_send_json_error(['message' => 'Please log in to vote on reviews.']);
    }
}

SMLMS_Reviews::init();