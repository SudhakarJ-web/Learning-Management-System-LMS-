<?php
/**
 * Topic Stage Content Part
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="smlms-focus-stage-content">
    
    <!-- Breadcrumbs -->
    <nav class="smlms-focus-breadcrumbs">
        <a href="<?php echo esc_url(get_permalink($course_id)); ?>"><?php echo esc_html(get_the_title($course_id)); ?></a> &gt; 
        <a href="<?php echo esc_url(get_permalink($lesson_id)); ?>"><?php echo esc_html(get_the_title($lesson_id)); ?></a> &gt; 
        <span><?php the_title(); ?></span>
    </nav>

    <!-- Topic Main Title -->
    <h1 class="smlms-topic-stage-title"><?php the_title(); ?></h1>

    <!-- Tabs Navigation Bar -->
    <div class="smlms-stage-tabs-header">
        <button type="button" class="smlms-stage-tab-btn active" data-target="#smlms-tab-topic">
            <span class="dashicons dashicons-welcome-learn-more"></span> Topic
        </button>
        <button type="button" class="smlms-stage-tab-btn" data-target="#smlms-tab-materials">
            <span class="dashicons dashicons-paperclip"></span> Materials
        </button>
    </div>

    <!-- Tab Panels Body -->
    <div class="smlms-stage-tabs-body">
        
        <!-- Tab 1: Video Player Stage -->
        <div id="smlms-tab-topic" class="smlms-stage-tab-pane active">
            <div class="smlms-video-container">
                <?php if ($video_id): ?>
                    <iframe src="https://player.vimeo.com/video/<?php echo esc_attr($video_id); ?>" 
                            width="100%" height="450" frameborder="0" 
                            allow="autoplay; fullscreen" allowfullscreen>
                    </iframe>
                <?php else: ?>
                    <div class="smlms-no-video-placeholder">
                        <p>No video attached to this topic yet.</p>
                    </div>
                <?php endif; ?>

                <!-- Dynamic Watermark -->
                <div class="smlms-watermark-overlay" id="smlms-watermark">
                    <span><?php echo esc_html($current_user->user_email); ?> | IP: <?php echo esc_html($_SERVER['REMOTE_ADDR']); ?></span>
                </div>
            </div>

            <div class="smlms-topic-editor-text">
                <?php the_content(); ?>
            </div>
        </div>

        <!-- Tab 2: Materials & Links -->
        <div id="smlms-tab-materials" class="smlms-stage-tab-pane">
            <div class="smlms-materials-box">
                <?php echo !empty($materials) ? wp_kses_post($materials) : '<p>No supplementary materials for this topic.</p>'; ?>
            </div>
        </div>

    </div>

    <!-- Bottom Navigation Bar -->
    <footer class="smlms-bottom-navigation-bar">
        <button type="button" class="smlms-btn-nav smlms-btn-prev-bottom" id="smlms-bottom-prev-btn">&larr; Previous Lesson</button>
        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="smlms-link-back-course">Back to Course</a>
        <button type="button" class="smlms-btn-nav smlms-btn-next-bottom" id="smlms-bottom-next-btn">Next Topic &rarr;</button>
    </footer>

</div>