<?php
/**
 * Standalone Canvas Template for Focus Mode
 */

if (!defined('ABSPATH')) exit;

// Suppress Jetpack Related Posts & Sharing Injections in Focus Mode
add_filter('jetpack_relatedposts_filter_enabled', '__return_false');

$post_id   = get_the_ID();
$user_id   = get_current_user_id();
$post_type = get_post_type($post_id);
$is_lesson = ($post_type === 'smlms_lesson');

// Parent Course Lookup
$course_id = SMLMS_DB::get_parent_course_id($post_id);
$hierarchy = $course_id ? SMLMS_DB::get_course_hierarchy($course_id, $user_id) : [];

// Course Access Mode Lookup
$access_type = $course_id ? (get_post_meta($course_id, '_smlms_access_type', true) ?: 'closed') : 'closed';

// Check Enrollment / Purchaser Status
$is_enrolled  = ($user_id > 0 && $course_id) ? SMLMS_DB::is_user_enrolled($user_id, $course_id) : false;
$is_purchaser = ($user_id > 0) && ($is_enrolled || current_user_can('manage_options'));

// Sample Status Check
$is_sample = false;
if ($is_lesson) {
    $is_sample = (get_post_meta($post_id, '_smlms_is_sample', true) === '1');
} else {
    $parent_lesson_id = get_post_meta($post_id, '_smlms_parent_lesson_id', true);
    if ($parent_lesson_id) {
        $is_sample = (get_post_meta($parent_lesson_id, '_smlms_is_sample', true) === '1');
    }
}

// Access Rules Evaluation
if ($access_type === 'open') {
    $has_access = true;
} elseif ($access_type === 'free') {
    $has_access = ($user_id > 0) && ($is_sample || current_user_can('manage_options') || $is_enrolled);
} else {
    $has_access = $is_sample || current_user_can('manage_options') || $is_enrolled;
}

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

// Linear Sequence Calculation & Hierarchy Indexing
$all_steps            = [];
$valid_step_ids       = [];
$total_steps_count    = 0;
$parent_lesson_url    = '';
$parent_lesson_title  = '';
$parent_l_number      = 1;
$topic_number         = 1;

if (!empty($hierarchy)) {
    foreach ($hierarchy as $l_i => $l_item) {
        $l_id    = $l_item['lesson_id'];
        $l_video = get_post_meta($l_id, '_smlms_video_id', true) ?: get_post_meta($l_id, '_smlms_media_embed', true);
        
        if ($l_id == $post_id) {
            $parent_l_number     = $l_i + 1;
            $parent_lesson_title = $l_item['lesson_title'];
            $parent_lesson_url   = $l_item['permalink'];
        }

        $all_steps[] = [
            'id'    => $l_id,
            'url'   => $l_item['permalink'],
            'type'  => 'lesson',
            'title' => $l_item['lesson_title']
        ];

        if (!empty(trim((string)$l_video))) {
            $total_steps_count++;
            $valid_step_ids[] = $l_id;
        }

        if (!empty($l_item['topics'])) {
            foreach ($l_item['topics'] as $t_i => $t_item) {
                if ($t_item['id'] == $post_id) {
                    $parent_l_number     = $l_i + 1;
                    $topic_number        = $t_i + 1;
                    $parent_lesson_title = $l_item['lesson_title'];
                    $parent_lesson_url   = $l_item['permalink'];
                }

                $all_steps[] = [
                    'id'    => $t_item['id'],
                    'url'   => $t_item['permalink'],
                    'type'  => 'topic',
                    'title' => $t_item['title']
                ];

                $total_steps_count++;
                $valid_step_ids[] = $t_item['id'];
            }
        }
    }
}

