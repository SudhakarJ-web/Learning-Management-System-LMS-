<?php
/**
 * Sabin Mathew LMS - Database & Helper Queries
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_DB {

    /**
     * Check if a specific user is actively enrolled in a course
     */
    public static function is_user_enrolled($user_id, $course_id) {
        if (!$user_id || !$course_id) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'smlms_enrollments';

        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$table} WHERE user_id = %d AND course_id = %d AND status = 'active' LIMIT 1",
            intval($user_id),
            intval($course_id)
        ));

        return !empty($status);
    }

    /**
     * Get total active admin-granted enrollments count for a course from DB
     */
    public static function get_enrolled_students_count($course_id) {
        if (!$course_id) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'smlms_enrollments';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE course_id = %d AND status = 'active'",
            intval($course_id)
        ));

        return intval($count);
    }

    /**
     * Fetch all topics belonging to a specific lesson ID
     */
    public static function get_lesson_topics($lesson_id) {
        if (!$lesson_id) {
            return [];
        }

        return get_posts([
            'post_type'      => 'smlms_topic',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_smlms_parent_lesson_id',
            'meta_value'     => intval($lesson_id),
            'orderby'        => 'menu_order',
            'order'          => 'ASC'
        ]);
    }

    /**
     * Fetch complete course hierarchy (Lessons & Topics)
     */
    public static function get_course_hierarchy($course_id, $user_id = 0) {
        $tree_json = get_post_meta($course_id, '_smlms_course_tree_json', true);
        if (empty($tree_json)) {
            return [];
        }

        $tree_data = json_decode($tree_json, true);
        if (!is_array($tree_data)) {
            return [];
        }

        $hierarchy = [];

        foreach ($tree_data as $lesson_node) {
            $lesson_id = intval($lesson_node['id']);
            $lesson_post = get_post($lesson_id);

            if (!$lesson_post || $lesson_post->post_status !== 'publish') {
                continue;
            }

            $topics = [];
            if (!empty($lesson_node['topics']) && is_array($lesson_node['topics'])) {
                foreach ($lesson_node['topics'] as $topic_node) {
                    $topic_id = intval($topic_node['id']);
                    $topic_post = get_post($topic_id);

                    if ($topic_post && $topic_post->post_status === 'publish') {
                        $topics[] = [
                            'id'        => $topic_id,
                            'title'     => $topic_post->post_title,
                            'permalink' => get_permalink($topic_id),
                            'duration'  => get_post_meta($topic_id, '_smlms_duration', true) ?: '5:00',
                        ];
                    }
                }
            }

            $hierarchy[] = [
                'lesson_id'    => $lesson_id,
                'lesson_title' => $lesson_post->post_title,
                'permalink'    => get_permalink($lesson_id),
                'duration'     => get_post_meta($lesson_id, '_smlms_duration', true) ?: '10:00',
                'topics'       => $topics,
            ];
        }

        return $hierarchy;
    }

    /**
     * Get Parent Course ID for any Lesson or Topic
     */
    public static function get_parent_course_id($post_id) {
        $post_type = get_post_type($post_id);

        if ($post_type === 'smlms_course') {
            return $post_id;
        }

        if ($post_type === 'smlms_lesson') {
            return intval(get_post_meta($post_id, '_smlms_parent_course_id', true));
        }

        if ($post_type === 'smlms_topic') {
            $parent_lesson_id = get_post_meta($post_id, '_smlms_parent_lesson_id', true);
            if ($parent_lesson_id) {
                return intval(get_post_meta($parent_lesson_id, '_smlms_parent_course_id', true));
            }
        }

        return 0;
    }
}