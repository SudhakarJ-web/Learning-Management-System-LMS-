<?php
/**
 * Renders Top Navigation Tabs on Admin Edit and List Screens
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Admin_Tabs {

    public function init() {
        add_action('in_admin_header', [$this, 'render_top_navigation_bar']);
    }

    public function render_top_navigation_bar() {
        $screen = get_current_screen();
        if (!$screen) return;

        $lms_cpts = ['smlms_course', 'smlms_lesson', 'smlms_topic'];
        if (!in_array($screen->post_type, $lms_cpts, true)) {
            return;
        }

        $post_type  = $screen->post_type;
        $is_edit    = ($screen->base === 'post');
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : ($post_type === 'smlms_course' ? 'course_page' : ($post_type === 'smlms_lesson' ? 'lesson_page' : 'topic_page'));

        $singular_label = ucfirst(str_replace('smlms_', '', $post_type));
        $plural_label   = $singular_label . 's';
        $back_url       = admin_url('edit.php?post_type=' . $post_type);
        $add_new_url    = admin_url('post-new.php?post_type=' . $post_type);
        ?>

        <div class="smlms-admin-header-bar">
            <div class="smlms-header-left">
                <?php if ($is_edit): ?>
                    <a href="<?php echo esc_url($back_url); ?>" class="smlms-back-button">
                        &larr; Back to <?php echo esc_html($plural_label); ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="smlms-header-tabs">
                <?php if ($is_edit): ?>
                    <?php if ($post_type === 'smlms_course'): ?>
                        <a href="#" class="smlms-tab <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard">Dashboard</a>
                        <a href="#" class="smlms-tab <?php echo $active_tab === 'course_page' ? 'active' : ''; ?>" data-tab="course_page">Course page</a>
                        <a href="#" class="smlms-tab <?php echo $active_tab === 'builder' ? 'active' : ''; ?>" data-tab="builder">Builder</a>
                        <a href="#" class="smlms-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" data-tab="settings">Settings</a>
                    <?php elseif ($post_type === 'smlms_lesson'): ?>
                        <a href="#" class="smlms-tab <?php echo $active_tab === 'lesson_page' ? 'active' : ''; ?>" data-tab="lesson_page">Lesson page</a>
                        <a href="#" class="smlms-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" data-tab="settings">Settings</a>
                    <?php elseif ($post_type === 'smlms_topic'): ?>
                        <a href="#" class="smlms-tab <?php echo $active_tab === 'topic_page' ? 'active' : ''; ?>" data-tab="topic_page">Topic page</a>
                        <a href="#" class="smlms-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" data-tab="settings">Settings</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo esc_url($back_url); ?>" class="smlms-tab active"><?php echo esc_html($plural_label); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smlms_settings')); ?>" class="smlms-tab">Settings</a>
                <?php endif; ?>
            </div>

            <div class="smlms-header-right">
                <a href="<?php echo esc_url($add_new_url); ?>" class="button button-primary smlms-add-new-btn">
                    + Add New <?php echo esc_html($singular_label); ?>
                </a>
            </div>
        </div>
        <?php
    }
}