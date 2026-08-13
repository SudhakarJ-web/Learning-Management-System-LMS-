<?php
/**
 * Plugin Name: Sabin Mathew LMS
 * Plugin URI:  https://sabinmathew.com/
 * Description: Custom Lightweight LMS for Sabin Mathew Engineering Courses.
 * Version:     1.1.0
 * Author:      Sabin Mathew
 * Text Domain: sabinmathew-lms
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('SMLMS_VERSION', '1.0.9');
define('SMLMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMLMS_PLUGIN_URL', plugin_dir_url(__FILE__));

// --- 1. Core Engine & Database ---
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-db.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-focus-mode.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-activator.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-payments.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-rest-api.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-payment-handler.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-dashboard.php';
// Add under section "--- 1. Core Engine & Database ---"
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-reviews.php';

// --- 2. Post Types & Taxonomies ---
require_once SMLMS_PLUGIN_DIR . 'includes/cpts/class-smlms-post-types.php';

// --- 3. Admin Area Modules ---
if (is_admin()) {
    require_once SMLMS_PLUGIN_DIR . 'admin/class-smlms-admin-menu.php';
    require_once SMLMS_PLUGIN_DIR . 'admin/class-smlms-admin-tabs.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/class-smlms-admin-columns.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/class-smlms-meta-saver.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/class-smlms-user-profile.php';
    require_once SMLMS_PLUGIN_DIR . 'includes/admin/class-user-course-access.php';

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

// --- 5. Asset Enqueuer ---
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
            time()
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
    
    // Check if on LMS post types, user profile screens, or LMS setup menu dashboard
    $is_lms_post_type = in_array($post_type, ['smlms_course', 'smlms_lesson', 'smlms_topic'], true);
    $is_profile_page  = in_array($hook, ['profile.php', 'user-edit.php'], true);
    $is_setup_page    = (strpos($hook, 'smlms_main_menu') !== false);

    if ($is_lms_post_type || $is_profile_page || $is_setup_page) {
        wp_enqueue_style('smlms-admin-css', SMLMS_PLUGIN_URL . 'admin/css/smlms-admin.css', [], time());
        wp_enqueue_script('smlms-admin-students-js', SMLMS_PLUGIN_URL . 'admin/js/smlms-admin-students.js', ['jquery'], SMLMS_VERSION, true);
        
        if ($is_lms_post_type) {
            wp_enqueue_script('smlms-admin-builder-js', SMLMS_PLUGIN_URL . 'admin/js/smlms-admin-builder.js', ['jquery'], SMLMS_VERSION, true);
        }
    }
}

// --- 6. Activation Hooks ---
register_activation_hook(__FILE__, ['SMLMS_Activator', 'activate']);

// --- 7. Free Instant Enrollment Handler ---
add_action('init', 'smlms_handle_free_enrollment');
function smlms_handle_free_enrollment() {
    if (isset($_GET['smlms_action']) && $_GET['smlms_action'] === 'free_enroll') {
        $course_id = intval($_GET['course_id'] ?? 0);
        $user_id   = get_current_user_id();

        if ($course_id > 0 && $user_id > 0 && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'smlms_free_enroll_' . $course_id)) {
            $access_type = get_post_meta($course_id, '_smlms_access_type', true);
            if ($access_type === 'free') {
                SMLMS_DB::enroll_student($user_id, $course_id);
                wp_safe_redirect(get_permalink($course_id));
                exit;
            }
        }
    }
}

// --- 8. Setup Page Callback Render Handler ---
if (!function_exists('smlms_render_setup_page')) {
    function smlms_render_setup_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $setup_view_file = SMLMS_PLUGIN_DIR . 'includes/admin/views/setup-page.php';

        if (file_exists($setup_view_file)) {
            include $setup_view_file;
        } else {
            echo '<div class="wrap"><h2>LMS Setup</h2><p>Setup template file not found at: <code>' . esc_html($setup_view_file) . '</code></p></div>';
        }
    }
}

// --- Step Completion Toggle AJAX Handler ---
add_action('wp_ajax_smlms_toggle_step_complete', 'smlms_ajax_toggle_step_complete');
function smlms_ajax_toggle_step_complete() {
    check_ajax_referer('smlms_progress_nonce', '_wpnonce');

    $user_id   = get_current_user_id();
    $post_id   = intval($_POST['post_id'] ?? 0);
    $course_id = intval($_POST['course_id'] ?? 0);

    if (!$user_id || !$post_id || !$course_id) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    $is_now_completed = SMLMS_DB::toggle_step_completion($user_id, $course_id, $post_id);
    $hierarchy        = SMLMS_DB::get_course_hierarchy($course_id, $user_id);

    // Calculate updated total steps & completed count
    $valid_step_ids    = [];
    $total_steps_count = 0;

    if (!empty($hierarchy)) {
        foreach ($hierarchy as $l_item) {
            $l_id    = $l_item['lesson_id'];
            $l_video = get_post_meta($l_id, '_smlms_video_id', true) ?: get_post_meta($l_id, '_smlms_media_embed', true);

            if (!empty(trim((string)$l_video))) {
                $total_steps_count++;
                $valid_step_ids[] = $l_id;
            }

            if (!empty($l_item['topics'])) {
                foreach ($l_item['topics'] as $t_item) {
                    $total_steps_count++;
                    $valid_step_ids[] = $t_item['id'];
                }
            }
        }
    }

    $completed_ids = SMLMS_DB::get_user_completed_steps($user_id, $course_id);
    $completed_count = 0;
    foreach ($valid_step_ids as $v_id) {
        if (in_array($v_id, $completed_ids)) {
            $completed_count++;
        }
    }

    $percentage = ($total_steps_count > 0) ? round(($completed_count / $total_steps_count) * 100) : 0;

    wp_send_json_success([
        'is_completed'    => $is_now_completed,
        'percentage'      => $percentage,
        'completed_count' => $completed_count,
        'total_count'     => $total_steps_count,
    ]);
}