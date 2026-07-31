<?php
/**
 * Plugin Name: Sabin Mathew LMS
 * Description: High-performance, native WordPress LMS with custom MySQL telemetry tables and Focus Mode.
 * Version:     1.0.0
 * Author:      Sabin Mathew
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('SMLMS_VERSION', '1.0.0');
define('SMLMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMLMS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require Core Files
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-activator.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-db.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-cpt.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-focus-mode.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-rest-api.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-dashboard.php';
require_once SMLMS_PLUGIN_DIR . 'includes/class-smlms-payments.php';
require_once SMLMS_PLUGIN_DIR . 'admin/class-smlms-admin-tabs.php';



// Register Activation Hook (Table Creation)
register_activation_hook(__FILE__, ['SMLMS_Activator', 'activate']);

// Initialize Plugin
function smlms_init_plugin() {
    $cpt = new SMLMS_CPT();
    $cpt->init();

    $focus_mode = new SMLMS_Focus_Mode();
    
    $rest_api = new SMLMS_REST_API();
    $rest_api->init();

    $dashboard = new SMLMS_Dashboard();
    $dashboard->init();

    $payments = new SMLMS_Payments();
    $payments->init();

    $admin_tabs = new SMLMS_Admin_Tabs();
    $admin_tabs->init();
}
add_action('plugins_loaded', 'smlms_init_plugin');