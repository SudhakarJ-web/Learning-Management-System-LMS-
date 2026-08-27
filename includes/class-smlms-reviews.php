<?php
/**
 * Sabin Mathew LMS - Course Reviews, Votes & Comments Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Reviews {

    public static function init() {
        add_action('init', [__CLASS__, 'ensure_tables_exist'], 5);
        add_action('init', [__CLASS__, 'handle_native_post_submission'], 10);
        add_action('wp_ajax_smlms_submit_review', [__CLASS__, 'ajax_submit_review']);
        add_action('wp_ajax_smlms_vote_review', [__CLASS__, 'ajax_vote_review']);
        add_action('wp_ajax_nopriv_smlms_vote_review', [__CLASS__, 'ajax_vote_review_nopriv']);
        add_action('wp_ajax_smlms_submit_review_comment', [__CLASS__, 'ajax_submit_review_comment']);
    }

    /**
     * Direct Database Table Creation & Schema Migration
     */
    public static function ensure_tables_exist() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $reviews_table  = $wpdb->prefix . 'smlms_reviews';
        $votes_table    = $wpdb->prefix . 'smlms_review_votes';
        $comments_table = $wpdb->prefix . 'smlms_review_comments';

        // 1. Create Reviews Table
        $sql_reviews = "CREATE TABLE {$reviews_table} (
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

        // 2. Create Votes Table
        $sql_votes = "CREATE TABLE {$votes_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            review_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            vote_type varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY review_user (review_id, user_id)
        ) {$charset_collate};";

        // 3. Create Review Comments Table
        $sql_comments = "CREATE TABLE {$comments_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            review_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            comment_text text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY review_id (review_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_reviews);
        dbDelta($sql_votes);
        dbDelta($sql_comments);

        // Direct Execution Guarantee
        $wpdb->query($sql_reviews);
        $wpdb->query($sql_votes);
        $wpdb->query($sql_comments);

        // Auto-Migration Column Checks
        $has_status = $wpdb->get_results("SHOW COLUMNS FROM `{$reviews_table}` LIKE 'status'");
        if (empty($has_status)) {
            $wpdb->query("ALTER TABLE `{$reviews_table}` ADD COLUMN `status` varchar(20) NOT NULL DEFAULT 'approved' AFTER `helpful_count`");
        }

        $has_headline = $wpdb->get_results("SHOW COLUMNS FROM `{$reviews_table}` LIKE 'headline'");
        if (empty($has_headline)) {
            $wpdb->query("ALTER TABLE `{$reviews_table}` ADD COLUMN `headline` varchar(255) NOT NULL AFTER `rating`");
        }

        $wpdb->query("UPDATE {$reviews_table} SET status = 'approved' WHERE status IS NULL OR status = '' OR status = 'publish'");
    }

    /**
     * Eligibility Check: User MUST be Logged-in AND Have Completed the Course
     */
    public static function is_user_eligible_to_review($user_id, $course_id) {
        $user_id   = intval($user_id);
        $course_id = intval($course_id);

        if (!$user_id || !$course_id) {
            return false;
        }

        return SMLMS_DB::is_course_completed($user_id, $course_id);
    }

    /**
     * Fetch Existing User Review
     */
    public static function get_user_review_for_course($user_id, $course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';

        self::ensure_tables_exist();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE course_id = %d AND user_id = %d LIMIT 1",
            intval($course_id),
            intval($user_id)
        ));
    }

    /**
     * Get Comments for a Specific Review
     */
    public static function get_review_comments($review_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_review_comments';

        self::ensure_tables_exist();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE review_id = %d ORDER BY created_at ASC",
            intval($review_id)
        ));
    }

    /**
     * Native HTTP POST Fallback Submission Handler (Clean URL Redirect)
     */
    public static function handle_native_post_submission() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['smlms_action']) && $_POST['smlms_action'] === 'submit_review') {
            
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'smlms_review_nonce')) {
                return;
            }

            $user_id   = get_current_user_id();
            $course_id = intval($_POST['course_id'] ?? 0);
            $rating    = intval($_POST['rating'] ?? 5);
            $headline  = sanitize_text_field($_POST['headline'] ?? '');
            $review_txt= sanitize_textarea_field($_POST['review_text'] ?? '');

            if (!$user_id || !$course_id || empty($headline) || empty($review_txt)) {
                return;
            }

            if (!self::is_user_eligible_to_review($user_id, $course_id)) {
                return;
            }

            self::ensure_tables_exist();

            global $wpdb;
            $table_name = $wpdb->prefix . 'smlms_reviews';

            $existing = self::get_user_review_for_course($user_id, $course_id);

            if ($existing) {
                $saved = $wpdb->update(
                    $table_name,
                    [
                        'rating'      => max(1, min(5, $rating)),
                        'headline'    => $headline,
                        'review_text' => $review_txt,
                        'status'      => 'approved',
                        'created_at'  => current_time('mysql')
                    ],
                    ['id' => $existing->id],
                    ['%d', '%s', '%s', '%s', '%s'],
                    ['%d']
                );
            } else {
                $saved = $wpdb->insert(
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
            }

            if ($saved !== false) {
                wp_redirect(get_permalink($course_id));
                exit;
            }
        }
    }

    /**
     * Get aggregate rating summary for a course
     */
    public static function get_rating_summary($course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';

        self::ensure_tables_exist();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT rating FROM {$table_name} WHERE course_id = %d AND status IN ('approved', 'publish')",
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

        self::ensure_tables_exist();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE course_id = %d AND status IN ('approved', 'publish') ORDER BY created_at DESC",
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

        self::ensure_tables_exist();

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
     * AJAX Submit / Update Review
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
            wp_send_json_error(['message' => 'You must complete all lessons and topics in this course before you can post a review.']);
        }

        if (empty($headline) || empty($review_txt)) {
            wp_send_json_error(['message' => 'Please fill in all required fields.']);
        }

        self::ensure_tables_exist();

        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_reviews';

        $existing = self::get_user_review_for_course($user_id, $course_id);

        if ($existing) {
            $saved = $wpdb->update(
                $table_name,
                [
                    'rating'      => max(1, min(5, $rating)),
                    'headline'    => $headline,
                    'review_text' => $review_txt,
                    'status'      => 'approved',
                    'created_at'  => current_time('mysql')
                ],
                ['id' => $existing->id],
                ['%d', '%s', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            $saved = $wpdb->insert(
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
        }

        if ($saved !== false) {
            wp_send_json_success(['message' => 'Thank you! Your review has been saved successfully.']);
        } else {
            $db_err = !empty($wpdb->last_error) ? $wpdb->last_error : 'Database query error.';
            wp_send_json_error(['message' => 'Unable to save review: ' . $db_err]);
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

        self::ensure_tables_exist();

        global $wpdb;
        $votes_table   = $wpdb->prefix . 'smlms_review_votes';
        $reviews_table = $wpdb->prefix . 'smlms_reviews';

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$votes_table} WHERE review_id = %d AND user_id = %d",
            $review_id,
            $user_id
        ));

        $new_user_vote = '';

        if ($existing) {
            if ($existing->vote_type === $vote_type) {
                $wpdb->delete($votes_table, ['id' => $existing->id], ['%d']);
                $new_user_vote = '';
            } else {
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

        $helpful_count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$votes_table} WHERE review_id = %d AND vote_type = 'helpful'",
            $review_id
        )));

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

    /**
     * AJAX Submit Review Comment
     */
    public static function ajax_submit_review_comment() {
        check_ajax_referer('smlms_review_nonce', '_wpnonce');

        $user_id     = get_current_user_id();
        $review_id   = intval($_POST['review_id'] ?? 0);
        $comment_txt = sanitize_textarea_field($_POST['comment_text'] ?? '');

        if (!$user_id) {
            wp_send_json_error(['message' => 'Please log in to post a comment.']);
        }

        if (!$review_id || empty($comment_txt)) {
            wp_send_json_error(['message' => 'Please write a comment before submitting.']);
        }

        self::ensure_tables_exist();

        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_review_comments';

        $inserted = $wpdb->insert(
            $table_name,
            [
                'review_id'    => $review_id,
                'user_id'      => $user_id,
                'comment_text' => $comment_txt,
                'created_at'   => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s']
        );

        if ($inserted) {
            $user          = get_userdata($user_id);
            $author_name   = $user ? $user->display_name : 'Anonymous';
            $author_avatar = get_avatar_url($user_id, ['size' => 32]);
            $created_date  = date('F j, Y', current_time('timestamp'));

            wp_send_json_success([
                'author_name'   => $author_name,
                'author_avatar' => $author_avatar,
                'comment_text'  => nl2br(esc_html($comment_txt)),
                'created_date'  => $created_date
            ]);
        } else {
            wp_send_json_error(['message' => 'Unable to save comment.']);
        }
    }
}

SMLMS_Reviews::init();