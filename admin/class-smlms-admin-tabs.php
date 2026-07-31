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

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'course_page';
        $post_type  = $screen->post_type;
        $is_edit    = ($screen->base === 'post');
        ?>

        <div class="smlms-admin-header-bar">
            <div class="smlms-header-left">
                <?php if ($is_edit): ?>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . $post_type)); ?>" class="smlms-back-button">
                        &lt; Back to <?php echo ucfirst(str_replace('smlms_', '', $post_type)) . 's'; ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="smlms-header-tabs">
                <?php if ($is_edit): ?>
                    <!-- Single Edit Screen Tabs -->
                    <a href="?post=<?php echo get_the_ID(); ?>&action=edit&tab=dashboard" class="smlms-tab <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                    <a href="?post=<?php echo get_the_ID(); ?>&action=edit&tab=course_page" class="smlms-tab <?php echo $active_tab === 'course_page' ? 'active' : ''; ?>">Course page</a>
                    <a href="?post=<?php echo get_the_ID(); ?>&action=edit&tab=builder" class="smlms-tab <?php echo $active_tab === 'builder' ? 'active' : ''; ?>">Builder</a>
                    <a href="?post=<?php echo get_the_ID(); ?>&action=edit&tab=access" class="smlms-tab <?php echo $active_tab === 'access' ? 'active' : ''; ?>">Extend Access</a>
                    <a href="?post=<?php echo get_the_ID(); ?>&action=edit&tab=settings" class="smlms-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">Settings</a>
                <?php else: ?>
                    <!-- List Table Screen Tabs -->
                    <a href="<?php echo admin_url('edit.php?post_type=' . $post_type); ?>" class="smlms-tab active">
                        <?php echo ucfirst(str_replace('smlms_', '', $post_type)) . 's'; ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=smlms_settings'); ?>" class="smlms-tab">Settings</a>
                <?php endif; ?>
            </div>

            <div class="smlms-header-right">
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . $post_type)); ?>" class="button button-primary smlms-add-new-btn">
                    + Add New <?php echo ucfirst(str_replace('smlms_', '', $post_type)); ?>
                </a>
            </div>
        </div>
        <?php
    }
}