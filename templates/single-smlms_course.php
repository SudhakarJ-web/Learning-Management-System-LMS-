<?php
/**
 * Single Course Landing Page Template - Sample Lesson Access
 */

if (!defined('ABSPATH')) {
    exit;
}

wp_enqueue_style('dashicons');

get_header();

$course_id     = get_the_ID();
$user_id       = get_current_user_id();
$author_id     = get_post_field('post_author', $course_id);
$author_name   = get_the_author_meta('display_name', $author_id);
$author_url    = get_author_posts_url($author_id);
$author_avatar = get_avatar_url($author_id, ['size' => 120]);
$author_bio    = get_the_author_meta('description', $author_id);

// Custom Metadata
$duration     = get_post_meta($course_id, '_smlms_duration', true) ?: '4 Weeks';
$level        = get_post_meta($course_id, '_smlms_level', true) ?: 'Beginner';
$language     = get_post_meta($course_id, '_smlms_language', true) ?: 'English';
$enrolled     = get_post_meta($course_id, '_smlms_students_enrolled', true) ?: '17';
$media_embed  = get_post_meta($course_id, '_smlms_media_embed', true);

// Enrollment & Access Control Settings
$price_type   = get_post_meta($course_id, '_smlms_price_type', true) ?: 'closed';
$price        = get_post_meta($course_id, '_smlms_price', true);
$raw_btn_url  = get_post_meta($course_id, '_smlms_button_url', true);

// Sanitize Double Protocol URLs
$button_url = preg_replace('#^https?://(https?://)+#i', 'https://', trim($raw_btn_url));
if (empty($button_url)) {
    $button_url = '#';
}

// Check Enrollment Access
$is_enrolled  = $user_id ? SMLMS_DB::is_user_enrolled($user_id, $course_id) : false;
$is_admin     = current_user_can('manage_options');
$has_access   = $is_enrolled || $is_admin || ($price_type === 'open');

// Parse Embed URL for Pop-up Modal
$embed_url = '';
if (!empty($media_embed)) {
    if (is_numeric($media_embed)) {
        $embed_url = 'https://player.vimeo.com/video/' . esc_attr($media_embed) . '?autoplay=1';
    } elseif (strpos($media_embed, 'vimeo.com') !== false) {
        preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $media_embed, $matches);
        if (!empty($matches[1])) {
            $embed_url = 'https://player.vimeo.com/video/' . $matches[1] . '?autoplay=1';
        }
    } elseif (strpos($media_embed, 'youtube.com') !== false || strpos($media_embed, 'youtu.be') !== false) {
        preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $media_embed, $matches);
        if (!empty($matches[1])) {
            $embed_url = 'https://www.youtube.com/embed/' . $matches[1] . '?autoplay=1';
        }
    } else {
        $embed_url = esc_url($media_embed);
    }
}

// Course Hierarchy
$hierarchy = SMLMS_DB::get_course_hierarchy($course_id, $user_id);

// Determine First Step Link
$first_step_url = '#';
if (!empty($hierarchy[0]['topics'][0]['permalink'])) {
    $first_step_url = $hierarchy[0]['topics'][0]['permalink'];
} elseif (!empty($hierarchy[0]['permalink'])) {
    $first_step_url = $hierarchy[0]['permalink'];
}

$categories = get_the_term_list($course_id, 'smlms_course_category', '', ', ', '');
if (!$categories) {
    $categories = get_the_term_list($course_id, 'category', '', ', ', '');
}

$featured_img_url = has_post_thumbnail() ? get_the_post_thumbnail_url($course_id, 'medium_large') : '';
?>

