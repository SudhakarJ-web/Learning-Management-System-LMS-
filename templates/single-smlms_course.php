<?php
/**
 * Single Course Landing Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$course_id   = get_the_ID();
$user_id     = get_current_user_id();

// Meta Values Lookup
$headline    = get_post_meta($course_id, '_smlms_course_headline', true) ?: get_the_title();
$short_desc  = get_post_meta($course_id, '_smlms_course_short_desc', true);
$level       = get_post_meta($course_id, '_smlms_level', true) ?: 'Beginner';
$language    = get_post_meta($course_id, '_smlms_language', true) ?: 'English';
$duration    = get_post_meta($course_id, '_smlms_duration', true) ?: '4 Weeks';
$access_type = get_post_meta($course_id, '_smlms_access_type', true) ?: 'closed';
$price       = get_post_meta($course_id, '_smlms_price', true) ?: 'Free';

// Student Count Calculation
$db_enrolled_count = SMLMS_DB::get_enrolled_students_count($course_id);
$manual_offset     = intval(get_post_meta($course_id, '_smlms_students_enrolled', true) ?: 0);
$total_students    = $db_enrolled_count + $manual_offset;

// Enrollment Check
$is_enrolled  = ($user_id > 0) ? SMLMS_DB::is_user_enrolled($user_id, $course_id) : false;
$has_access   = $is_enrolled || current_user_can('manage_options');

// Rating Summary
$summary = class_exists('SMLMS_Reviews') ? SMLMS_Reviews::get_rating_summary($course_id) : ['avg_rating' => 0, 'total_count' => 0];

// Course Hierarchy & First Lesson Link for Action Buttons
$hierarchy  = SMLMS_DB::get_course_hierarchy($course_id, $user_id);
$first_step_url = '#';
if (!empty($hierarchy) && !empty($hierarchy[0]['permalink'])) {
    $first_step_url = $hierarchy[0]['permalink'];
}

// Media / Video Embed Parser
$video_url = get_post_meta($course_id, '_smlms_video_id', true) ?: get_post_meta($course_id, '_smlms_media_embed', true);
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

<div class="smlms-course-page-wrapper">

    <!-- Top Purple Hero Banner Header -->
    <section class="smlms-course-hero-header">
        <div class="smlms-hero-container">
            <div class="smlms-hero-content-col">
                
                <!-- Breadcrumbs -->
                <div class="smlms-hero-breadcrumbs">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &gt; 
                    <a href="<?php echo esc_url(home_url('/courses/')); ?>">Courses</a> &gt; 
                    <span><?php the_title(); ?></span>
                </div>

                <h1 class="smlms-hero-course-title"><?php the_title(); ?></h1>

                <div class="smlms-hero-meta-row">
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-admin-users"></span> Created by <strong><?php echo esc_html(get_the_author()); ?></strong>
                    </span>
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-groups"></span> <strong><?php echo esc_html($total_students); ?></strong> students enrolled
                    </span>
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-chart-bar"></span> <strong><?php echo esc_html($level); ?></strong>
                    </span>
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-translation"></span> <strong><?php echo esc_html($language); ?></strong>
                    </span>
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-clock"></span> <strong><?php echo esc_html($duration); ?></strong>
                    </span>
                </div>

                <?php if ($summary['total_count'] > 0): ?>
                    <div class="smlms-hero-ratings-row">
                        <div class="smlms-stars-display">
                            <?php 
                            $avg_round = round($summary['avg_rating']);
                            for ($i = 1; $i <= 5; $i++): 
                                $filled = ($i <= $avg_round) ? 'star-filled' : 'star-empty';
                            ?>
                                <span class="dashicons dashicons-star-filled <?php echo esc_attr($filled); ?>"></span>
                            <?php endfor; ?>
                        </div>
                        <span class="smlms-rating-score-text"><?php echo esc_html($summary['avg_rating']); ?> (<?php echo esc_html($summary['total_count']); ?> ratings)</span>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </section>

    <!-- Main Section Body (Tabs + Sidebar Card) -->
    <section class="smlms-course-body-section">
        <div class="smlms-body-container">
            
            <!-- Left Main Column: Navigation Tabs & Tab Content -->
            <div class="smlms-course-main-column">
                
                <!-- Main Horizontal Navigation Tabs Bar -->
                <nav class="smlms-course-tabs-nav">
                    <button type="button" class="smlms-course-tab-btn active" data-tab="#smlms-tab-curriculum">Curriculum</button>
                    <button type="button" class="smlms-course-tab-btn" data-tab="#smlms-tab-overview">Overview</button>
                    <button type="button" class="smlms-course-tab-btn" data-tab="#smlms-tab-instructors">Instructors</button>
                    <button type="button" class="smlms-course-tab-btn" data-tab="#smlms-tab-reviews">Reviews</button>
                </nav>

                <!-- Tab Panes Group -->
                <div class="smlms-course-tab-panes">
                    
                    <!-- 1. Curriculum Tab Pane -->
                    <div id="smlms-tab-curriculum" class="smlms-tab-pane active">
                        <div class="smlms-curriculum-accordion-box">
                            <?php if (!empty($hierarchy)): ?>
                                <?php foreach ($hierarchy as $l_idx => $lesson): ?>
                                    <div class="smlms-curriculum-lesson-card">
                                        <div class="smlms-curriculum-lesson-header">
                                            <div class="smlms-lesson-header-left">
                                                <span class="smlms-curriculum-lesson-title">
                                                    <?php echo ($l_idx + 1) . '. ' . esc_html($lesson['lesson_title']); ?>
                                                </span>
                                            </div>
                                            <div class="smlms-lesson-header-right">
                                                <span class="smlms-lesson-duration"><?php echo esc_html($lesson['duration']); ?></span>
                                            </div>
                                        </div>

                                        <?php if (!empty($lesson['topics'])): ?>
                                            <ul class="smlms-curriculum-topics-list">
                                                <?php foreach ($lesson['topics'] as $t_idx => $topic): ?>
                                                    <li class="smlms-topic-list-item">
                                                        <div class="smlms-topic-left">
                                                            <span class="dashicons dashicons-controls-play smlms-topic-play-icon"></span>
                                                            <a href="<?php echo esc_url($topic['permalink']); ?>" class="smlms-topic-title-link">
                                                                <?php echo ($l_idx + 1) . '.' . ($t_idx + 1) . ' ' . esc_html($topic['title']); ?>
                                                            </a>
                                                        </div>
                                                        <div class="smlms-topic-right">
                                                            <span class="smlms-topic-duration"><?php echo esc_html($topic['duration']); ?></span>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="smlms-no-content-msg">No course curriculum published yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2. Overview Tab Pane -->
                    <div id="smlms-tab-overview" class="smlms-tab-pane">
                        <div class="smlms-overview-content-body">
                            <?php 
                            while (have_posts()) : the_post();
                                the_content();
                            endwhile;
                            ?>
                        </div>
                    </div>

                    <!-- 3. Instructors Tab Pane -->
                    <div id="smlms-tab-instructors" class="smlms-tab-pane">
                        <div class="smlms-instructor-card">
                            <img src="<?php echo esc_url(get_avatar_url(get_the_author_meta('ID'), ['size' => 80])); ?>" class="smlms-instructor-avatar" alt="Instructor Avatar">
                            <div class="smlms-instructor-info">
                                <h3 class="smlms-instructor-name"><?php echo esc_html(get_the_author()); ?></h3>
                                <p class="smlms-instructor-bio"><?php echo esc_html(get_the_author_meta('description') ?: 'Course Instructor & Engineering Expert.'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Reviews Tab Pane -->
                    <div id="smlms-tab-reviews" class="smlms-tab-pane">
                        <?php 
                        $reviews_template = SMLMS_PLUGIN_DIR . 'templates/parts/tab-reviews.php';
                        if (file_exists($reviews_template)) {
                            include $reviews_template;
                        } elseif (file_exists(SMLMS_PLUGIN_DIR . 'templates/parts/course-reviews.php')) {
                            include SMLMS_PLUGIN_DIR . 'templates/parts/course-reviews.php';
                        } else {
                            echo '<p>Reviews module template not found.</p>';
                        }
                        ?>
                    </div>

                </div>

            </div>

            <!-- Right Sticky Sidebar Card (Preview Media + Enrolment Actions) -->
            <div class="smlms-course-sidebar-column">
                <div class="smlms-sticky-course-card">
                    
                    <!-- Top Media Preview Player / Featured Image -->
                    <div class="smlms-card-media-wrap">
                        <?php if (!empty($embed_src)): ?>
                            <iframe src="<?php echo esc_url($embed_src); ?>" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
                        <?php elseif (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium_large', ['class' => 'smlms-card-featured-img']); ?>
                        <?php else: ?>
                            <div class="smlms-card-media-placeholder">
                                <span class="dashicons dashicons-welcome-learn-more"></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons Section -->
                    <div class="smlms-card-actions-body">
                        <?php if ($has_access): ?>
                            <a href="<?php echo esc_url($first_step_url); ?>" class="smlms-btn-action-purple">RESUME COURSE</a>
                        <?php else: ?>
                            <?php if ($access_type === 'free'): ?>
                                <?php 
                                $free_url = wp_nonce_url(
                                    add_query_arg(['smlms_action' => 'free_enroll', 'course_id' => $course_id], get_permalink($course_id)),
                                    'smlms_free_enroll_' . $course_id
                                );
                                ?>
                                <a href="<?php echo esc_url($user_id ? $free_url : wp_login_url(get_permalink($course_id))); ?>" class="smlms-btn-action-purple">
                                    ENROLL NOW (FREE)
                                </a>
                            <?php else: ?>
                                <?php 
                                $checkout_url = get_post_meta($course_id, '_smlms_custom_checkout_url', true) ?: '#';
                                ?>
                                <a href="<?php echo esc_url($checkout_url); ?>" class="smlms-btn-action-purple">
                                    TAKE THIS COURSE (<?php echo esc_html($price); ?>)
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <button type="button" class="smlms-btn-share-course" onclick="navigator.clipboard.writeText(window.location.href); alert('Course link copied to clipboard!');">
                            SHARE <span class="dashicons dashicons-share"></span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </section>

</div>

<script>
jQuery(document).ready(function($) {
    // Single Course Main Navigation Tabs Handler
    $(document).on('click', '.smlms-course-tab-btn', function(e) {
        e.preventDefault();
        var targetTab = $(this).attr('data-tab');

        $('.smlms-course-tab-btn').removeClass('active');
        $('.smlms-tab-pane').removeClass('active');

        $(this).addClass('active');
        $(targetTab).addClass('active');
    });

    // Check for URL hash redirect to reviews tab
    if (window.location.hash && window.location.hash.indexOf('reviews') > -1) {
        $('.smlms-course-tab-btn[data-tab="#smlms-tab-reviews"]').trigger('click');
    }
});
</script>

<?php
get_footer();