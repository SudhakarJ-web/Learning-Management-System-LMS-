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
     * Check if a user has completed all actionable steps in a course
     */
    public static function is_course_completed($user_id, $course_id) {
        $user_id   = intval($user_id);
        $course_id = intval($course_id);
        if (!$user_id || !$course_id) return false;

        // Administrators can always review for testing purposes
        if (current_user_can('manage_options')) return true;

        $hierarchy = self::get_course_hierarchy($course_id);
        if (empty($hierarchy)) return false;

        // Collect actionable leaf steps (child topics, or lessons without topics)
        $all_leaf_step_ids = [];
        foreach ($hierarchy as $lesson) {
            if (!empty($lesson['topics'])) {
                foreach ($lesson['topics'] as $topic) {
                    $all_leaf_step_ids[] = intval($topic['id']);
                }
            } else {
                $all_leaf_step_ids[] = intval($lesson['lesson_id']);
            }
        }

        if (empty($all_leaf_step_ids)) return false;

        $completed_steps = self::get_user_completed_steps($user_id, $course_id);
        $diff            = array_diff($all_leaf_step_ids, $completed_steps);

        return empty($diff);
    }

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

    public static function get_enrolled_students_count($course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smlms_enrollments';

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE course_id = %d",
            intval($course_id)
        )));
    }

    public static function get_user_completed_steps($user_id, $course_id) {
        $user_id   = intval($user_id);
        $course_id = intval($course_id);
        if (!$user_id || !$course_id) return [];

        $completed = get_user_meta($user_id, '_smlms_completed_steps_' . $course_id, true);
        return is_array($completed) ? array_map('intval', $completed) : [];
    }

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