<div class="smlms-single-course-page">

    <!-- Hero Header Banner -->
    <header class="smlms-hero-banner">
        <div class="smlms-hero-container">
            
            <div class="smlms-hero-left">
                <!-- Breadcrumbs -->
                <nav class="smlms-breadcrumbs">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &gt; 
                    <a href="<?php echo esc_url(get_post_type_archive_link('smlms_course')); ?>">Courses</a> &gt; 
                    <?php if ($categories): ?>
                        <span><?php echo $categories; ?></span> &gt;
                    <?php endif; ?>
                    <span><?php the_title(); ?></span>
                </nav>

                <!-- Mobile Featured Image & Play Button -->
                <div class="smlms-mobile-media-preview">
                    <div class="smlms-card-preview-media">
                        <?php if ($featured_img_url): ?>
                            <img src="<?php echo esc_url($featured_img_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <?php else: ?>
                            <div style="width:100%; height:100%; background:#1e293b;"></div>
                        <?php endif; ?>

                        <?php if ($embed_url): ?>
                            <button type="button" class="smlms-play-overlay smlms-open-modal" data-embed="<?php echo esc_attr($embed_url); ?>" title="Watch Video Preview">
                                <span class="dashicons dashicons-controls-play"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Course Title -->
                <h1 class="smlms-course-title"><?php the_title(); ?></h1>

                <!-- Hero Meta Row -->
                <div class="smlms-hero-meta">
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-admin-users"></span> Created by <a href="<?php echo esc_url($author_url); ?>" class="smlms-hero-author-link"><?php echo esc_html($author_name); ?></a>
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-groups"></span> <strong><?php echo esc_html($enrolled); ?></strong> students enrolled
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-chart-bar"></span> <strong><?php echo esc_html($level); ?></strong>
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-translation"></span> <strong><?php echo esc_html($language); ?></strong>
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-clock"></span> <strong><?php echo esc_html($duration); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Desktop Floating Side Card -->
            <div class="smlms-hero-right-card">
                <!-- Featured Image & Play Button Preview -->
                <div class="smlms-card-preview-media smlms-desktop-media-preview">
                    <?php if ($featured_img_url): ?>
                        <img src="<?php echo esc_url($featured_img_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                    <?php else: ?>
                        <div style="width:100%; height:100%; background:#1e293b;"></div>
                    <?php endif; ?>

                    <?php if ($embed_url): ?>
                        <button type="button" class="smlms-play-overlay smlms-open-modal" data-embed="<?php echo esc_attr($embed_url); ?>" title="Watch Video Preview">
                            <span class="dashicons dashicons-controls-play"></span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="smlms-card-actions">
                    <?php if ($has_access): ?>
                        <a href="<?php echo esc_url($first_step_url); ?>" class="smlms-btn-start-course">
                            <?php echo $is_enrolled ? 'RESUME COURSE' : 'START COURSE'; ?>
                        </a>
                    <?php else: ?>
                        <?php if (!empty($price)): ?>
                            <div class="smlms-card-price-display">
                                $<?php echo esc_html($price); ?>
                            </div>
                        <?php endif; ?>

                        <a href="<?php echo esc_url($button_url); ?>" class="smlms-btn-enroll-now">
                            ENROLL NOW
                        </a>
                    <?php endif; ?>

                    <button type="button" class="smlms-btn-share-course" onclick="navigator.clipboard.writeText(window.location.href); alert('Course link copied to clipboard!');">
                        SHARE <span class="dashicons dashicons-share"></span>
                    </button>
                </div>
            </div>

        </div>
    </header>

    <!-- Navigation Tabs Bar -->
    <div class="smlms-course-nav-tabs">
        <div class="smlms-tabs-container">
            <button type="button" class="smlms-course-tab-btn active" data-target="#smlms-tab-curriculum">Curriculum</button>
            <button type="button" class="smlms-course-tab-btn" data-target="#smlms-tab-overview">Overview</button>
            <button type="button" class="smlms-course-tab-btn" data-target="#smlms-tab-instructors">Instructors</button>
            <button type="button" class="smlms-course-tab-btn" data-target="#smlms-tab-reviews">Reviews</button>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="smlms-course-body-container">
        <div class="smlms-main-column">

            <!-- Tab 1: Curriculum -->
            <div id="smlms-tab-curriculum" class="smlms-course-tab-pane active">
                <div class="smlms-curriculum-header">
                    <h2>Course Content</h2>
                    <button type="button" id="smlms-frontend-expand-all" class="smlms-expand-all-btn">
                        &#9650; Collapse All
                    </button>
                </div>

                <div class="smlms-curriculum-tree">
                    <?php if (empty($hierarchy)): ?>
                        <p>No curriculum steps published yet.</p>
                    <?php else: ?>
                        <?php foreach ($hierarchy as $l_index => $lesson): 
                            $topics           = !empty($lesson['topics']) ? $lesson['topics'] : [];
                            $topic_count      = count($topics);
                            $is_lesson_sample = get_post_meta($lesson['lesson_id'], '_smlms_is_sample', true) === '1';
                            $lesson_can_view  = $has_access || $is_lesson_sample;
                            $lesson_target_url= $lesson_can_view ? esc_url($lesson['permalink']) : esc_url($button_url);
                        ?>
                            <div class="smlms-frontend-lesson-card">
                                <div class="smlms-lesson-card-header">
                                    <div class="smlms-lesson-title-group">
                                        <?php if ($topic_count > 0): ?>
                                            <span class="dashicons dashicons-desktop smlms-lesson-icon" title="Lesson with Topics"></span>
                                        <?php else: ?>
                                            <span class="smlms-play-circle-icon" title="Video Lesson">
                                                <span class="dashicons dashicons-controls-play"></span>
                                            </span>
                                        <?php endif; ?>

                                        <a href="<?php echo $lesson_target_url; ?>" class="smlms-lesson-name">
                                            <?php echo ($l_index + 1) . '. ' . esc_html($lesson['lesson_title']); ?>
                                        </a>
                                    </div>

                                    <div class="smlms-lesson-meta-group">
                                        <?php if ($is_lesson_sample): ?>
                                            <span class="smlms-sample-badge">
                                                <span class="dashicons dashicons-unlock"></span> Sample Lesson
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($topic_count > 0): ?>
                                            <span class="smlms-topic-badge-count"><?php echo $topic_count; ?> Topics</span>
                                        <?php else: ?>
                                            <span class="smlms-duration-tag"><?php echo esc_html($lesson['duration']); ?></span>
                                        <?php endif; ?>

                                        <?php if (!$lesson_can_view): ?>
                                            <div class="smlms-lock-tooltip-wrap">
                                                <span class="dashicons dashicons-lock"></span>
                                                <span class="smlms-tooltip-text">You don't currently have access to this content</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($topic_count > 0): ?>
                                            <span class="smlms-accordion-circle">&#9660;</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($topics)): ?>
                                    <div class="smlms-lesson-card-body" style="display: block;">
                                        <ul class="smlms-frontend-topic-list">
                                            <?php foreach ($topics as $t_index => $topic): 
                                                $topic_can_view   = $lesson_can_view; // Child topics inherit sample lesson access
                                                $topic_target_url = $topic_can_view ? esc_url($topic['permalink']) : esc_url($button_url);
                                            ?>
                                                <li class="smlms-frontend-topic-item">
                                                    <a href="<?php echo $topic_target_url; ?>">
                                                        <div class="smlms-topic-left">
                                                            <span class="smlms-play-circle-icon">
                                                                <span class="dashicons dashicons-controls-play"></span>
                                                            </span>
                                                            <span><?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic['title']); ?></span>
                                                        </div>

                                                        <div class="smlms-topic-right">
                                                            <span class="smlms-duration-tag"><?php echo esc_html($topic['duration']); ?></span>
                                                            
                                                            <?php if (!$topic_can_view): ?>
                                                                <div class="smlms-lock-tooltip-wrap">
                                                                    <span class="dashicons dashicons-lock"></span>
                                                                    <span class="smlms-tooltip-text">You don't currently have access to this content</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab 2: Overview -->
            <div id="smlms-tab-overview" class="smlms-course-tab-pane">
                <div style="background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e2e8f0; line-height: 1.6;">
                    <?php the_content(); ?>
                </div>
            </div>

            <!-- Tab 3: Instructors -->
            <div id="smlms-tab-instructors" class="smlms-course-tab-pane">
                <div class="smlms-instructor-card">
                    <img src="<?php echo esc_url($author_avatar); ?>" class="smlms-instructor-img" alt="<?php echo esc_attr($author_name); ?>">
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 600;"><?php echo esc_html($author_name); ?></h3>
                        <p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b; font-weight: 400;"><?php echo esc_html($author_bio ? $author_bio : 'Robotics and industrial automation professional.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Reviews -->
            <div id="smlms-tab-reviews" class="smlms-course-tab-pane">
                <div style="background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h3 style="margin-top:0; font-size: 18px; font-weight: 600;">Student Reviews</h3>
                    <p style="color:#64748b; margin:0; font-weight: 400;">There are no reviews for this course yet.</p>
                </div>
            </div>

        </div>
    </main>

