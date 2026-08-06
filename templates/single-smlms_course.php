<?php
/**
 * Single Course Landing Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$course_id     = get_the_ID();
$author_id     = get_post_field('post_author', $course_id);
$author_name   = get_the_author_meta('display_name', $author_id);
$author_avatar = get_avatar_url($author_id, ['size' => 120]);
$author_bio    = get_the_author_meta('description', $author_id);

// Custom Meta
$duration     = get_post_meta($course_id, '_smlms_duration', true) ?: '4 Weeks';
$level        = get_post_meta($course_id, '_smlms_level', true) ?: 'Beginner';
$language     = get_post_meta($course_id, '_smlms_language', true) ?: 'English';
$enrolled     = get_post_meta($course_id, '_smlms_students_enrolled', true) ?: '17';
$media_embed  = get_post_meta($course_id, '_smlms_media_embed', true);

// Course Hierarchy
$hierarchy    = SMLMS_DB::get_course_hierarchy($course_id, get_current_user_id());

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
?>

<div class="smlms-single-course-page">

    <!-- Hero Header Banner -->
    <header class="smlms-hero-banner">
        <div class="smlms-hero-container">
            
            <div class="smlms-hero-left">
                <!-- 1. Breadcrumbs -->
                <nav class="smlms-breadcrumbs">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &gt; 
                    <a href="<?php echo esc_url(get_post_type_archive_link('smlms_course')); ?>">Courses</a> &gt; 
                    <?php if ($categories): ?>
                        <span><?php echo $categories; ?></span> &gt;
                    <?php endif; ?>
                    <span><?php the_title(); ?></span>
                </nav>

                <!-- 2. Mobile Video Preview Media (Appears above Title on Mobile) -->
                <div class="smlms-mobile-media-preview">
                    <div class="smlms-card-preview-media">
                        <?php if ($media_embed && is_numeric($media_embed)): ?>
                            <iframe src="https://player.vimeo.com/video/<?php echo esc_attr($media_embed); ?>" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
                        <?php elseif (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium_large'); ?>
                        <?php else: ?>
                            <div style="width:100%; height:100%; background:#1e293b;"></div>
                        <?php endif; ?>

                        <a href="<?php echo esc_url($first_step_url); ?>" class="smlms-play-overlay" title="Start Course">
                            <span class="dashicons dashicons-controls-play" style="font-size:28px; width:28px; height:28px; line-height:28px;"></span>
                        </a>
                    </div>
                </div>

                <!-- 3. Course Title -->
                <h1 class="smlms-course-title"><?php the_title(); ?></h1>

                <!-- 4. Hero Meta Row -->
                <div class="smlms-hero-meta">
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-admin-users"></span> Created by <?php echo esc_html($author_name); ?>
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-groups"></span><?php echo esc_html($enrolled); ?> Students enrolled
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-chart-bar"></span> <?php echo esc_html($level); ?>
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-translation"></span><?php echo esc_html($language); ?>
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-clock"></span><?php echo esc_html($duration); ?>
                    </div>
                </div>
            </div>

            <!-- 5. Right Floating Side Card -->
            <div class="smlms-hero-right-card">
                <!-- Desktop Media Preview (Hidden on Mobile) -->
                <div class="smlms-card-preview-media smlms-desktop-media-preview">
                    <?php if ($media_embed && is_numeric($media_embed)): ?>
                        <iframe src="https://player.vimeo.com/video/<?php echo esc_attr($media_embed); ?>" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
                    <?php elseif (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium_large'); ?>
                    <?php else: ?>
                        <div style="width:100%; height:100%; background:#1e293b;"></div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url($first_step_url); ?>" class="smlms-play-overlay" title="Start Course">
                        <span class="dashicons dashicons-controls-play" style="font-size:28px; width:28px; height:28px; line-height:28px;"></span>
                    </a>
                </div>

                <div class="smlms-card-actions">
                    <a href="<?php echo esc_url($first_step_url); ?>" class="smlms-btn-start-course">
                        START COURSE
                    </a>
                    <button type="button" class="smlms-btn-share-course" onclick="navigator.clipboard.writeText(window.location.href); alert('Course link copied!');">
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
                        Collapse All
                    </button>
                </div>

                <div class="smlms-curriculum-tree">
                    <?php if (empty($hierarchy)): ?>
                        <p>No curriculum steps published yet.</p>
                    <?php else: ?>
                        <?php foreach ($hierarchy as $l_index => $lesson): 
                            $topics = !empty($lesson['topics']) ? $lesson['topics'] : [];
                            $topic_count = count($topics);
                        ?>
                            <div class="smlms-frontend-lesson-card">
                                <div class="smlms-lesson-card-header">
                                    <div class="smlms-lesson-title-group">
                                        <span class="dashicons dashicons-desktop smlms-lesson-icon"></span>
                                        <a href="<?php echo esc_url($lesson['permalink']); ?>" class="smlms-lesson-name">
                                            <?php echo ($l_index + 1) . '. ' . esc_html($lesson['lesson_title']); ?>
                                        </a>
                                    </div>
                                    <div class="smlms-lesson-meta-group">
                                        <span class="smlms-topic-badge-count"><?php echo $topic_count; ?> Topics</span>
                                        <span class="smlms-accordion-circle">&#9660;</span>
                                    </div>
                                </div>

                                <div class="smlms-lesson-card-body" style="display: block;">
                                    <?php if (!empty($topics)): ?>
                                        <ul class="smlms-frontend-topic-list">
                                            <?php foreach ($topics as $t_index => $topic): ?>
                                                <li class="smlms-frontend-topic-item">
                                                    <a href="<?php echo esc_url($topic['permalink']); ?>">
                                                        <span><?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic['title']); ?></span>
                                                        <span style="color:#64748b; font-size:13px;"><?php echo esc_html($topic['duration']); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
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
                        <h3 style="margin: 0; font-size: 18px;"><?php echo esc_html($author_name); ?></h3>
                        <p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b;"><?php echo esc_html($author_bio ? $author_bio : 'Robotics and industrial automation professional.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Reviews -->
            <div id="smlms-tab-reviews" class="smlms-course-tab-pane">
                <div style="background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h3 style="margin-top:0;">Student Reviews</h3>
                    <p style="color:#64748b; margin:0;">There are no reviews for this course yet.</p>
                </div>
            </div>

        </div>
    </main>

</div>

<script>
jQuery(document).ready(function($) {
    $('.smlms-course-tab-btn').on('click', function() {
        $('.smlms-course-tab-btn').removeClass('active');
        $('.smlms-course-tab-pane').removeClass('active');

        $(this).addClass('active');
        $($(this).data('target')).addClass('active');
    });

    $(document).on('click', '.smlms-lesson-card-header', function(e) {
        if ($(e.target).is('a')) return;
        const body = $(this).next('.smlms-lesson-card-body');
        body.slideToggle(150);
    });

    $('#smlms-frontend-expand-all').on('click', function() {
        const isCollapsed = $(this).data('collapsed');

        if (isCollapsed) {
            $('.smlms-lesson-card-body').slideDown(150);
            $(this).text('Collapse All').data('collapsed', false);
        } else {
            $('.smlms-lesson-card-body').slideUp(150);
            $(this).text('Expand All').data('collapsed', true);
        }
    });
});
</script>

<?php get_footer(); ?>