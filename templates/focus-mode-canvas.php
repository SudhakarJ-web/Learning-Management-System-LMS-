<?php
/**
 * Focus Mode Master Canvas - Course Name Enforcement
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    auth_redirect();
}

$current_user  = wp_get_current_user();
$current_id    = get_the_ID();
$course_id     = SMLMS_DB::get_parent_course_id($current_id);
$course_post   = get_post($course_id);
$course_title  = $course_post ? $course_post->post_title : get_the_title($current_id);

$avatar_url    = get_avatar_url($current_user->ID, ['size' => 96]);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>

    <style id="smlms-embedded-canvas-css">
        html, body.smlms-focus-mode-active {
            margin: 0 !important;
            padding: 0 !important;
            height: 100vh !important;
            width: 100vw !important;
            overflow: hidden !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            background-color: #ffffff !important;
            color: #2d3748 !important;
        }

        .smlms-focus-wrapper, .smlms-focus-container {
            display: flex !important;
            width: 100vw !important;
            height: 100vh !important;
            overflow: hidden !important;
        }

        .smlms-focus-sidebar {
            width: 380px !important;
            min-width: 380px !important;
            background-color: #f8fafc !important;
            border-right: 1px solid #cbd5e1 !important;
            display: flex !important;
            flex-direction: column !important;
            height: 100vh !important;
            box-sizing: border-box !important;
            transition: margin-left 0.3s ease !important;
        }

        .smlms-focus-sidebar.collapsed {
            margin-left: -380px !important;
        }

        .smlms-sidebar-course-header {
            background-color: #00a2e8 !important;
            color: #ffffff !important;
            padding: 16px 20px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
            box-sizing: border-box !important;
        }

        .smlms-course-header-left {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            overflow: hidden !important;
        }

        .smlms-header-icon {
            color: #ffffff !important;
            font-size: 18px !important;
        }

        .smlms-course-header-title {
            color: #ffffff !important;
            text-decoration: none !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            line-height: 1.3 !important;
        }

        .smlms-sidebar-toggle-btn {
            background: rgba(255, 255, 255, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            color: #ffffff !important;
            width: 28px !important;
            height: 28px !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            font-weight: bold !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .smlms-sidebar-content-scroll {
            overflow-y: auto !important;
            flex-grow: 1 !important;
            padding: 15px !important;
            background-color: #f8fafc !important;
        }

        .smlms-focus-main {
            flex-grow: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            height: 100vh !important;
            overflow: hidden !important;
            background: #ffffff !important;
        }

        .smlms-focus-header {
            height: 65px !important;
            min-height: 65px !important;
            background: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 30px !important;
            box-sizing: border-box !important;
        }

        .smlms-header-progress {
            width: 240px !important;
        }

        .smlms-progress-stats {
            display: flex !important;
            justify-content: space-between !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            color: #64748b !important;
            margin-bottom: 6px !important;
        }

        .smlms-progress-percent {
            color: #019e7c !important;
        }

        .smlms-progress-bar-bg {
            height: 8px !important;
            background: #e2e8f0 !important;
            border-radius: 4px !important;
            overflow: hidden !important;
        }

        .smlms-progress-bar-fill {
            height: 100% !important;
            background: #019e7c !important;
            transition: width 0.3s ease !important;
        }

        .smlms-header-actions {
            display: flex !important;
            align-items: center !important;
            gap: 15px !important;
        }

        .smlms-btn-action {
            padding: 8px 18px !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            cursor: pointer !important;
            border: none !important;
        }

        .smlms-btn-complete {
            background: #22c55e !important;
            color: #ffffff !important;
        }

        .smlms-btn-complete:disabled {
            background: #cbd5e1 !important;
            cursor: not-allowed !important;
        }

        .smlms-user-welcome {
            font-size: 13px !important;
            color: #475569 !important;
        }

        .smlms-user-avatar {
            width: 36px !important;
            height: 36px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
        }

        .smlms-focus-stage {
            flex-grow: 1 !important;
            overflow-y: auto !important;
            padding: 30px 50px !important;
            box-sizing: border-box !important;
        }
    </style>
</head>
<body class="smlms-focus-mode-active">

<div class="smlms-focus-wrapper">
    <div class="smlms-focus-container" id="smlms-focus-container">
        
        <!-- Left Sidebar Navigation -->
        <aside class="smlms-focus-sidebar" id="smlms-focus-sidebar">
            <div class="smlms-sidebar-course-header">
                <div class="smlms-course-header-left">
                    <span class="dashicons dashicons-content smlms-header-icon"></span>
                    <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-course-header-title">
                        <?php echo esc_html($course_title); ?>
                    </a>
                </div>
                <button type="button" class="smlms-sidebar-toggle-btn" id="smlms-sidebar-toggle" title="Collapse Sidebar">&lt;</button>
            </div>

            <div class="smlms-sidebar-content-scroll">
                <?php 
                if (file_exists(SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php')) {
                    include SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php'; 
                }
                ?>
            </div>
        </aside>

        <!-- Main Focus Stage Area -->
        <div class="smlms-focus-main">
            <header class="smlms-focus-header">
                <div class="smlms-header-brand">
                    <strong><?php echo esc_html(get_bloginfo('name')); ?></strong>
                </div>

                <div class="smlms-header-progress">
                    <div class="smlms-progress-stats">
                        <span class="smlms-progress-percent" id="smlms-progress-percent-text">0% COMPLETE</span>
                        <span class="smlms-progress-steps" id="smlms-progress-steps-text">0/0 Steps</span>
                    </div>
                    <div class="smlms-progress-bar-bg">
                        <div class="smlms-progress-bar-fill" id="smlms-progress-fill" style="width: 0%;"></div>
                    </div>
                </div>

                <div class="smlms-header-actions">
                    <button type="button" class="smlms-btn-action smlms-btn-complete" id="smlms-mark-complete-btn" disabled>Mark Complete</button>
                    <span class="smlms-user-welcome">Hello, <strong><?php echo esc_html($current_user->display_name); ?></strong>!</span>
                    <img src="<?php echo esc_url($avatar_url); ?>" class="smlms-user-avatar" alt="Avatar">
                </div>
            </header>

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