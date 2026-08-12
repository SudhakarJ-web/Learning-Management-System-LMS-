<?php
/**
 * Standalone Theme-Proof Canvas Template for Focus Mode
 */

if (!defined('ABSPATH')) exit;

$post_id   = get_the_ID();
$user_id   = get_current_user_id();
$post_type = get_post_type($post_id);
$is_lesson = ($post_type === 'smlms_lesson');

// Parent Course Lookup
$course_id = SMLMS_DB::get_parent_course_id($post_id);
$hierarchy = $course_id ? SMLMS_DB::get_course_hierarchy($course_id, $user_id) : [];

// Access Permissions with Inherited Sample Check
$is_sample = false;
if ($is_lesson) {
    $is_sample = (get_post_meta($post_id, '_smlms_is_sample', true) === '1');
} else {
    // Topic: Check parent lesson's sample status
    $parent_lesson_id = get_post_meta($post_id, '_smlms_parent_lesson_id', true);
    if ($parent_lesson_id) {
        $is_sample = (get_post_meta($parent_lesson_id, '_smlms_is_sample', true) === '1');
    }
}

$has_access = current_user_can('manage_options') || ($course_id && SMLMS_DB::is_user_enrolled($user_id, $course_id)) || $is_sample;

// Check Materials Availability
$materials_enabled = get_post_meta($post_id, '_smlms_materials_enabled', true) ?: '0';
$raw_materials     = get_post_meta($post_id, '_smlms_materials', true);
$has_materials     = ($materials_enabled === '1') && !empty(trim(wp_strip_all_tags($raw_materials)));

// Video URL Fallback Parser
$video_url = get_post_meta($post_id, '_smlms_video_id', true);
if (empty($video_url)) {
    $video_url = get_post_meta($post_id, '_smlms_media_embed', true);
}

