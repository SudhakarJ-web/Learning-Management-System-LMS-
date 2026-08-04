<?php
/**
 * Single Course Frontend View - LearnDash Layout Replica
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

// Course Tree JSON
$raw_tree   = get_post_meta($course_id, '_smlms_course_tree_json', true);
$course_tree = !empty($raw_tree) ? json_decode($raw_tree, true) : [];

// First Topic URL for "Start Course" button
$first_topic_url = '#';
if (!empty($course_tree[0]['topics'][0]['id'])) {
    $first_topic_url = get_permalink($course_tree[0]['topics'][0]['id']);
} elseif (!empty($course_tree[0]['id'])) {
    $first_topic_url = get_permalink($course_tree[0]['id']);
}

// Metadata
$level      = get_post_meta($course_id, '_smlms_level', true) ?: 'Beginner';
$language   = get_post_meta($course_id, '_smlms_language', true) ?: 'English';
$duration   = get_post_meta($course_id, '_smlms_duration', true) ?: '4 Weeks';
$categories = get_the_term_list($course_id, 'smlms_course_category', '', ', ', '');
?>

<div class="smlms-single-course-page">

    <!-- 1. Purple Hero Banner Section -->
    <header class="smlms-hero-banner">
        <div class="smlms-hero-container">
            
            <div class="smlms-hero-left">
                <!-- Breadcrumbs -->
                <nav class="smlms-breadcrumbs">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &gt; 
                    <a href="<?php echo esc_url(get_post_type_archive_link('smlms_course')); ?>">Courses</a> &gt; 
                    <span><?php the_title(); ?></span>
                </nav>

                <!-- Title -->
                <h1 class="smlms-course-title"><?php the_title(); ?></h1>

                <!-- Hero Meta Details -->
                <div class="smlms-hero-meta">
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-admin-users"></span> Created by <strong><?php echo esc_html($author_name); ?></strong>
                    </div>
                    <?php if ($categories): ?>
                        <div class="smlms-meta-item">
                            <span class="dashicons dashicons-tag"></span> <?php echo $categories; ?>
                        </div>
                    <?php endif; ?>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-chart-bar"></span> <?php echo esc_html($level); ?>
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-translation"></span> <?php echo esc_html($language); ?>
                    </div>
                    <div class="smlms-meta-item">
                        <span class="dashicons dashicons-clock"></span> <?php echo esc_html($duration); ?>
                    </div>
                </div>
            </div>

            <!-- 2. Right Floating Card -->
            <div class="smlms-hero-right-card">
                <div class="smlms-card-preview-media">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium_large'); ?>
                    <?php else: ?>
                        <div class="smlms-media-placeholder"></div>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($first_topic_url); ?>" class="smlms-play-overlay">
                        <span class="dashicons dashicons-controls-play"></span>
                    </a>
                </div>

                <div class="smlms-card-actions">
                    <a href="<?php echo esc_url($first_topic_url); ?>" class="smlms-btn-start-course">
                        START COURSE
                    </a>
                    <button type="button" class="smlms-btn-share-course" onclick="navigator.clipboard.writeText(window.location.href); alert('Course link copied!');">
                        SHARE <span class="dashicons dashicons-share"></span>
                    </button>
                </div>
            </div>

        </div>
    </header>

    <!-- 3. Navigation Tabs Header -->
    <div class="smlms-course-nav-tabs">
        <div class="smlms-tabs-container">
            <button class="smlms-course-tab-btn active" data-target="#smlms-tab-curriculum">Curriculum</button>
            <button class="smlms-course-tab-btn" data-target="#smlms-tab-overview">Overview</button>
            <button class="smlms-course-tab-btn" data-target="#smlms-tab-instructors">Instructors</button>
            <button class="smlms-course-tab-btn" data-target="#smlms-tab-reviews">Reviews</button>
        </div>
    </div>

    <!-- 4. Tab Content Body -->
    <main class="smlms-course-body-container">
        <div class="smlms-main-column">

            <!-- Tab 1: Curriculum -->
            <div id="smlms-tab-curriculum" class="smlms-course-tab-pane active">
                <div class="smlms-curriculum-header">
                    <h2>Course Content</h2>
                    <button type="button" id="smlms-frontend-expand-all" class="smlms-expand-all-btn">
                        Expand All
                    </button>
                </div>

                <div class="smlms-curriculum-tree">
                    <?php if (empty($course_tree)): ?>
                        <p class="smlms-no-content">No curriculum steps published yet.</p>
                    <?php else: ?>
                        <?php foreach ($course_tree as $l_index => $lesson): 
                            $topics = !empty($lesson['topics']) ? $lesson['topics'] : [];
                            $topic_count = count($topics);
                        ?>
                            <div class="smlms-frontend-lesson-card">
                                <div class="smlms-lesson-card-header">
                                    <div class="smlms-lesson-title-group">
                                        <span class="dashicons dashicons-desktop smlms-lesson-icon"></span>
                                        <strong class="smlms-lesson-name"><?php echo ($l_index + 1) . '. ' . esc_html($lesson['title']); ?></strong>
                                    </div>
                                    <div class="smlms-lesson-meta-group">
                                        <span class="smlms-topic-badge-count"><?php echo $topic_count; ?> Topics</span>
                                        <span class="smlms-accordion-circle">&#9660;</span>
                                    </div>
                                </div>

                                <div class="smlms-lesson-card-body" style="display: none;">
                                    <?php if (!empty($topics)): ?>
                                        <ul class="smlms-frontend-topic-list">
                                            <?php foreach ($topics as $t_index => $topic): ?>
                                                <li class="smlms-frontend-topic-item">
                                                    <a href="<?php echo esc_url(get_permalink($topic['id'])); ?>">
                                                        <span class="dashicons dashicons-controls-play smlms-topic-icon"></span>
                                                        <span><?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic['title']); ?></span>
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
                <div class="smlms-overview-content">
                    <?php the_content(); ?>
                </div>
            </div>

            <!-- Tab 3: Instructors -->
            <div id="smlms-tab-instructors" class="smlms-course-tab-pane">
                <div class="smlms-instructor-card">
                    <img src="<?php echo esc_url($author_avatar); ?>" class="smlms-instructor-img" alt="<?php echo esc_attr($author_name); ?>">
                    <div class="smlms-instructor-info">
                        <h3><?php echo esc_html($author_name); ?></h3>
                        <p><?php echo esc_html($author_bio ? $author_bio : 'Robotics and industrial automation professional.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Reviews -->
            <div id="smlms-tab-reviews" class="smlms-course-tab-pane">
                <div class="smlms-reviews-box">
                    <h3>Student Reviews</h3>
                    <p>There are no reviews for this course yet.</p>
                </div>
            </div>

        </div>
    </main>

</div>

<?php get_footer(); ?>