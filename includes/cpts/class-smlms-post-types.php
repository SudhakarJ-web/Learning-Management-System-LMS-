<?php
/**
 * Post Types, Taxonomies, and Custom Nested Rewrite Rules Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Post_Types {

    public static function init() {
        add_action('init', [__CLASS__, 'register_all']);
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_filter('post_type_link', [__CLASS__, 'filter_post_type_link'], 10, 2);
        add_filter('post_updated_messages', [__CLASS__, 'custom_post_updated_messages']);
    }

    public static function register_all() {
        register_taxonomy('smlms_course_category', ['smlms_course'], [
            'labels'       => ['name' => 'Course Categories', 'singular_name' => 'Course Category'],
            'hierarchical' => true,
            'show_ui'      => true,
            'show_in_rest' => true,
        ]);

        register_post_type('smlms_course', [
            'labels' => [
                'name'               => 'Courses',
                'singular_name'      => 'Course',
                'menu_name'          => 'Courses',
                'add_new'            => 'Add New Course',
                'add_new_item'       => 'Add New Course',
                'edit_item'          => 'Edit Course',
                'new_item'           => 'New Course',
                'view_item'          => 'View Course',
                'search_items'       => 'Search Courses',
                'not_found'          => 'No courses found',
                'not_found_in_trash' => 'No courses found in trash',
            ],
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => ['slug' => 'courses', 'with_front' => false],
            'supports'     => ['title', 'editor', 'thumbnail', 'author', 'comments', 'page-attributes'],
            'taxonomies'   => ['category', 'smlms_course_category'],
            'show_ui'      => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
        ]);

        register_post_type('smlms_lesson', [
            'labels' => [
                'name'          => 'Lessons',
                'singular_name' => 'Lesson',
                'add_new'       => 'Add New Lesson',
                'add_new_item'  => 'Add New Lesson',
                'edit_item'     => 'Edit Lesson',
                'view_item'     => 'View Lesson',
            ],
            'public'       => true,
            'supports'     => ['title', 'editor', 'page-attributes'],
            'rewrite'      => ['slug' => 'courses/%smlms_course%', 'with_front' => false],
            'show_ui'      => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
        ]);

        register_post_type('smlms_topic', [
            'labels' => [
                'name'          => 'Topics',
                'singular_name' => 'Topic',
                'add_new'       => 'Add New Topic',
                'add_new_item'  => 'Add New Topic',
                'edit_item'     => 'Edit Topic',
                'view_item'     => 'View Topic',
            ],
            'public'       => true,
            'supports'     => ['title', 'editor', 'page-attributes'],
            'rewrite'      => ['slug' => 'courses/%smlms_course%/%smlms_lesson%', 'with_front' => false],
            'show_ui'      => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Add Custom Rewrite Rules for Nested URLs
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^courses/([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?smlms_topic=$matches[3]',
            'top'
        );
        add_rewrite_rule(
            '^courses/([^/]+)/([^/]+)/?$',
            'index.php?smlms_lesson=$matches[2]',
            'top'
        );
    }

    /**
     * Dynamically replace %smlms_course% and %smlms_lesson% tags in permalinks
     */
    public static function filter_post_type_link($post_link, $post) {
        if ($post->post_type === 'smlms_lesson') {
            $course_id = SMLMS_DB::get_parent_course_id($post->ID);
            $course_slug = 'uncategorized';

            if ($course_id) {
                $course = get_post($course_id);
                if ($course) {
                    $course_slug = $course->post_name;
                }
            }
            return str_replace('%smlms_course%', $course_slug, $post_link);
        }

        if ($post->post_type === 'smlms_topic') {
            $lesson_id   = get_post_meta($post->ID, '_smlms_parent_lesson_id', true);
            $course_id   = SMLMS_DB::get_parent_course_id($post->ID);
            $course_slug = 'uncategorized';
            $lesson_slug = 'uncategorized';

            if ($course_id) {
                $course = get_post($course_id);
                if ($course) {
                    $course_slug = $course->post_name;
                }
            }
            if ($lesson_id) {
                $lesson = get_post($lesson_id);
                if ($lesson) {
                    $lesson_slug = $lesson->post_name;
                }
            }

            $post_link = str_replace('%smlms_course%', $course_slug, $post_link);
            $post_link = str_replace('%smlms_lesson%', $lesson_slug, $post_link);
            return $post_link;
        }

        return $post_link;
    }

    /**
     * Replace generic "Post updated" messages
     */
    public static function custom_post_updated_messages($messages) {
        global $post, $post_ID;

        if (!$post) {
            return $messages;
        }

        $permalink = get_permalink($post_ID);

        $messages['smlms_course'] = [
            0  => '',
            1  => sprintf(__('Course updated. <a href="%s">View Course</a>', 'sabinmathew-lms'), esc_url($permalink)),
            6  => sprintf(__('Course published. <a href="%s">View Course</a>', 'sabinmathew-lms'), esc_url($permalink)),
            7  => __('Course saved.', 'sabinmathew-lms'),
        ];

        return $messages;
    }
}
SMLMS_Post_Types::init();