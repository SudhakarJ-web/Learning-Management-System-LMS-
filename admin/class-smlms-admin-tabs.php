<?php
/**
 * Admin Course Edit Screen Navigation Tabs
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Admin_Tabs {

    public static function init() {
        add_action('edit_form_after_title', [__CLASS__, 'render_tabs']);
    }

    public static function render_tabs($post) {
        if ($post->post_type !== 'smlms_course') {
            return;
        }
        ?>
        <div class="smlms-admin-tabs-bar">
            <button type="button" class="smlms-admin-tab-btn active" data-tab="course-page">
                <span class="dashicons dashicons-welcome-learn-more"></span> Course Page
            </button>
            <button type="button" class="smlms-admin-tab-btn" data-tab="builder">
                <span class="dashicons dashicons-networking"></span> Builder
            </button>
            <button type="button" class="smlms-admin-tab-btn" data-tab="settings">
                <span class="dashicons dashicons-admin-generic"></span> Settings
            </button>
        </div>
        <?php
    }
}
SMLMS_Admin_Tabs::init();