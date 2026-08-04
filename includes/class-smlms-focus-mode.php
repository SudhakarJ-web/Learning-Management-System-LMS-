<?php
/**
 * Focus Mode, Course Template Loader, & Asset Manager Class
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SMLMS_Focus_Mode {

    public function __construct() {
        // Hijack default theme templates for Focus Mode & Single Course Pages
        add_filter('template_include', [$this, 'load_custom_templates']);

        // Enqueue frontend scripts & styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Enqueue admin scripts & styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Loads custom templates for Courses, Lessons, and Topics.
     *
     * @param string $template Current theme template path.
     * @return string Modified template path.
     */
    public function load_custom_templates($template) {
        // 1. Focus Mode Canvas for Lessons & Topics
        if (is_singular('smlms_topic') || is_singular('smlms_lesson')) {
            $focus_template = SMLMS_PLUGIN_DIR . 'templates/focus-mode-canvas.php';
            if (file_exists($focus_template)) {
                return $focus_template;
            }
        }

        // 2. Single Course Frontend View (LearnDash Replica Layout)
        if (is_singular('smlms_course')) {
            $course_template = SMLMS_PLUGIN_DIR . 'templates/single-smlms_course.php';
            if (file_exists($course_template)) {
                return $course_template;
            }
        }

        return $template;
    }

    /**
     * Enqueues frontend styles and JavaScript.
     */
    public function enqueue_frontend_assets() {
        // Assets for Single Course Landing Page
        if (is_singular('smlms_course')) {
            wp_enqueue_style(
                'smlms-course-single-css',
                SMLMS_PLUGIN_URL . 'public/css/smlms-course-single.css',
                ['dashicons'],
                SMLMS_VERSION
            );

            wp_enqueue_script(
                'smlms-course-single-js',
                SMLMS_PLUGIN_URL . 'public/js/smlms-course-single.js',
                ['jquery'],
                SMLMS_VERSION,
                true
            );
        }

        // Assets for Focus Mode Player Canvas
        if (is_singular('smlms_topic') || is_singular('smlms_lesson')) {
            wp_enqueue_style(
                'smlms-focus-css',
                SMLMS_PLUGIN_URL . 'public/css/smlms-focus-mode.css',
                ['dashicons'],
                SMLMS_VERSION
            );

            wp_enqueue_script(
                'smlms-app-js',
                SMLMS_PLUGIN_URL . 'public/js/smlms-app.js',
                ['jquery'],
                SMLMS_VERSION,
                true
            );

            // Pass REST parameters and nonces dynamically to JavaScript
            wp_localize_script('smlms-app-js', 'smlmsSettings', [
                'root'       => esc_url_raw(rest_url('smlms/v1/')),
                'nonce'      => wp_create_nonce('wp_rest'),
                'current_id' => get_the_ID()
            ]);
        }
    }

    /**
     * Enqueues admin styles and JavaScript for LMS CPT edit screens.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();

        if ($screen && in_array($screen->post_type, ['smlms_course', 'smlms_lesson', 'smlms_topic'], true)) {
            wp_enqueue_style(
                'smlms-admin-css',
                SMLMS_PLUGIN_URL . 'admin/css/smlms-admin.css',
                [],
                SMLMS_VERSION
            );

            wp_enqueue_script(
                'smlms-admin-js',
                SMLMS_PLUGIN_URL . 'admin/js/smlms-admin-builder.js',
                ['jquery'],
                SMLMS_VERSION,
                true
            );
        }
    }
}

/**
 * Fallback Helper Function for Focus Mode Sidebar
 */
if (!function_exists('smlms_render_sidebar_hierarchy')) {
    function smlms_render_sidebar_hierarchy($lesson_id = 0, $topic_id = 0) {
        $course_id = get_post_meta($lesson_id, '_smlms_parent_course_id', true);
        $current_topic_id = $topic_id ? $topic_id : get_the_ID();

        if (file_exists(SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php')) {
            include SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php';
        }
    }
}