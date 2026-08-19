<?php
/**
 * Sabin Mathew LMS Database Helper Methods (Full Unified Class)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_DB {

    /**
     * Get full course hierarchy (Lessons and Child Topics)
     */
    public static function get_course_hierarchy($course_id, $user_id = 0) {
        $course_id = intval($course_id);
        if (!$course_id) return [];

        $hierarchy = [];

        // 1. Try JSON Course Builder Tree first
        $raw_json = get_post_meta($course_id, '_smlms_course_tree_json', true);
        $tree_data = !empty($raw_json) ? json_decode($raw_json, true) : null;

        if (is_array($tree_data) && !empty($tree_data)) {
            foreach ($tree_data as $l_node) {
                $lesson_id = intval($l_node['id'] ?? 0);
                if (!$lesson_id || get_post_status($lesson_id) !== 'publish') continue;

                $lesson_duration     = trim((string) get_post_meta($lesson_id, '_smlms_duration', true));
                $lesson_content_type = trim((string) get_post_meta($lesson_id, '_smlms_content_type', true));

                $topics = [];
                if (!empty($l_node['topics']) && is_array($l_node['topics'])) {
                    foreach ($l_node['topics'] as $t_node) {
                        $topic_id = intval($t_node['id'] ?? 0);
                        if (!$topic_id || get_post_status($topic_id) !== 'publish') continue;

                        $topic_duration     = trim((string) get_post_meta($topic_id, '_smlms_duration', true));
                        $topic_content_type = trim((string) get_post_meta($topic_id, '_smlms_content_type', true));

                        $topics[] = [
                            'id'           => $topic_id,
                            'title'        => get_the_title($topic_id),
                            'permalink'    => get_permalink($topic_id),
                            'duration'     => $topic_duration,
                            'content_type' => $topic_content_type,
                        ];
                    }
                }

                $hierarchy[] = [
                    'lesson_id'    => $lesson_id,
                    'lesson_title' => get_the_title($lesson_id),
                    'permalink'    => get_permalink($lesson_id),
                    'duration'     => $lesson_duration,
                    'content_type' => $lesson_content_type,
                    'topics'       => $topics,
                ];
            }
            return $hierarchy;
        }

        // 2. Fallback to WP_Query via parent post meta relationships
        $lessons = get_posts([
            'post_type'      => 'smlms_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'meta_key'       => '_smlms_parent_course_id',
            'meta_value'     => $course_id,
        ]);

        foreach ($lessons as $lesson) {
            $lesson_duration     = trim((string) get_post_meta($lesson->ID, '_smlms_duration', true));
            $lesson_content_type = trim((string) get_post_meta($lesson->ID, '_smlms_content_type', true));

            $child_topics = self::get_lesson_topics($lesson->ID);
            $topics_data  = [];

            foreach ($child_topics as $topic) {
                $topic_duration     = trim((string) get_post_meta($topic->ID, '_smlms_duration', true));
                $topic_content_type = trim((string) get_post_meta($topic->ID, '_smlms_content_type', true));

                $topics_data[] = [
                    'id'           => $topic->ID,
                    'title'        => $topic->post_title,
                    'permalink'    => get_permalink($topic->ID),
                    'duration'     => $topic_duration,
                    'content_type' => $topic_content_type,
                ];
            }

            $hierarchy[] = [
                'lesson_id'    => $lesson->ID,
                'lesson_title' => $lesson->post_title,
                'permalink'    => get_permalink($lesson->ID),
                'duration'     => $lesson_duration,
                'content_type' => $lesson_content_type,
                'topics'       => $topics_data,
            ];
        }

        return $hierarchy;
    }

    /**
     * Get Parent Course ID for a Lesson or Topic
     */
    public static function get_parent_course_id($post_id) {
        $post_id = intval($post_id);
        if (!$post_id) return 0;

        $course_id = get_post_meta($post_id, '_smlms_parent_course_id', true);
        if (!empty($course_id)) return intval($course_id);

        $parent_lesson_id = get_post_meta($post_id, '_smlms_parent_lesson_id', true);
        if (!empty($parent_lesson_id)) {
            return intval(get_post_meta($parent_lesson_id, '_smlms_parent_course_id', true));
        }

        return 0;
    }

    /**
     * Get Child Topics for a specific Lesson
     */
    public static function get_lesson_topics($lesson_id) {
        $lesson_id = intval($lesson_id);
        if (!$lesson_id) return [];

        return get_posts([
            'post_type'      => 'smlms_topic',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'meta_key'       => '_smlms_parent_lesson_id',
            'meta_value'     => $lesson_id,
        ]);
    }

    /**
     * Enroll a student in a course
     */
    public static function enroll_student($user_id, $course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_enrollments';

        $user_id   = intval($user_id);
        $course_id = intval($course_id);

        if (!$user_id || !$course_id) return false;

        if (self::is_user_enrolled($user_id, $course_id)) {
            return true;
        }

        return $wpdb->insert(
            $table_name,
            [
                'user_id'     => $user_id,
                'course_id'   => $course_id,
                'enrolled_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );
    }

    /**
     * Unenroll a student from a course
     */
    public static function unenroll_student($user_id, $course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_enrollments';

        return $wpdb->delete(
            $table_name,
            [
                'user_id'   => intval($user_id),
                'course_id' => intval($course_id),
            ],
            ['%d', '%d']
        );
    }

    /**
     * Get all user IDs enrolled in a specific course
     */
    public static function get_enrolled_user_ids($course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_enrollments';

        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$table_name} WHERE course_id = %d",
            intval($course_id)
        ));

        return array_map('intval', $results ?: []);
    }

    /**
     * Get all course IDs a user is enrolled in
     */
    public static function get_user_enrolled_courses($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_enrollments';

        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT course_id FROM {$table_name} WHERE user_id = %d",
            intval($user_id)
        ));

        return array_map('intval', $results ?: []);
    }

    /**
     * Check if a user is enrolled in a course
     */
    public static function is_user_enrolled($user_id, $course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_enrollments';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND course_id = %d",
            intval($user_id),
            intval($course_id)
        ));

        return intval($count) > 0;
    }

    /**
     * Get total enrolled student count for a course
     */
    public static function get_enrolled_students_count($course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_enrollments';

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE course_id = %d",
            intval($course_id)
        )));
    }

    /**
     * Get total unique enrolled students count
     */
    public static function get_total_students_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_enrollments';

        $db_count = intval($wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table_name}"));

        $manual_offsets = $wpdb->get_var("
            SELECT SUM(CAST(meta_value AS UNSIGNED)) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_smlms_students_enrolled'
        ");

        return $db_count + intval($manual_offsets);
    }

    /**
     * Get total published courses count
     */
    public static function get_total_courses_count() {
        $counts = wp_count_posts('smlms_course');
        return intval($counts->publish ?? 0);
    }

    /**
     * Get total published lessons count
     */
    public static function get_total_lessons_count() {
        $counts = wp_count_posts('smlms_lesson');
        return intval($counts->publish ?? 0);
    }

    /**
     * Get total published topics count
     */
    public static function get_total_topics_count() {
        $counts = wp_count_posts('smlms_topic');
        return intval($counts->publish ?? 0);
    }

    /**
     * Get user completed step IDs for a specific course
     */
    public static function get_user_completed_steps($user_id, $course_id) {
        $user_id   = intval($user_id);
        $course_id = intval($course_id);
        if (!$user_id || !$course_id) return [];

        $completed = get_user_meta($user_id, '_smlms_completed_steps_' . $course_id, true);
        return is_array($completed) ? array_map('intval', $completed) : [];
    }

    /**
     * Toggle step completion status for a user
     */
    public static function toggle_step_completion($user_id, $course_id, $step_id) {
        $user_id   = intval($user_id);
        $course_id = intval($course_id);
        $step_id   = intval($step_id);

        if (!$user_id || !$course_id || !$step_id) return false;

        $completed = self::get_user_completed_steps($user_id, $course_id);
        $is_completed = in_array($step_id, $completed);

        if ($is_completed) {
            $completed = array_diff($completed, [$step_id]);
        } else {
            $completed[] = $step_id;
        }

        $completed = array_values(array_unique($completed));
        update_user_meta($user_id, '_smlms_completed_steps_' . $course_id, $completed);

        return !$is_completed;
    }

}