// Construct Display Title with Dynamic Prefix
$raw_title = get_the_title();
if ($is_lesson) {
    if (preg_match('/^\d+\.\s*/', $raw_title)) {
        $display_title = $raw_title;
    } else {
        $display_title = $parent_l_number . '. ' . $raw_title;
    }
} else {
    if (preg_match('/^\d+\.\d+\s*/', $raw_title)) {
        $display_title = $raw_title;
    } else {
        $display_title = $parent_l_number . '.' . $topic_number . ' ' . $raw_title;
    }
}

// Navigation Step Targets
$next_step_url   = '';
$next_step_label = '';
$prev_step_url   = '';
$prev_step_label = '';
$current_idx     = -1;

foreach ($all_steps as $idx => $step) {
    if ($step['id'] == $post_id) {
        $current_idx = $idx;
        break;
    }
}

if ($current_idx !== -1) {
    if (isset($all_steps[$current_idx + 1])) {
        $next_step       = $all_steps[$current_idx + 1];
        $next_step_url   = $next_step['url'];
        $next_step_label = ($next_step['type'] === 'topic') ? 'Next Topic' : 'Next Lesson';
    }
    if (isset($all_steps[$current_idx - 1])) {
        $prev_step       = $all_steps[$current_idx - 1];
        $prev_step_url   = $prev_step['url'];
        $prev_step_label = ($prev_step['type'] === 'topic') ? 'Previous Topic' : 'Previous Lesson';
    }
}

// User Step Completion Calculation
$user_completed_ids = ($user_id > 0 && $course_id) ? SMLMS_DB::get_user_completed_steps($user_id, $course_id) : [];

$completed_steps_count = 0;
foreach ($valid_step_ids as $v_step_id) {
    if (in_array($v_step_id, $user_completed_ids)) {
        $completed_steps_count++;
    }
}

$progress_percentage  = ($total_steps_count > 0) ? round(($completed_steps_count / $total_steps_count) * 100) : 0;
$is_current_completed = in_array($post_id, $user_completed_ids);

// Fetch Child Topics for Current Lesson
$lesson_topics = $is_lesson ? SMLMS_DB::get_lesson_topics($post_id) : [];

$post_body_raw = trim(get_post_field('post_content', $post_id));
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url(SMLMS_PLUGIN_URL . 'public/css/smlms-focus-mode.css?v=' . time()); ?>">
</head>
<body <?php body_class('smlms-focus-canvas-body'); ?>>

