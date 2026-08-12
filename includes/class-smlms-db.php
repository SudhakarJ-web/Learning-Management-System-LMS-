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

                $topics = [];
                if (!empty($l_node['topics']) && is_array($l_node['topics'])) {
                    foreach ($l_node['topics'] as $t_node) {
                        $topic_id = intval($t_node['id'] ?? 0);
                        if (!$topic_id || get_post_status($topic_id) !== 'publish') continue;

                        $topics[] = [
                            'id'        => $topic_id,
                            'title'     => get_the_title($topic_id),
                            'permalink' => get_permalink($topic_id),
                            'duration'  => get_post_meta($topic_id, '_smlms_duration', true) ?: '5.00',
                        ];
                    }
                }

                $hierarchy[] = [
                    'lesson_id'    => $lesson_id,
                    'lesson_title' => get_the_title($lesson_id),
                    'permalink'    => get_permalink($lesson_id),
                    'duration'     => get_post_meta($lesson_id, '_smlms_duration', true) ?: '5.00',
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
            $child_topics = self::get_lesson_topics($lesson->ID);
            $topics_data  = [];

            foreach ($child_topics as $topic) {
                $topics_data[] = [
                    'id'        => $topic->ID,
                    'title'     => $topic->post_title,
                    'permalink' => get_permalink($topic->ID),
                    'duration'  => get_post_meta($topic->ID, '_smlms_duration', true) ?: '5.00',
                ];
            }

            $hierarchy[] = [
                'lesson_id'    => $lesson->ID,
                'lesson_title' => $lesson->post_title,
                'permalink'    => get_permalink($lesson->ID),
                'duration'     => get_post_meta($lesson->ID, '_smlms_duration', true) ?: '5.00',
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
}