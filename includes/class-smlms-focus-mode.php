<?php
/**
 * Focus Mode & Asset Loader Class
 *
 * Handles template hijacking for distraction-free Focus Mode
 * and enqueues frontend/admin scripts and styles.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SMLMS_Focus_Mode {

    /**
     * Constructor to hook methods into WordPress lifecycle.
     */
    public function __construct() {
        // Hijack default theme templates for Focus Mode
        add_filter('template_include', [$this, 'load_focus_mode_template']);

        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Replaces standard theme templates with the Focus Mode canvas
     * for single lessons and topics.
     *
     * @param string $template Current template path.
     * @return string Modified template path.
     */
    public function load_focus_mode_template($template) {
        if (is_singular('smlms_topic') || is_singular('smlms_lesson')) {
            $custom_template = SMLMS_PLUGIN_DIR . 'templates/focus-mode-canvas.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }

    /**
     * Enqueues frontend styles and JavaScript for Focus Mode.
     */
    public function enqueue_frontend_assets() {
        if (is_singular('smlms_topic') || is_singular('smlms_lesson')) {
            wp_enqueue_style(
                'smlms-focus-css',
                SMLMS_PLUGIN_URL . 'public/css/smlms-focus-mode.css',
                [],
                SMLMS_VERSION
            );

            wp_enqueue_script(
                'smlms-app-js',
                SMLMS_PLUGIN_URL . 'public/js/smlms-app.js',
                ['jquery'],
                SMLMS_VERSION,
                true
            );

            // Pass REST settings, nonces, and parameters dynamically to JavaScript
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
        global $post_type;

        if (in_array($post_type, ['smlms_course', 'smlms_lesson', 'smlms_topic'], true)) {
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