<div id="smlms-focus-app" class="smlms-focus-app-stage <?php echo is_admin_bar_showing() ? 'has-admin-bar' : ''; ?>">
    
    <!-- Top Bar Navigation Header -->
    <header class="smlms-focus-topbar">
        <div class="smlms-topbar-left">
            <button type="button" id="smlms-mobile-menu-toggle" class="smlms-mobile-menu-btn" title="Open Menu">
                <span class="dashicons dashicons-menu"></span>
            </button>

            <a href="<?php echo esc_url(home_url('/')); ?>" class="smlms-topbar-brand">
                <?php bloginfo('name'); ?>
            </a>
        </div>

        <!-- Centered Progress Controls -->
        <?php if ($is_purchaser): ?>
            <div class="smlms-topbar-center">
                <div class="smlms-progress-widget">
                    <span class="smlms-progress-label" id="smlms-progress-label"><?php echo esc_html($progress_percentage); ?>% COMPLETE</span>
                    <div class="smlms-progress-track">
                        <div class="smlms-progress-fill" id="smlms-progress-fill" style="width: <?php echo esc_attr($progress_percentage); ?>%;"></div>
                    </div>
                    <span class="smlms-progress-count" id="smlms-progress-count"><?php echo esc_html($completed_steps_count . '/' . $total_steps_count); ?> Steps</span>
                </div>

                <button type="button" class="smlms-btn-mark-complete-toggle <?php echo $is_current_completed ? 'completed' : ''; ?>" data-post-id="<?php echo esc_attr($post_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
                    <span class="smlms-btn-text"><?php echo $is_current_completed ? 'Mark Incomplete' : 'Mark Complete'; ?></span>
                    <span class="dashicons <?php echo $is_current_completed ? 'dashicons-yes-alt' : 'dashicons-yes'; ?>"></span>
                </button>
            </div>
        <?php else: ?>
            <div class="smlms-topbar-center"></div>
        <?php endif; ?>

        <!-- Right Header User Greeting -->
        <div class="smlms-topbar-right">
            <?php if ($user_id): $user = wp_get_current_user(); ?>
                <span class="smlms-user-greeting smlms-desktop-only">Hello, <strong><?php echo esc_html($user->display_name); ?></strong>!</span>
                <img src="<?php echo esc_url(get_avatar_url($user_id, ['size' => 32])); ?>" class="smlms-user-avatar" alt="Avatar">
            <?php else: ?>
                <span class="smlms-user-greeting smlms-desktop-only">Hello, <strong>Visitor</strong>!</span>
            <?php endif; ?>
        </div>
    </header>

    <div class="smlms-focus-stage-split">
        
        <!-- Mobile Sidebar Backdrop Overlay -->
        <div id="smlms-sidebar-backdrop" class="smlms-sidebar-backdrop"></div>

        <!-- Floating Expand Trigger Tab (Desktop) -->
        <button type="button" id="smlms-sidebar-expand-btn" class="smlms-sidebar-expand-tab" title="Open Sidebar">
            <span class="smlms-expand-btn-inner">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </span>
        </button>

        <!-- Left Sidebar Navigation Drawer -->
        <aside class="smlms-focus-sidebar" id="smlms-focus-sidebar">
            <?php if ($course_id): ?>
                <div class="smlms-sidebar-header-box">
                    <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-sidebar-header-title-wrap" title="<?php echo esc_attr(get_the_title($course_id)); ?>">
                        <span class="dashicons dashicons-welcome-learn-more smlms-sidebar-header-icon"></span>
                        <span class="smlms-sidebar-course-name"><?php echo esc_html(get_the_title($course_id)); ?></span>
                    </a>
                    <button type="button" id="smlms-sidebar-collapse-btn" class="smlms-sidebar-collapse-btn" title="Hide Sidebar">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="smlms-sidebar-content-tree">
                <?php include SMLMS_PLUGIN_DIR . 'templates/parts/sidebar-tree.php'; ?>
            </div>
        </aside>

        <!-- Main Content Stage -->
        <main class="smlms-focus-main-stage">
            <div class="smlms-focus-stage-inner">
                
                <h1 class="smlms-step-main-title"><?php echo esc_html($display_title); ?></h1>

                <?php if (!$has_access): ?>
                    <div class="smlms-access-restricted-box">
                        <span class="dashicons dashicons-lock"></span>
                        <h2>Access Restricted</h2>
                        <p><?php echo ($access_type === 'free') ? 'Please log in and enroll to view this free course.' : 'You must be enrolled in this course to view this step.'; ?></p>
                        <?php if ($course_id): ?>
                            <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-btn-enroll-link">View Course Page</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>

                    <!-- Full Hierarchy Breadcrumb Banner -->
                    <div class="smlms-focus-breadcrumb-banner">
                        <div class="smlms-breadcrumb-links">
                            <?php if ($course_id): ?>
                                <a href="<?php echo esc_url(get_permalink($course_id)); ?>"><?php echo esc_html(get_the_title($course_id)); ?></a> &gt; 
                            <?php endif; ?>

                            <?php if (!$is_lesson && !empty($parent_lesson_title)): ?>
                                <a href="<?php echo esc_url($parent_lesson_url); ?>">
                                    <?php 
                                    $parent_l_display = preg_match('/^\d+\.\s*/', $parent_lesson_title) ? $parent_lesson_title : ($parent_l_number . '. ' . $parent_lesson_title);
                                    echo esc_html($parent_l_display); 
                                    ?>
                                </a> &gt; 
                            <?php endif; ?>

                            <span><?php echo esc_html($display_title); ?></span>
                        </div>

                        <span class="smlms-status-pill <?php echo $is_current_completed ? 'completed' : 'in-progress'; ?>">
                            <?php echo $is_current_completed ? 'COMPLETED' : 'IN PROGRESS'; ?>
                        </span>
                    </div>

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
                            <?php 
                            ob_start();
                            while (have_posts()) : the_post();
                                the_content();
                            endwhile;
                            $content_output = ob_get_clean();

                            $content_output = preg_replace('/<div[^>]*id="jp-relatedposts"[^>]*>.*?<\/div>/s', '', $content_output);
                            $content_output = preg_replace('/<div[^>]*class="[^"]*jp-relatedposts[^"]*"[^>]*>.*?<\/div>/s', '', $content_output);
                            $content_output = preg_replace('/<p>\s*(?:&nbsp;|\s)*<\/p>/i', '', $content_output);
                            $content_output = trim($content_output);

                            if (!empty($content_output)): ?>
                                <div class="smlms-focus-body-content">
                                    <?php echo $content_output; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Dedicated Child Topics List Card -->
                            <?php if ($is_lesson && !empty($lesson_topics)): ?>
                                <div class="smlms-lesson-topics-card">
                                    <ul class="smlms-lesson-topics-group">
                                        <?php foreach ($lesson_topics as $t_index => $t_obj): 
                                            $t_id       = $t_obj->ID;
                                            $t_title    = $t_obj->post_title;
                                            $t_url      = get_permalink($t_id);
                                            $t_duration = get_post_meta($t_id, '_smlms_duration', true) ?: '5.00';
                                            $t_type     = strtolower(trim((string) get_post_meta($t_id, '_smlms_content_type', true)));
                                            $t_icon     = ($t_type === 'presentation') ? 'dashicons-media-interactive' : 'dashicons-controls-play';
                                        ?>
                                            <li class="smlms-lesson-topic-row">
                                                <div class="smlms-lesson-topic-left">
                                                    <span class="smlms-topic-play-icon" title="<?php echo esc_attr(ucfirst($t_type)); ?>">
                                                        <span class="dashicons <?php echo esc_attr($t_icon); ?>"></span>
                                                    </span>
                                                    <a href="<?php echo esc_url($t_url); ?>" class="smlms-lesson-topic-link">
                                                        <?php echo $parent_l_number . '.' . ($t_index + 1) . ' ' . esc_html($t_title); ?>
                                                    </a>
                                                </div>

                                                <div class="smlms-lesson-topic-right">
                                                    <span class="smlms-topic-duration"><?php echo esc_html($t_duration); ?></span>
                                                    <span class="smlms-status-circle <?php echo in_array($t_id, $user_completed_ids) ? 'active-circle' : ''; ?>"></span>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

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

                    <!-- Horizontal Footer Navigation Action Row -->
                    <div class="smlms-focus-footer-nav-row">
                        <div class="smlms-footer-col left">
                            <?php if (!empty($prev_step_url)): ?>
                                <a href="<?php echo esc_url($prev_step_url); ?>" class="smlms-btn-pill-cyan">
                                    &lt; <?php echo esc_html($prev_step_label); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="smlms-footer-col center">
                            <?php if (!$is_lesson && !empty($parent_lesson_url)): ?>
                                <a href="<?php echo esc_url($parent_lesson_url); ?>" class="smlms-center-nav-link">Back to Lesson</a>
                            <?php elseif ($course_id): ?>
                                <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-center-nav-link">Back to Course</a>
                            <?php endif; ?>
                        </div>

                        <div class="smlms-footer-col right">
                            <?php if (!empty($next_step_url)): ?>
                                <a href="<?php echo esc_url($next_step_url); ?>" class="smlms-btn-pill-cyan">
                                    <?php echo esc_html($next_step_label); ?> &gt;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </main>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const focusApp = $('#smlms-focus-app');
    const ajaxUrl  = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    const nonce    = '<?php echo wp_create_nonce('smlms_progress_nonce'); ?>';

    // Sidebar State Restoration
    if ($(window).width() > 768) {
        if (localStorage.getItem('smlms_sidebar_collapsed') === 'true') {
            focusApp.addClass('sidebar-collapsed');
        } else {
            focusApp.removeClass('sidebar-collapsed');
        }
    }

    // Toggle Sidebar Drawer (Mobile & Desktop)
    $(document).on('click', '#smlms-mobile-menu-toggle, #smlms-sidebar-expand-btn', function(e) {
        e.preventDefault();
        focusApp.removeClass('sidebar-collapsed').addClass('drawer-open');
        if ($(window).width() > 768) {
            localStorage.setItem('smlms_sidebar_collapsed', 'false');
        }
    });

    $(document).on('click', '#smlms-sidebar-collapse-btn, #smlms-sidebar-backdrop', function(e) {
        e.preventDefault();
        focusApp.removeClass('drawer-open');
        if ($(window).width() > 768) {
            focusApp.addClass('sidebar-collapsed');
            localStorage.setItem('smlms_sidebar_collapsed', 'true');
        }
    });

    // Focus Mode Tab Switcher
    $(document).on('click', '.smlms-focus-tab-link', function(e) {
        e.preventDefault();
        $('.smlms-focus-tab-link').removeClass('active');
        $('.smlms-focus-tab-pane').removeClass('active');

        $(this).addClass('active');
        $($(this).data('target')).addClass('active');
    });

    // Sidebar Accordion Drawer Toggle
    $(document).on('click', '.smlms-sidebar-toggle-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var targetList = $(this).closest('.smlms-sidebar-lesson-item').find('.smlms-sidebar-topic-sublist');
        targetList.slideToggle(150);
        $(this).toggleClass('open');
    });

    // Interactive Mark Complete AJAX Handler
    $(document).on('click', '.smlms-btn-mark-complete-toggle', function(e) {
        e.preventDefault();
        var btn = $(this);
        var postId   = btn.data('post-id');
        var courseId = btn.data('course-id');

        btn.prop('disabled', true);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'smlms_toggle_step_complete',
                post_id: postId,
                course_id: courseId,
                _wpnonce: nonce
            },
            success: function(response) {
                btn.prop('disabled', false);
                if (response.success) {
                    var data = response.data;
                    
                    // Update Progress Widgets
                    $('#smlms-progress-label').text(data.percentage + '% COMPLETE');
                    $('#smlms-progress-fill').css('width', data.percentage + '%');
                    $('#smlms-progress-count').text(data.completed_count + '/' + data.total_count + ' Steps');

                    // Synchronize Mark Complete buttons & status pill badge
                    if (data.is_completed) {
                        $('.smlms-btn-mark-complete-toggle')
                            .addClass('completed')
                            .find('.smlms-btn-text').text('Mark Incomplete');
                        $('.smlms-btn-mark-complete-toggle')
                            .find('.dashicons').attr('class', 'dashicons dashicons-yes-alt');
                        
                        $('.smlms-status-pill')
                            .removeClass('in-progress')
                            .addClass('completed')
                            .text('COMPLETED');
                    } else {
                        $('.smlms-btn-mark-complete-toggle')
                            .removeClass('completed')
                            .find('.smlms-btn-text').text('Mark Complete');
                        $('.smlms-btn-mark-complete-toggle')
                            .find('.dashicons').attr('class', 'dashicons dashicons-yes');

                        $('.smlms-status-pill')
                            .removeClass('completed')
                            .addClass('in-progress')
                            .text('IN PROGRESS');
                    }
                }
            },
            error: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Downloadable Links Handler
    $('.smlms-materials-body-box a').each(function() {
        var href = $(this).attr('href');
        if (href) {
            $(this).attr('target', '_blank');
            if (href.match(/\.(pdf|doc|docx|zip|rar|png|jpg|jpeg|csv|xls|xlsx)$/i)) {
                $(this).attr('download', '');
            }
        }
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>