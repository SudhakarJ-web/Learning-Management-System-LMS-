<?php
/**
 * Admin Menu Handler (Full Navigation Tree)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Admin_Menu {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menus']);
    }

    public static function register_menus() {
        // 1. Main Admin Menu
        add_menu_page(
            'LMS Setup',
            'Sabin Mathew LMS',
            'manage_options',
            'smlms_main_menu',
            [__CLASS__, 'render_setup_page'],
            'dashicons-welcome-learn-more',
            30
        );

        // 2. Submenu: Setup Dashboard
        add_submenu_page(
            'smlms_main_menu',
            'LMS Setup',
            'Setup',
            'manage_options',
            'smlms_main_menu',
            [__CLASS__, 'render_setup_page']
        );

        // 3. Submenu: Courses List
        add_submenu_page(
            'smlms_main_menu',
            'Courses',
            'Courses',
            'edit_posts',
            'edit.php?post_type=smlms_course'
        );

        // 4. Submenu: Lessons List
        add_submenu_page(
            'smlms_main_menu',
            'Lessons',
            'Lessons',
            'edit_posts',
            'edit.php?post_type=smlms_lesson'
        );

        // 5. Submenu: Topics List
        add_submenu_page(
            'smlms_main_menu',
            'Topics',
            'Topics',
            'edit_posts',
            'edit.php?post_type=smlms_topic'
        );

        // 6. Submenu: Orders / Enrollments
        add_submenu_page(
            'smlms_main_menu',
            'LMS Orders',
            'Orders',
            'manage_options',
            'smlms_orders',
            [__CLASS__, 'render_orders_page']
        );

        // 7. Submenu: Global Settings
        add_submenu_page(
            'smlms_main_menu',
            'LMS Settings',
            'Settings',
            'manage_options',
            'smlms_settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Render LMS Setup Dashboard
     */
    public static function render_setup_page() {
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

    /**
     * Render Orders Page Callback
     */
    public static function render_orders_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $orders_file = SMLMS_PLUGIN_DIR . 'includes/admin/views/orders-page.php';

        if (file_exists($orders_file)) {
            include $orders_file;
        } else {
            ?>
            <div class="wrap">
                <h1>LMS Orders & Enrollments</h1>
                <p>Manage student course transactions and manual access records here.</p>
            </div>
            <?php
        }
    }

    /**
     * Render Settings Page Callback
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings_file = SMLMS_PLUGIN_DIR . 'includes/admin/views/settings-page.php';

        if (file_exists($settings_file)) {
            include $settings_file;
        } else {
            ?>
            <div class="wrap">
                <h1>LMS Global Settings</h1>
                <p>Configure general plugin settings, video defaults, and payment gateway integrations.</p>
            </div>
            <?php
        }
    }
}

SMLMS_Admin_Menu::init();