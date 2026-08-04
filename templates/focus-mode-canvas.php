<?php
/**
 * Focus Mode Canvas Template - LearnDash Layout Replica
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    auth_redirect();
}

$current_user     = wp_get_current_user();
$topic_id         = get_the_ID();
$lesson_id        = get_post_meta($topic_id, '_smlms_parent_lesson_id', true);
$course_id        = get_post_meta($lesson_id, '_smlms_parent_course_id', true);
$video_id         = get_post_meta($topic_id, '_smlms_video_id', true);
$materials        = get_post_meta($topic_id, '_smlms_materials', true);
$current_topic_id = $topic_id;
$avatar_url       = get_avatar_url($current_user->ID, ['size' => 96]);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="smlms-focus-mode-active">

<div class="smlms-focus-wrapper">
    <div class="smlms-focus-container" id="smlms-focus-container">
        
        <!-- Left Sidebar Navigation -->
        <aside class="smlms-focus-sidebar" id="smlms-focus-sidebar">
            <div class="smlms-sidebar-heading">
                <button type="button" class="smlms-sidebar-toggle-btn" id="smlms-sidebar-toggle" title="Toggle Sidebar">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-course-link">
                    <span class="dashicons dashicons-content"></span>
                    <span><?php echo esc_html(get_the_title($course_id ? $course_id : $lesson_id)); ?></span>
                </a>
            </div>

            <div class="smlms-sidebar-content-scroll">
                <?php 
                if (file_exists(SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php')) {
                    include SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php'; 
                }
                ?>
            </div>
        </aside>

        <!-- Main Focus Stage -->
        <div class="smlms-focus-main">
            
            <!-- Top Header Bar -->
            <header class="smlms-focus-header">
                <div class="smlms-header-left">
                    <button type="button" class="smlms-mobile-trigger" id="smlms-mobile-menu-trigger">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="smlms-brand-logo">
                        <a href="<?php echo esc_url(home_url('/')); ?>">
                            <?php if (has_custom_logo()): ?>
                                <?php the_custom_logo(); ?>
                            <?php else: ?>
                                <strong>Sabin Mathew LMS</strong>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="smlms-header-progress">
                    <div class="smlms-progress-stats">
                        <span class="smlms-progress-percent" id="smlms-progress-percent-text">0% Complete</span>
                        <span class="smlms-progress-steps" id="smlms-progress-steps-text">0/0 Steps</span>
                    </div>
                    <div class="smlms-progress-bar-bg">
                        <div class="smlms-progress-bar-fill" id="smlms-progress-fill" style="width: 0%;"></div>
                    </div>
                </div>

                <!-- Top Header Actions & User Menu -->
                <div class="smlms-header-actions">
                    <button type="button" class="smlms-btn-action smlms-btn-prev" id="smlms-top-prev-btn">&larr; Previous</button>
                    <button type="button" class="smlms-btn-action smlms-btn-complete" id="smlms-mark-complete-btn" disabled>Mark Complete</button>

                    <div class="smlms-user-profile-menu">
                        <span class="smlms-user-welcome">Hello, <strong><?php echo esc_html($current_user->display_name); ?></strong>!</span>
                        <img src="<?php echo esc_url($avatar_url); ?>" class="smlms-user-avatar" alt="Avatar">
                        <div class="smlms-user-dropdown">
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>">Course Home</a>
                            <a href="<?php echo esc_url(wp_logout_url(get_permalink($course_id))); ?>">Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Topic Main Stage Content -->
            <div class="smlms-focus-stage">
                <?php 
                if (file_exists(SMLMS_PLUGIN_DIR . 'templates/parts/topic-content.php')) {
                    include SMLMS_PLUGIN_DIR . 'templates/parts/topic-content.php'; 
                }
                ?>
            </div>

        </div>

    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>