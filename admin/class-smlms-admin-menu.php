<?php
/**
 * Admin Sidebar Menu Registration & Submenu Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Admin_Menu {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_filter('parent_file', [__CLASS__, 'fix_parent_menu_highlight']);
    }

    public static function register_menu() {
        // Main Top-Level Menu
        add_menu_page(
            'Sabin Mathew LMS',
            'Sabin Mathew LMS',
            'manage_options',
            'smlms_main_menu',
            [__CLASS__, 'render_setup_page'],
            'dashicons-welcome-learn-more',
            30
        );

        // Submenus
        add_submenu_page('smlms_main_menu', 'Setup', 'Setup', 'manage_options', 'smlms_main_menu', [__CLASS__, 'render_setup_page']);
        add_submenu_page('smlms_main_menu', 'Courses', 'Courses', 'manage_options', 'edit.php?post_type=smlms_course');
        add_submenu_page('smlms_main_menu', 'Lessons', 'Lessons', 'manage_options', 'edit.php?post_type=smlms_lesson');
        add_submenu_page('smlms_main_menu', 'Topics', 'Topics', 'manage_options', 'edit.php?post_type=smlms_topic');
        add_submenu_page('smlms_main_menu', 'Orders', 'Orders', 'manage_options', 'smlms_orders', [__CLASS__, 'render_orders_page']);
        add_submenu_page('smlms_main_menu', 'Settings', 'Settings', 'manage_options', 'smlms_settings', [__CLASS__, 'render_settings_page']);
    }

    public static function fix_parent_menu_highlight($parent_file) {
        global $current_screen;
        if ($current_screen && in_array($current_screen->post_type, ['smlms_course', 'smlms_lesson', 'smlms_topic'], true)) {
            return 'smlms_main_menu';
        }
        return $parent_file;
    }

    public static function render_setup_page() {
        echo '<div class="wrap"><h1>LMS Setup</h1></div>';
    }

    public static function render_orders_page() {
        echo '<div class="wrap"><h1>LMS Orders</h1></div>';
    }

    public static function render_settings_page() {
        echo '<div class="wrap"><h1>LMS Settings</h1></div>';
    }
}
SMLMS_Admin_Menu::init();