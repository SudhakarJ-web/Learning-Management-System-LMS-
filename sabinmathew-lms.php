<?php
/**
 * Plugin Name: Sabin Mathew LMS
 * Plugin URI:  https://sabinmathew.com/
 * Description: Custom Lightweight LMS for Sabin Mathew Engineering Courses.
 * Version:     1.0.7
 * Author:      Sabin Mathew
 * Text Domain: sabinmathew-lms
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('SMLMS_VERSION', '1.0.7');
define('SMLMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMLMS_PLUGIN_URL', plugin_dir_url(__FILE__));

// --- 1. Core Engine & Database ---
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-db.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-focus-mode.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-activator.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-payments.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-rest-api.php';

// --- 2. Post Types & Taxonomies ---
require_once SMLMS_PLUGIN_DIR . 'includes/cpts/class-smlms-post-types.php';

// --- 3. Admin Area Modules ---
if (is_admin()) {
    require_once SMLMS_PLUGIN_DIR . 'admin/class-smlms-admin-menu.php';
    require_once SMLMS_PLUGIN_DIR . 'admin/class-smlms-admin-tabs.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/class-smlms-admin-columns.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/class-smlms-meta-saver.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/class-smlms-user-profile.php';

    // Meta Box Renderers
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/meta-boxes/class-meta-course-builder.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/meta-boxes/class-meta-course-details.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/meta-boxes/class-meta-enrollment.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/meta-boxes/class-meta-students.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/meta-boxes/class-meta-item-custom.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/meta-boxes/class-meta-display-options.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/meta-boxes/class-meta-course-custom.php';
}

// --- 4. Template Redirect Loader ---
add_filter('template_include', 'smlms_register_course_templates', 99);
function smlms_register_course_templates($template) {
    if (is_singular('smlms_course')) {
        $course_template = SMLMS_PLUGIN_DIR . 'templates/single-smlms_course.php';
        if (file_exists($course_template)) {
            return $course_template;
        }
    }

    if (is_singular(['smlms_lesson', 'smlms_topic'])) {
        $focus_template = SMLMS_PLUGIN_DIR . 'templates/focus-mode-canvas.php';
        if (file_exists($focus_template)) {
            return $focus_template;
        }
    }

    return $template;
}

// --- 5. High-Priority Asset Enqueuer (Bypasses Staging Cache) ---
add_action('wp_enqueue_scripts', 'smlms_enqueue_frontend_assets', 999);
function smlms_enqueue_frontend_assets() {
    global $post;
    
    $post_type = isset($post->post_type) ? $post->post_type : get_post_type();

    // Single Course Landing Page CSS & JS
    if (is_singular('smlms_course') || $post_type === 'smlms_course') {
        wp_enqueue_style('dashicons');
        wp_enqueue_style(
            'smlms-course-single-css', 
            SMLMS_PLUGIN_URL . 'public/css/smlms-course-single.css', 
            [], 
            time() // Appends dynamic timestamp query string to bust cache
        );
        wp_enqueue_script(
            'smlms-course-single-js', 
            SMLMS_PLUGIN_URL . 'public/js/smlms-course-single.js', 
            ['jquery'], 
            time(), 
            true
        );
    }

    // Focus Mode Canvas CSS
    if (is_singular(['smlms_lesson', 'smlms_topic']) || in_array($post_type, ['smlms_lesson', 'smlms_topic'], true)) {
        wp_enqueue_style('dashicons');
        wp_enqueue_style(
            'smlms-focus-mode-css', 
            SMLMS_PLUGIN_URL . 'public/css/smlms-focus-mode.css', 
            [], 
            time()
        );
    }
}

add_action('admin_enqueue_scripts', 'smlms_enqueue_admin_assets');
function smlms_enqueue_admin_assets($hook) {
    global $post_type;
    
    if (in_array($post_type, ['smlms_course', 'smlms_lesson', 'smlms_topic'], true) || in_array($hook, ['profile.php', 'user-edit.php'], true)) {
        wp_enqueue_style('smlms-admin-css', SMLMS_PLUGIN_URL . 'admin/css/smlms-admin.css', [], SMLMS_VERSION);
        wp_enqueue_script('smlms-admin-students-js', SMLMS_PLUGIN_URL . 'admin/js/smlms-admin-students.js', ['jquery'], SMLMS_VERSION, true);
        
        if (in_array($post_type, ['smlms_course', 'smlms_lesson', 'smlms_topic'], true)) {
            wp_enqueue_script('smlms-admin-builder-js', SMLMS_PLUGIN_URL . 'admin/js/smlms-admin-builder.js', ['jquery'], SMLMS_VERSION, true);
        }
    }
}

// --- 6. Activation Hooks ---
register_activation_hook(__FILE__, ['SMLMS_Activator', 'activate']);