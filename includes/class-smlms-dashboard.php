<?php
if (!defined('ABSPATH')) exit;

class SMLMS_Dashboard {

    public function init() {
        add_shortcode('smlms_dashboard', [$this, 'render_dashboard_shortcode']);
    }

    public function render_dashboard_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url() . '">log in</a> to view your courses.</p>';
        }

        $user_id = get_current_user_id();
        
        // Execute the direct SQL query function
        $enrolled_courses = SMLMS_DB::smlms_get_user_dashboard_fast($user_id);

        ob_start();
        include SMLMS_PLUGIN_DIR . 'templates/user-dashboard.php';
        return ob_get_clean();
    }
}