$embed_src = '';
if (!empty($video_url)) {
    $video_url = trim($video_url);
    if (strpos($video_url, '<iframe') !== false) {
        preg_match('/src=["\']([^"\']+)["\']/', $video_url, $matches);
        if (!empty($matches[1])) $embed_src = $matches[1];
    } elseif (is_numeric($video_url)) {
        $embed_src = 'https://player.vimeo.com/video/' . esc_attr($video_url);
    } elseif (strpos($video_url, 'vimeo.com') !== false) {
        preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $video_url, $matches);
        $embed_src = !empty($matches[1]) ? 'https://player.vimeo.com/video/' . $matches[1] : esc_url($video_url);
    } elseif (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
        preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/', $video_url, $matches);
        $embed_src = !empty($matches[1]) ? 'https://www.youtube.com/embed/' . $matches[1] : esc_url($video_url);
    } else {
        $embed_src = esc_url($video_url);
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('smlms-focus-canvas-body'); ?>>

<div id="smlms-focus-app" class="smlms-focus-app-stage <?php echo is_admin_bar_showing() ? 'has-admin-bar' : ''; ?>">
    
    <!-- Top Bar Navigation Header -->
    <header class="smlms-focus-topbar">
        <div class="smlms-topbar-left">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="smlms-topbar-brand">
                <?php bloginfo('name'); ?>
            </a>
        </div>

        <div class="smlms-topbar-center">
            <div class="smlms-progress-widget">
                <span class="smlms-progress-label">0% COMPLETE</span>
                <div class="smlms-progress-track">
                    <div class="smlms-progress-fill" style="width: 0%;"></div>
                </div>
                <span class="smlms-progress-count">0/2 Steps</span>
            </div>
            <button type="button" class="smlms-btn-header-complete">Mark Complete</button>
        </div>

        <div class="smlms-topbar-right">
            <?php if ($user_id): $user = wp_get_current_user(); ?>
                <span class="smlms-user-greeting">Hello, <strong><?php echo esc_html($user->display_name); ?></strong>!</span>
                <img src="<?php echo esc_url(get_avatar_url($user_id, ['size' => 32])); ?>" class="smlms-user-avatar" alt="Avatar">
            <?php else: ?>
                <span class="smlms-user-greeting">Hello, <strong>Visitor</strong>!</span>
            <?php endif; ?>
        </div>
    </header>

    <div class="smlms-focus-stage-split">
        
        <!-- Left Sidebar Navigation Tree -->
        <aside class="smlms-focus-sidebar">
            <?php if ($course_id): ?>
                <div class="smlms-sidebar-header-box">
                    <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-sidebar-course-link">
                        <span><?php echo esc_html(get_the_title($course_id)); ?></span>
                        <span class="smlms-back-arrow">&lt;</span>
                    </a>
                </div>
            <?php endif; ?>

            <div class="smlms-sidebar-content-tree">
                <?php include SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php'; ?>
            </div>
        </aside>

        <!-- Main Content Stage -->
        <main class="smlms-focus-main-stage">
            <div class="smlms-focus-stage-inner">
                
                <div class="smlms-breadcrumbs-header-row">
                    <nav class="smlms-breadcrumbs">
                        <?php if ($course_id): ?>
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>"><?php echo esc_html(get_the_title($course_id)); ?></a> &gt; 
                        <?php endif; ?>
                        <span><?php the_title(); ?></span>
                    </nav>
                    <span class="smlms-status-badge">IN PROGRESS</span>
                </div>

                <h1 class="smlms-step-main-title"><?php the_title(); ?></h1>

                <?php if (!$has_access): ?>
                    <div class="smlms-access-restricted-box">
                        <span class="dashicons dashicons-lock"></span>
                        <h2>Access Restricted</h2>
                        <p>You must be enrolled in this course to view this step.</p>
                        <?php if ($course_id): ?>
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-btn-enroll-link">View Course Page</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>

                    <!-- Tabs Bar: ONLY Rendered if Materials Exist -->
                    <?php if ($has_materials): ?>
                        <div class="smlms-focus-tabs-nav">
                            <button type="button" class="smlms-focus-tab-link active" data-target="#smlms-tab-step-content">
                                <span class="dashicons dashicons-welcome-learn-more"></span> <?php echo $is_lesson ? 'Lesson' : 'Topic'; ?>
                            </button>
                            <button type="button" class="smlms-focus-tab-link" data-target="#smlms-tab-materials-content">
                                <span class="dashicons dashicons-paperclip"></span> Materials
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="smlms-focus-tab-panes">
                        <div id="smlms-tab-step-content" class="smlms-focus-tab-pane active">
                            
                            <!-- Embedded Video Player -->
                            <?php if (!empty($embed_src)): ?>
                                <div class="smlms-focus-video-wrap">
                                    <iframe src="<?php echo esc_url($embed_src); ?>" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>

                            <!-- Entry Content -->
                            <div class="smlms-focus-body-content">
                                <?php 
                                while (have_posts()) : the_post();
                                    the_content();
                                endwhile;
                                ?>
                            </div>

                            <!-- Topic Sub-list Inside Lessons -->
                            <?php if ($is_lesson): 
                                $topics = SMLMS_DB::get_lesson_topics($post_id);
                                if (!empty($topics)):
                            ?>
                                <div class="smlms-lesson-topics-box">
                                    <h3>Topics in this Lesson</h3>
                                    <ul class="smlms-topics-list">
                                        <?php foreach ($topics as $topic): ?>
                                            <li>
                                                <a href="<?php echo esc_url(get_permalink($topic->ID)); ?>">
                                                    <div class="smlms-topic-left">
                                                        <span class="dashicons dashicons-controls-play"></span>
                                                        <span><?php echo esc_html($topic->post_title); ?></span>
                                                    </div>
                                                    <span class="smlms-topic-duration"><?php echo esc_html(get_post_meta($topic->ID, '_smlms_duration', true) ?: '5.04'); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; endif; ?>

                        </div>

                        <!-- Materials Tab Pane -->
                        <?php if ($has_materials): ?>
                            <div id="smlms-tab-materials-content" class="smlms-focus-tab-pane">
                                <div class="smlms-materials-body-box">
                                    <?php echo wpautop(wp_kses_post($raw_materials)); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Footer Action Row -->
                    <div class="smlms-focus-footer-row">
                        <?php if ($course_id): ?>
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-back-course-link">&larr; Back to Course</a>
                        <?php endif; ?>

                        <button type="button" class="smlms-btn-mark-complete">
                            Mark Complete &#10003;
                        </button>
                    </div>

                <?php endif; ?>

            </div>
        </main>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $(document).on('click', '.smlms-focus-tab-link', function(e) {
        e.preventDefault();
        $('.smlms-focus-tab-link').removeClass('active');
        $('.smlms-focus-tab-pane').removeClass('active');

        $(this).addClass('active');
        $($(this).data('target')).addClass('active');
    });

    $(document).on('click', '.smlms-sidebar-toggle-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var targetList = $(this).closest('.smlms-tree-lesson-card').find('.smlms-tree-topic-list');
        targetList.slideToggle(150);
        $(this).toggleClass('open');
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>