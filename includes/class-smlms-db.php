<?php
/**
 * Database & Hierarchy Query Service
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_DB {

    /**
     * Fast SQL JOIN for User Dashboard
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
     * Saves or updates topic completion telemetry
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
     * Enrollment Status Checker
     */
    public static function is_user_enrolled($user_id, $course_id) {
        global $wpdb;
        $sql = "SELECT id FROM {$wpdb->prefix}smlms_enrollments WHERE user_id = %d AND course_id = %d AND status = 'active' LIMIT 1";
        return (bool) $wpdb->get_var($wpdb->prepare($sql, $user_id, $course_id));
    }

    /**
     * Resolves the Root Course ID from any Lesson or Topic ID
     */
    public static function get_parent_course_id($post_id) {
        $post_type = get_post_type($post_id);

        if ($post_type === 'smlms_course') {
            return $post_id;
        }

        if ($post_type === 'smlms_topic') {
            $lesson_id = get_post_meta($post_id, '_smlms_parent_lesson_id', true);
            if ($lesson_id) {
                $course_id = get_post_meta($lesson_id, '_smlms_parent_course_id', true);
                if ($course_id) return intval($course_id);
            }
        }

        if ($post_type === 'smlms_lesson') {
            $course_id = get_post_meta($post_id, '_smlms_parent_course_id', true);
            if ($course_id) return intval($course_id);
        }

        // Fallback: Check Builder JSON across all published courses
        $courses = get_posts(['post_type' => 'smlms_course', 'posts_per_page' => -1, 'fields' => 'ids']);
        foreach ($courses as $c_id) {
            $raw_tree = get_post_meta($c_id, '_smlms_course_tree_json', true);
            if (!empty($raw_tree)) {
                $tree = json_decode($raw_tree, true);
                if (is_array($tree)) {
                    foreach ($tree as $l_node) {
                        if ($l_node['id'] == $post_id) return $c_id;
                        if (!empty($l_node['topics'])) {
                            foreach ($l_node['topics'] as $t_node) {
                                if ($t_node['id'] == $post_id) return $c_id;
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Fetches Complete Course Hierarchy (Lessons, Topics, Durations, Types, Progress)
     */
    public static function get_course_hierarchy($course_id, $user_id = 0) {
        global $wpdb;

        if (!$course_id) {
            return [];
        }

        // Check if structure exists in Course Builder JSON
        $raw_tree = get_post_meta($course_id, '_smlms_course_tree_json', true);
        $builder_tree = !empty($raw_tree) ? json_decode($raw_tree, true) : [];

        $hierarchy = [];

        if (!empty($builder_tree) && is_array($builder_tree)) {
            foreach ($builder_tree as $lesson_node) {
                $lesson_id = intval($lesson_node['id']);
                $lesson_post = get_post($lesson_id);
                if (!$lesson_post || $lesson_post->post_status !== 'publish') continue;

                $topics_data = [];
                if (!empty($lesson_node['topics']) && is_array($lesson_node['topics'])) {
                    foreach ($lesson_node['topics'] as $topic_node) {
                        $topic_id = intval($topic_node['id']);
                        $topic_post = get_post($topic_id);
                        if (!$topic_post || $topic_post->post_status !== 'publish') continue;

                        $prog = null;
                        if ($user_id) {
                            $prog = $wpdb->get_row($wpdb->prepare(
                                "SELECT is_completed, watched_seconds FROM {$wpdb->prefix}smlms_progress WHERE user_id = %d AND topic_id = %d",
                                $user_id, $topic_id
                            ));
                        }

                        $topics_data[] = [
                            'id'           => $topic_id,
                            'title'        => $topic_post->post_title,
                            'permalink'    => get_permalink($topic_id),
                            'duration'     => get_post_meta($topic_id, '_smlms_duration', true) ?: '0.00',
                            'content_type' => get_post_meta($topic_id, '_smlms_content_type', true) ?: 'video',
                            'is_completed' => $prog ? (bool)$prog->is_completed : false,
                            'watched_sec'  => $prog ? intval($prog->watched_seconds) : 0
                        ];
                    }
                }

                $hierarchy[] = [
                    'lesson_id'    => $lesson_id,
                    'lesson_title' => $lesson_post->post_title,
                    'permalink'    => get_permalink($lesson_id),
                    'duration'     => get_post_meta($lesson_id, '_smlms_duration', true) ?: '0.00',
                    'content_type' => get_post_meta($lesson_id, '_smlms_content_type', true) ?: 'video',
                    'topics'       => $topics_data
                ];
            }
        } else {
            // Query by parent meta fallback
            $lessons = get_posts([
                'post_type'      => 'smlms_lesson',
                'posts_per_page' => -1,
                'meta_key'       => '_smlms_parent_course_id',
                'meta_value'     => $course_id,
                'orderby'        => 'menu_order',
                'order'          => 'ASC'
            ]);

            foreach ($lessons as $lesson) {
                $topics = get_posts([
                    'post_type'      => 'smlms_topic',
                    'posts_per_page' => -1,
                    'meta_key'       => '_smlms_parent_lesson_id',
                    'meta_value'     => $lesson->ID,
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC'
                ]);

                $topics_data = [];
                foreach ($topics as $topic) {
                    $prog = $user_id ? $wpdb->get_row($wpdb->prepare(
                        "SELECT is_completed, watched_seconds FROM {$wpdb->prefix}smlms_progress WHERE user_id = %d AND topic_id = %d",
                        $user_id, $topic->ID
                    )) : null;

                    $topics_data[] = [
                        'id'           => $topic->ID,
                        'title'        => $topic->post_title,
                        'permalink'    => get_permalink($topic->ID),
                        'duration'     => get_post_meta($topic->ID, '_smlms_duration', true) ?: '0.00',
                        'content_type' => get_post_meta($topic->ID, '_smlms_content_type', true) ?: 'video',
                        'is_completed' => $prog ? (bool)$prog->is_completed : false,
                        'watched_sec'  => $prog ? intval($prog->watched_seconds) : 0
                    ];
                }

                $hierarchy[] = [
                    'lesson_id'    => $lesson->ID,
                    'lesson_title' => $lesson->post_title,
                    'permalink'    => get_permalink($lesson->ID),
                    'duration'     => get_post_meta($lesson->ID, '_smlms_duration', true) ?: '0.00',
                    'content_type' => get_post_meta($lesson->ID, '_smlms_content_type', true) ?: 'video',
                    'topics'       => $topics_data
                ];
            }
        }

        return $hierarchy;
    }
}