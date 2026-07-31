<?php
if (!defined('ABSPATH')) exit;

class SMLMS_DB {

    /**
     * Fast single-query SQL JOIN for User Dashboard rendering (< 10ms execution time).
     */
    public static function smlms_get_user_dashboard_fast($user_id) {
        global $wpdb;

        $sql = "
            SELECT 
                e.course_id,
                p.post_title AS course_title,
                e.enrolled_at,
                COUNT(DISTINCT prog.topic_id) AS completed_topics
            FROM {$wpdb->prefix}smlms_enrollments e
            INNER JOIN {$wpdb->posts} p ON e.course_id = p.ID
            LEFT JOIN {$wpdb->prefix}smlms_progress prog 
                   ON prog.user_id = e.user_id AND prog.is_completed = 1
            WHERE e.user_id = %d AND e.status = 'active' AND p.post_status = 'publish'
            GROUP BY e.course_id, p.post_title, e.enrolled_at
        ";

        return $wpdb->get_results($wpdb->prepare($sql, $user_id));
    }

    /**
     * Upserts telemetry progress for a specific topic.
     */
    public static function save_topic_progress($user_id, $topic_id, $watched_seconds, $is_completed) {
        global $wpdb;

        $table = $wpdb->prefix . 'smlms_progress';
        $completed_at = $is_completed ? current_time('mysql') : null;

        $sql = "
            INSERT INTO {$table} (user_id, topic_id, watched_seconds, is_completed, completed_at)
            VALUES (%d, %d, %d, %d, %s)
            ON DUPLICATE KEY UPDATE 
                watched_seconds = GREATEST(watched_seconds, VALUES(watched_seconds)),
                is_completed = IF(is_completed = 1, 1, VALUES(is_completed)),
                completed_at = IF(completed_at IS NOT NULL, completed_at, VALUES(completed_at))
        ";

        return $wpdb->query($wpdb->prepare($sql, $user_id, $topic_id, $watched_seconds, $is_completed, $completed_at));
    }

    /**
     * Checks if a user is actively enrolled in a course.
     */
    public static function is_user_enrolled($user_id, $course_id) {
        global $wpdb;

        $sql = "SELECT id FROM {$wpdb->prefix}smlms_enrollments WHERE user_id = %d AND course_id = %d AND status = 'active' LIMIT 1";
        return (bool) $wpdb->get_var($wpdb->prepare($sql, $user_id, $course_id));
    }

    /**
     * Retrieves lesson & topic hierarchy with completion status for a given course.
     */
    public static function get_course_hierarchy($course_id, $user_id) {
        global $wpdb;

        $lessons = get_posts([
            'post_type'      => 'smlms_lesson',
            'posts_per_page' => -1,
            'meta_key'       => '_smlms_parent_course_id',
            'meta_value'     => $course_id,
            'orderby'        => 'menu_order',
            'order'          => 'ASC'
        ]);

        $hierarchy = [];

        foreach ($lessons as $lesson) {
            $topics = get_posts([
                'post_type'      => 'smlms_topic',
                'posts_per_page' => -1,
                'meta_key'       => '_smlms_parent_lesson_id',
                'meta_value'     => $lesson->ID,
                'orderby'        => 'menu_order',
                'order'          => 'ASC'
            ]);

            $topic_data = [];
            foreach ($topics as $topic) {
                $progress_sql = "SELECT is_completed, watched_seconds FROM {$wpdb->prefix}smlms_progress WHERE user_id = %d AND topic_id = %d";
                $prog = $wpdb->get_row($wpdb->prepare($progress_sql, $user_id, $topic->ID));

                $topic_data[] = [
                    'id'           => $topic->ID,
                    'title'        => $topic->post_title,
                    'permalink'    => get_permalink($topic->ID),
                    'is_completed' => $prog ? (bool)$prog->is_completed : false,
                    'watched_sec'  => $prog ? intval($prog->watched_seconds) : 0
                ];
            }

            $hierarchy[] = [
                'lesson_id'    => $lesson->ID,
                'lesson_title' => $lesson->post_title,
                'topics'       => $topic_data
            ];
        }

        return $hierarchy;
    }
}