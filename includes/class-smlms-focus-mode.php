<?php
/**
 * Focus Mode, Course Template Loader, & Asset Manager Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Focus_Mode {

    public function __construct() {
        add_filter('template_include', [$this, 'load_custom_templates']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function load_custom_templates($template) {
        if (is_singular('smlms_topic') || is_singular('smlms_lesson')) {
            $focus_template = SMLMS_PLUGIN_DIR . 'templates/focus-mode-canvas.php';
            if (file_exists($focus_template)) {
                return $focus_template;
            }
        }

        if (is_singular('smlms_course')) {
            $course_template = SMLMS_PLUGIN_DIR . 'templates/single-smlms_course.php';
            if (file_exists($course_template)) {
                return $course_template;
            }
        }

        return $template;
    }

    public function enqueue_frontend_assets() {
        // Single Course Landing Page Assets with Dynamic Timestamp Cache-Busting
        if (is_singular('smlms_course')) {
            $css_path = SMLMS_PLUGIN_DIR . 'public/css/smlms-course-single.css';
            $css_ver  = file_exists($css_path) ? filemtime($css_path) : SMLMS_VERSION;

            wp_enqueue_style(
                'smlms-course-single-css',
                SMLMS_PLUGIN_URL . 'public/css/smlms-course-single.css',
                ['dashicons'],
                $css_ver
            );

            wp_enqueue_script(
                'smlms-course-single-js',
                SMLMS_PLUGIN_URL . 'public/js/smlms-course-single.js',
                ['jquery'],
                SMLMS_VERSION,
                true
            );
        }

        // Focus Mode Player Canvas Assets
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

            wp_localize_script('smlms-app-js', 'smlmsSettings', [
                'root'       => esc_url_raw(rest_url('smlms/v1/')),
                'nonce'      => wp_create_nonce('wp_rest'),
                'current_id' => get_the_ID()
            ]);
        }
    }

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

if (!function_exists('smlms_render_sidebar_hierarchy')) {
    function smlms_render_sidebar_hierarchy($lesson_id = 0, $topic_id = 0) {
        $course_id = SMLMS_DB::get_parent_course_id($lesson_id ? $lesson_id : get_the_ID());
        $current_topic_id = $topic_id ? $topic_id : get_the_ID();

        if (file_exists(SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php')) {
            include SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php';
        }
    }
}