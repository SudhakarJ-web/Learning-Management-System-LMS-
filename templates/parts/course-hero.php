<?php
/**
 * Single Course Hero Banner & Side Card Part
 */

if (!defined('ABSPATH')) exit;

$course_id        = $context['course_id'];
$author_id        = $context['author_id'] ?? get_post_field('post_author', $course_id);
$author_name      = $context['author_name'] ?? (get_the_author_meta('display_name', $author_id) ?: 'Sabin Mathew');
$access_type      = $context['access_type'] ?? 'closed';
$user_id          = $context['user_id'];
$duration         = get_post_meta($course_id, '_smlms_duration', true) ?: '4 Weeks';
$level            = get_post_meta($course_id, '_smlms_level', true) ?: 'Beginner';
$language         = get_post_meta($course_id, '_smlms_language', true) ?: 'English';

// Total Enrolled Calculation
$manual_offset    = intval(get_post_meta($course_id, '_smlms_students_enrolled', true));
$db_admin_granted = SMLMS_DB::get_enrolled_students_count($course_id);
$enrolled         = $manual_offset + $db_admin_granted;

$featured_img_url = has_post_thumbnail() ? get_the_post_thumbnail_url($course_id, 'medium_large') : '';

$categories = get_the_term_list($course_id, 'smlms_course_category', '', ', ', '');
if (!$categories) {
    $categories = get_the_term_list($course_id, 'category', '', ', ', '');
}

$about_me_url = home_url('/about-me/');

// Build Free Enrollment Nonce URL
$free_enroll_url = wp_nonce_url(
    add_query_arg(['smlms_action' => 'free_enroll', 'course_id' => $course_id], get_permalink($course_id)),
    'smlms_free_enroll_' . $course_id
);
?>

<header class="smlms-hero-banner">
    <div class="smlms-hero-container">
        
        <div class="smlms-hero-left">
            <nav class="smlms-breadcrumbs">
                <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &gt; 
                <a href="<?php echo esc_url(get_post_type_archive_link('smlms_course')); ?>">Courses</a> &gt; 
                <?php if ($categories): ?>
                    <span><?php echo $categories; ?></span> &gt;
                <?php endif; ?>
                <span><?php the_title(); ?></span>
            </nav>

            <!-- Mobile Video Preview -->
            <div class="smlms-card-preview-media smlms-mobile-media-preview" onclick="smlmsOpenCourseVideo(this)" data-embed="<?php echo esc_attr($context['embed_url']); ?>">
                <?php if ($featured_img_url): ?>
                    <img src="<?php echo esc_url($featured_img_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                <?php else: ?>
                    <div style="width:100%; height:100%; background:#1e293b;"></div>
                <?php endif; ?>

                <?php if (!empty($context['embed_url'])): ?>
                    <button type="button" class="smlms-play-overlay" onclick="smlmsOpenCourseVideo(this); event.stopPropagation();" data-embed="<?php echo esc_attr($context['embed_url']); ?>" title="Watch Video Preview">
                        <span class="dashicons dashicons-controls-play"></span>
                    </button>
                <?php endif; ?>
            </div>

            <h1 class="smlms-course-title"><?php the_title(); ?></h1>

            <div class="smlms-hero-meta">
                <div class="smlms-meta-item">
                    <span class="dashicons dashicons-admin-users"></span> Created by 
                    <a href="<?php echo esc_url($about_me_url); ?>" class="smlms-hero-author-link">
                        <?php echo esc_html($author_name); ?>
                    </a>
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

        <div class="smlms-hero-right-card">
            <!-- Desktop Video Preview -->
            <div class="smlms-card-preview-media smlms-desktop-media-preview" onclick="smlmsOpenCourseVideo(this)" data-embed="<?php echo esc_attr($context['embed_url']); ?>">
                <?php if ($featured_img_url): ?>
                    <img src="<?php echo esc_url($featured_img_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                <?php else: ?>
                    <div style="width:100%; height:100%; background:#1e293b;"></div>
                <?php endif; ?>

                <?php if (!empty($context['embed_url'])): ?>
                    <button type="button" class="smlms-play-overlay" onclick="smlmsOpenCourseVideo(this); event.stopPropagation();" data-embed="<?php echo esc_attr($context['embed_url']); ?>" title="Watch Video Preview">
                        <span class="dashicons dashicons-controls-play"></span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="smlms-card-actions">
                <!-- Price Display Logic -->
                <?php if ($access_type === 'open' || $access_type === 'free'): ?>
                    <div class="smlms-card-price-display">FREE</div>
                <?php elseif (!$context['has_access'] && !empty($context['price'])): ?>
                    <div class="smlms-card-price-display">$<?php echo esc_html($context['price']); ?></div>
                <?php endif; ?>

                <!-- Primary Action Button Logic -->
                <?php if ($context['has_access']): ?>
                    <a href="<?php echo esc_url($context['first_step_url']); ?>" class="smlms-btn-start-course">
                        <?php echo $context['is_enrolled'] ? 'RESUME COURSE' : 'START COURSE'; ?>
                    </a>
                <?php elseif ($access_type === 'free'): ?>
                    <?php if ($user_id > 0): ?>
                        <a href="<?php echo esc_url($free_enroll_url); ?>" class="smlms-btn-enroll-now">
                            Enroll For Free
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink($course_id))); ?>" class="smlms-btn-enroll-now">
                            Login To Enroll
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo esc_url($context['button_url']); ?>" class="smlms-btn-enroll-now">
                        Enroll Now
                    </a>
                <?php endif; ?>

                <button type="button" class="smlms-btn-share-course" onclick="navigator.clipboard.writeText(window.location.href); alert('Course link copied to clipboard!');">
                    SHARE <span class="dashicons dashicons-share"></span>
                </button>
            </div>
        </div>

    </div>
</header>