</div>

<!-- Pop-up Video Lightbox Modal -->
<div id="smlms-video-modal" class="smlms-video-modal">
    <div class="smlms-video-modal-backdrop" id="smlms-modal-backdrop"></div>
    <div class="smlms-video-modal-dialog">
        <button type="button" class="smlms-modal-close-btn" id="smlms-close-video-modal">&times;</button>
        <div class="smlms-video-responsive-wrap">
            <iframe id="smlms-lightbox-iframe" src="" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {

    // Tab Switching
    $('.smlms-course-tab-btn').on('click', function() {
        $('.smlms-course-tab-btn').removeClass('active');
        $('.smlms-course-tab-pane').removeClass('active');

        $(this).addClass('active');
        $($(this).data('target')).addClass('active');
    });

    // Lesson Accordions
    $(document).on('click', '.smlms-lesson-card-header', function(e) {
        if ($(e.target).is('a') || $(e.target).closest('.smlms-lock-tooltip-wrap').length) return;
        const body = $(this).next('.smlms-lesson-card-body');
        body.slideToggle(150);
    });

    // Expand / Collapse All
    $('#smlms-frontend-expand-all').on('click', function() {
        const isCollapsed = $(this).data('collapsed');

        if (isCollapsed) {
            $('.smlms-lesson-card-body').slideDown(150);
            $(this).html('&#9650; Collapse All').data('collapsed', false);
        } else {
            $('.smlms-lesson-card-body').slideUp(150);
            $(this).html('&#9660; Expand All').data('collapsed', true);
        }
    });

    // Video Lightbox Pop-up
    $(document).on('click', '.smlms-open-modal', function(e) {
        e.preventDefault();
        const embedUrl = $(this).data('embed');
        if (embedUrl) {
            $('#smlms-lightbox-iframe').attr('src', embedUrl);
            $('#smlms-video-modal').addClass('active');
        }
    });

    function closeVideoModal() {
        $('#smlms-video-modal').removeClass('active');
        $('#smlms-lightbox-iframe').attr('src', '');
    }

    $('#smlms-close-video-modal, #smlms-modal-backdrop').on('click', closeVideoModal);

    $(document).on('keyup', function(e) {
        if (e.key === "Escape") {
            closeVideoModal();
        }
    });
});
</script>

<?php get_footer(); ?>