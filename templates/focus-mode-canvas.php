<?php
if (!is_user_logged_in()) {
    auth_redirect();
}

$current_user = wp_get_current_user();
$topic_id     = get_the_ID();
$lesson_id    = get_post_meta($topic_id, '_smlms_parent_lesson_id', true);
$video_id     = get_post_meta($topic_id, '_smlms_video_id', true);
$materials    = get_post_meta($topic_id, '_smlms_materials', true);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo SMLMS_PLUGIN_URL . 'public/css/smlms-focus-mode.css'; ?>">
</head>
<body class="smlms-focus-mode-active">

<div id="smlms-app" class="smlms-container">
    <!-- Left Navigation Sidebar -->
    <aside class="smlms-sidebar">
        <div class="smlms-sidebar-header">
            <a href="<?php echo site_url('/dashboard'); ?>" class="smlms-back-link">&larr; Dashboard</a>
            <h2><?php echo esc_html(get_the_title($lesson_id)); ?></h2>
        </div>
        <nav class="smlms-tree-nav">
            <!-- Sidebar Navigation populated dynamically via PHP/JS -->
            <?php smlms_render_sidebar_hierarchy($lesson_id, $topic_id); ?>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="smlms-main-content">
        <header class="smlms-top-bar">
            <h1><?php the_title(); ?></h1>
            <button id="smlms-mark-complete-btn" class="button button-primary" disabled>
                Mark Complete
            </button>
        </header>

        <!-- Video Player Frame -->
        <div class="smlms-video-wrapper">
            <div id="smlms-video-container" data-video-id="<?php echo esc_attr($video_id); ?>">
                <!-- Signed HLS Stream / Cloudflare Iframe loaded dynamically -->
            </div>
            <!-- Dynamic Anti-Piracy Watermark -->
            <div class="smlms-watermark">
                <?php echo esc_html($current_user->user_email); ?> | IP: <?php echo $_SERVER['REMOTE_ADDR']; ?>
            </div>
        </div>

        <!-- Material & Content Tabs -->
        <div class="smlms-tabs-container">
            <ul class="smlms-tabs">
                <li class="tab-active" data-tab="content">Overview</li>
                <li data-tab="materials">Materials</li>
            </ul>
            <div id="tab-content" class="tab-pane active">
                <?php the_content(); ?>
            </div>
            <div id="tab-materials" class="tab-pane">
                <?php echo wp_kses_post($materials); ?>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo SMLMS_PLUGIN_URL . 'public/js/smlms-app.js'; ?>"></script>
<?php wp_footer(); ?>
</body>
</html>