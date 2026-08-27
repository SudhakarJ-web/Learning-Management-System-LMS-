<?php
/**
 * Single Course Landing Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inject custom body class for CSS isolation
add_filter('body_class', function($classes) {
    $classes[] = 'smlms-single-course-body';
    return $classes;
});

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

// Formatted Price Display ($ Prefix for Numbers)
$raw_price = get_post_meta($course_id, '_smlms_price', true);
if (empty($raw_price) || strtolower(trim((string)$raw_price)) === 'free') {
    $formatted_price = 'Free';
} else {
    $clean_price = trim((string)$raw_price);
    $formatted_price = (strpos($clean_price, '$') === 0) ? $clean_price : '$' . $clean_price;
}

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

// Media / Video Embed Parser for Pop-up Modal
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

<div class="smlms-single-course-page">

    <!-- Top Purple Hero Banner Header -->
    <section class="smlms-hero-banner">
        <div class="smlms-hero-container">
            
            <div class="smlms-hero-left">
                <!-- Breadcrumbs -->
                <div class="smlms-breadcrumbs">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &gt; 
                    <a href="<?php echo esc_url(home_url('/courses/')); ?>">Courses</a> &gt; 
                    <span><?php the_title(); ?></span>
                </div>

                <!-- Mobile Media Preview -->
                <div class="smlms-mobile-media-preview smlms-card-preview-media" data-embed="<?php echo esc_attr($embed_src); ?>">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium_large', ['class' => 'smlms-card-featured-img']); ?>
                    <?php else: ?>
                        <div class="smlms-card-media-placeholder">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($embed_src)): ?>
                        <div class="smlms-play-overlay">
                            <span class="dashicons dashicons-controls-play"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <h1 class="smlms-course-title"><?php the_title(); ?></h1>

                <!-- Integrated Hero Meta Row -->
                <div class="smlms-hero-meta">
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-admin-users"></span> Created by <a href="#" class="smlms-hero-author-link"><?php echo esc_html(get_the_author()); ?></a>
                    </span>
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-groups"></span> <?php echo esc_html($total_students); ?> students enrolled
                    </span>
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-chart-bar"></span><?php echo esc_html($level); ?>
                    </span>
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-translation"></span><?php echo esc_html($language); ?>
                    </span>
                    <span class="smlms-meta-item">
                        <span class="dashicons dashicons-clock"></span> <?php echo esc_html($duration); ?>
                    </span>

                    <?php if ($summary['total_count'] > 0): ?>
                        <span class="smlms-meta-item smlms-meta-ratings">
                            <span class="smlms-stars-display">
                                <?php 
                                $avg_round = round($summary['avg_rating']);
                                for ($i = 1; $i <= 5; $i++): 
                                    $filled = ($i <= $avg_round) ? 'star-filled' : 'star-empty';
                                ?>
                                    <span class="dashicons dashicons-star-filled <?php echo esc_attr($filled); ?>"></span>
                                <?php endfor; ?>
                            </span>
                            <?php echo esc_html($summary['avg_rating']); ?> (<?php echo esc_html($summary['total_count']); ?> <?php echo ($summary['total_count'] === 1) ? 'rating' : 'ratings'; ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Floating Purchase Card -->
            <div class="smlms-hero-right-card">
                <div class="smlms-card-preview-media smlms-desktop-media-preview" data-embed="<?php echo esc_attr($embed_src); ?>">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium_large', ['class' => 'smlms-card-featured-img']); ?>
                    <?php else: ?>
                        <div class="smlms-card-media-placeholder">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($embed_src)): ?>
                        <div class="smlms-play-overlay">
                            <span class="dashicons dashicons-controls-play"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="smlms-card-actions">
                    <?php if ($has_access): ?>
                        <a href="<?php echo esc_url($first_step_url); ?>" class="smlms-btn-start-course">START COURSE</a>
                    <?php else: ?>
                        <div class="smlms-card-price-display"><?php echo esc_html($formatted_price); ?></div>
                        <?php if ($access_type === 'free'): ?>
                            <?php 
                            $free_url = wp_nonce_url(
                                add_query_arg(['smlms_action' => 'free_enroll', 'course_id' => $course_id], get_permalink($course_id)),
                                'smlms_free_enroll_' . $course_id
                            );
                            ?>
                            <a href="<?php echo esc_url($user_id ? $free_url : wp_login_url(get_permalink($course_id))); ?>" class="smlms-btn-enroll-now">
                                Enroll Now
                            </a>
                        <?php else: ?>
                            <?php 
                            $checkout_url = get_post_meta($course_id, '_smlms_custom_checkout_url', true) ?: '#';
                            ?>
                            <a href="<?php echo esc_url($checkout_url); ?>" class="smlms-btn-enroll-now">
                                Enroll Now
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <button type="button" class="smlms-btn-share-course" onclick="navigator.clipboard.writeText(window.location.href); alert('Course link copied to clipboard!');">
                        SHARE <span class="dashicons dashicons-share"></span>
                    </button>
                </div>
            </div>

        </div>
    </section>

    <!-- Navigation Tabs Bar -->
    <div class="smlms-course-nav-tabs">
        <div class="smlms-tabs-container">
            <button type="button" class="smlms-tab-btn active" data-target="#smlms-pane-curriculum">Curriculum</button>
            <button type="button" class="smlms-tab-btn" data-target="#smlms-pane-overview">Overview</button>
            <button type="button" class="smlms-tab-btn" data-target="#smlms-pane-instructors">Instructors</button>
            <button type="button" class="smlms-tab-btn" data-target="#smlms-pane-reviews">Reviews</button>
        </div>
    </div>

    <!-- Main Body Content Area -->
    <div class="smlms-course-body-container">
        <div class="smlms-main-column">
            
            <!-- 1. Curriculum Tab Pane -->
            <div id="smlms-pane-curriculum" class="smlms-tab-pane active">
                
                <!-- Collapse All Header Row -->
                <div class="smlms-curriculum-header-row">
                    <h2 class="smlms-curriculum-title">Course Content</h2>
                    <button type="button" class="smlms-btn-collapse-all" id="smlms-toggle-all-lessons">
                        <span class="dashicons dashicons-arrow-up-alt2 smlms-toggle-icon"></span>
                        <span class="smlms-toggle-text">Collapse All</span>
                    </button>
                </div>

                <div class="smlms-curriculum-wrapper">
                    <?php if (!empty($hierarchy)): ?>
                        <?php foreach ($hierarchy as $l_idx => $lesson): 
                            $l_id          = $lesson['lesson_id'];
                            $l_topic_count = !empty($lesson['topics']) ? count($lesson['topics']) : 0;
                            $l_has_sample  = false;

                            if (!empty($lesson['topics'])) {
                                foreach ($lesson['topics'] as $t_chk) {
                                    if (get_post_meta($t_chk['id'], '_smlms_is_sample', true) === '1') {
                                        $l_has_sample = true;
                                        break;
                                    }
                                }
                            }
                            if (get_post_meta($l_id, '_smlms_is_sample', true) === '1') {
                                $l_has_sample = true;
                            }

                            $lesson_accessible = $has_access || ($access_type === 'open') || ($access_type === 'free' && $is_enrolled) || $l_has_sample;
                        ?>
                            <div class="smlms-lesson-card">
                                <div class="smlms-lesson-card-header">
                                    <div class="smlms-lesson-title-wrap">
                                        <span class="dashicons dashicons-desktop smlms-lesson-icon"></span>

                                        <div class="smlms-lesson-title-details">
                                            <div class="smlms-lesson-title-text">
                                                <?php if ($lesson_accessible): ?>
                                                    <a href="<?php echo esc_url($lesson['permalink']); ?>" class="smlms-lesson-link">
                                                        <?php echo ($l_idx + 1) . '. ' . esc_html($lesson['lesson_title']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="smlms-lesson-locked-title">
                                                        <?php echo ($l_idx + 1) . '. ' . esc_html($lesson['lesson_title']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="smlms-lesson-sub-meta smlms-mobile-sub-meta">
                                                <?php if ($l_has_sample && !$has_access): ?>
                                                    <span class="smlms-badge-sample"><span class="dashicons dashicons-lock"></span> SAMPLE LESSON</span>
                                                <?php endif; ?>

                                                <?php if ($l_topic_count > 0): ?>
                                                    <span class="smlms-topic-count"><?php echo esc_html($l_topic_count); ?> <?php echo ($l_topic_count === 1) ? 'Topic' : 'Topics'; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="smlms-lesson-meta-wrap">
                                        <div class="smlms-lesson-sub-meta smlms-desktop-sub-meta">
                                            <?php if ($l_has_sample && !$has_access): ?>
                                                <span class="smlms-badge-sample"><span class="dashicons dashicons-lock"></span> SAMPLE LESSON</span>
                                            <?php endif; ?>

                                            <?php if ($l_topic_count > 0): ?>
                                                <span class="smlms-topic-count"><?php echo esc_html($l_topic_count); ?> <?php echo ($l_topic_count === 1) ? 'Topic' : 'Topics'; ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($lesson['duration']) && $lesson['duration'] !== '0.00' && $lesson['duration'] !== '0'): ?>
                                            <span class="smlms-lesson-duration"><?php echo esc_html($lesson['duration']); ?></span>
                                        <?php endif; ?>

                                        <?php if (!$lesson_accessible): ?>
                                            <div class="smlms-tooltip-holder">
                                                <span class="dashicons dashicons-lock smlms-lock-icon"></span>
                                                <span class="smlms-tooltip-text">Enroll in course to unlock</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="smlms-status-circle-icon"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($lesson['topics'])): ?>
                                    <div class="smlms-lesson-card-body">
                                        <ul class="smlms-topic-list-group">
                                            <?php foreach ($lesson['topics'] as $t_idx => $topic): 
                                                $t_id     = $topic['id'];
                                                $t_sample = (get_post_meta($t_id, '_smlms_is_sample', true) === '1');
                                                $t_access = $has_access || ($access_type === 'open') || ($access_type === 'free' && $is_enrolled) || $t_sample || $l_has_sample;
                                            ?>
                                                <li class="smlms-topic-item" data-href="<?php echo $t_access ? esc_url($topic['permalink']) : ''; ?>">
                                                    <div class="smlms-topic-title-wrap">
                                                        <span class="smlms-topic-play-icon">
                                                            <span class="dashicons dashicons-controls-play"></span>
                                                        </span>

                                                        <div class="smlms-topic-title-details">
                                                            <?php if ($t_access): ?>
                                                                <a href="<?php echo esc_url($topic['permalink']); ?>" class="smlms-topic-link">
                                                                    <?php echo ($l_idx + 1) . '.' . ($t_idx + 1) . ' ' . esc_html($topic['title']); ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="smlms-topic-locked-title">
                                                                    <?php echo ($l_idx + 1) . '.' . ($t_idx + 1) . ' ' . esc_html($topic['title']); ?>
                                                                </span>
                                                            <?php endif; ?>

                                                            <?php if ($t_sample && !$has_access): ?>
                                                                <span class="smlms-badge-sample">SAMPLE</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <div class="smlms-topic-meta-wrap">
                                                        <?php if (!empty($topic['duration']) && $topic['duration'] !== '0.00' && $topic['duration'] !== '0'): ?>
                                                            <span class="smlms-topic-duration"><?php echo esc_html($topic['duration']); ?></span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!$t_access): ?>
                                                            <div class="smlms-tooltip-holder">
                                                                <span class="dashicons dashicons-lock smlms-lock-icon"></span>
                                                                <span class="smlms-tooltip-text">Enroll in course to unlock</span>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="smlms-status-circle-icon"></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="smlms-no-reviews-msg">No course curriculum published yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Overview Tab Pane -->
            <div id="smlms-pane-overview" class="smlms-tab-pane">
                <div class="smlms-overview-content-body">
                    <?php 
                    while (have_posts()) : the_post();
                        the_content();
                    endwhile;
                    ?>
                </div>
            </div>

            <!-- 3. Instructors Tab Pane -->
            <div id="smlms-pane-instructors" class="smlms-tab-pane">
                <div class="smlms-instructor-card-box">
                    <div class="smlms-instructor-header">
                        <img src="<?php echo esc_url(get_avatar_url(get_the_author_meta('ID'), ['size' => 80])); ?>" class="smlms-instructor-avatar" alt="Instructor">
                        <div>
                            <h3 class="smlms-instructor-name"><?php echo esc_html(get_the_author()); ?></h3>
                        </div>
                    </div>
                    <p class="smlms-instructor-bio"><?php echo esc_html(get_the_author_meta('description') ?: 'Course Instructor & Engineering Expert.'); ?></p>
                </div>
            </div>

            <!-- 4. Reviews Tab Pane -->
            <div id="smlms-pane-reviews" class="smlms-tab-pane">
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

</div>

<!-- Video Pop-up Modal Lightbox -->
<div id="smlms-video-modal" class="smlms-video-modal">
    <div class="smlms-video-modal-backdrop"></div>
    <div class="smlms-video-modal-dialog">
        <button type="button" class="smlms-modal-close-btn">&times;</button>
        <div class="smlms-video-responsive-wrap">
            <iframe id="smlms-modal-iframe" src="" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Single Course Tab Switching
    $(document).on('click', '.smlms-tab-btn', function(e) {
        e.preventDefault();
        var targetPane = $(this).attr('data-target');

        $('.smlms-tab-btn').removeClass('active');
        $('.smlms-tab-pane').removeClass('active').hide();

        $(this).addClass('active');
        $(targetPane).addClass('active').show();
    });

    // Accordion Slide Toggle
    $(document).on('click', '.smlms-lesson-card-header', function() {
        var cardBody = $(this).siblings('.smlms-lesson-card-body');
        cardBody.slideToggle(150);
    });

    // Entire Topic Row Click Navigation
    $(document).on('click', '.smlms-topic-item', function(e) {
        if ($(e.target).is('a') || $(e.target).parents('a').length) {
            return;
        }
        var href = $(this).attr('data-href');
        if (href) {
            window.location.href = href;
        }
    });

    // Toggle All Lessons (Collapse All / Expand All)
    var allCollapsed = false;
    $(document).on('click', '#smlms-toggle-all-lessons', function(e) {
        e.preventDefault();
        if (!allCollapsed) {
            $('.smlms-lesson-card-body').slideUp(150);
            $(this).find('.smlms-toggle-text').text('Expand All');
            $(this).find('.smlms-toggle-icon').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            allCollapsed = true;
        } else {
            $('.smlms-lesson-card-body').slideDown(150);
            $(this).find('.smlms-toggle-text').text('Collapse All');
            $(this).find('.smlms-toggle-icon').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            allCollapsed = false;
        }
    });

    // Video Pop-up Modal Lightbox Trigger
    $(document).on('click', '.smlms-card-preview-media, .smlms-play-overlay', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var embedWrap = $(this).closest('.smlms-card-preview-media');
        var src = embedWrap.attr('data-embed');
        if (src) {
            if (src.indexOf('autoplay=1') === -1) {
                src += (src.indexOf('?') > -1 ? '&' : '?') + 'autoplay=1';
            }
            $('#smlms-modal-iframe').attr('src', src);
            $('#smlms-video-modal').addClass('active');
        }
    });

    $(document).on('click', '.smlms-modal-close-btn, .smlms-video-modal-backdrop', function(e) {
        e.preventDefault();
        $('#smlms-video-modal').removeClass('active');
        $('#smlms-modal-iframe').attr('src', '');
    });
});
</script>

<?php
get_footer();