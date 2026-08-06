<?php
/**
 * Topic & Lesson Stage Content Part
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id      = get_the_ID();
$post_type    = get_post_type($post_id);
$video_id     = get_post_meta($post_id, '_smlms_video_id', true);
$materials    = get_post_meta($post_id, '_smlms_materials', true);
$status_label = 'IN PROGRESS';

$course_id    = SMLMS_DB::get_parent_course_id($post_id);
$course_post  = get_post($course_id);
$course_title = $course_post ? $course_post->post_title : 'Course';

if ($post_type === 'smlms_topic') {
    $lesson_id   = get_post_meta($post_id, '_smlms_parent_lesson_id', true);
    $lesson_post = get_post($lesson_id);
    $lesson_title= $lesson_post ? $lesson_post->post_title : '';
} else {
    $lesson_id    = $post_id;
    $lesson_title = get_the_title($post_id);
}

// Fetch sub-topics if viewing a Lesson
$sub_topics = [];
if ($post_type === 'smlms_lesson') {
    $sub_topics = get_posts([
        'post_type'      => 'smlms_topic',
        'posts_per_page' => -1,
        'meta_key'       => '_smlms_parent_lesson_id',
        'meta_value'     => $post_id,
        'orderby'        => 'menu_order',
        'order'          => 'ASC'
    ]);
}
?>

<div class="smlms-focus-stage-content">
    
    <!-- Stage Breadcrumbs & Status Tag -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <nav class="smlms-focus-breadcrumbs" style="font-size: 13px; color: #64748b;">
            <a href="<?php echo esc_url(get_permalink($course_id)); ?>" style="color: #0284c7; text-decoration: none; font-weight: bold;"><?php echo esc_html($course_title); ?></a> &gt; 
            <?php if ($post_type === 'smlms_topic'): ?>
                <a href="<?php echo esc_url(get_permalink($lesson_id)); ?>" style="color: #0284c7; text-decoration: none;"><?php echo esc_html($lesson_title); ?></a> &gt; 
            <?php endif; ?>
            <span><?php the_title(); ?></span>
        </nav>

        <span style="background-color: #00a2e8; color: #ffffff; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
            <?php echo esc_html($status_label); ?>
        </span>
    </div>

    <!-- Title -->
    <h1 style="font-size: 26px; margin-top: 0; margin-bottom: 25px; color: #0f172a; font-weight: 700;"><?php the_title(); ?></h1>

    <!-- Tabs Header -->
    <div class="smlms-stage-tabs-header">
        <button type="button" class="smlms-stage-tab-btn active" data-target="#smlms-tab-content-pane">
            <span class="dashicons dashicons-welcome-learn-more" style="vertical-align: middle;"></span> <?php echo ($post_type === 'smlms_lesson') ? 'Lesson' : 'Topic'; ?>
        </button>
        <button type="button" class="smlms-stage-tab-btn" data-target="#smlms-tab-materials-pane">
            <span class="dashicons dashicons-paperclip" style="vertical-align: middle;"></span> Materials
        </button>
    </div>

    <!-- Tab Panels Body -->
    <div class="smlms-stage-tabs-body">
        
        <!-- Tab 1: Video / Content Stage -->
        <div id="smlms-tab-content-pane" class="smlms-stage-tab-pane active">
            <div class="smlms-video-container">
                <?php if (!empty($video_id)): ?>
                    <?php if (is_numeric($video_id)): ?>
                        <iframe src="https://player.vimeo.com/video/<?php echo esc_attr($video_id); ?>" 
                                width="100%" height="450" frameborder="0" 
                                allow="autoplay; fullscreen" allowfullscreen>
                        </iframe>
                    <?php else: ?>
                        <div style="padding: 10px; background: #000;">
                            <?php echo wp_oembed_get($video_id) ?: $video_id; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="smlms-topic-editor-text" style="margin-top: 20px; line-height: 1.6; font-size: 15px; color: #334155;">
                <?php the_content(); ?>
            </div>

            <!-- LearnDash Replica Sub-Topics List (When viewing a Lesson) -->
            <?php if ($post_type === 'smlms_lesson' && !empty($sub_topics)): ?>
                <div style="margin-top: 35px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #0f172a; font-size: 16px;">
                        Topics in this Lesson
                    </div>
                    <ul style="list-style: none; margin: 0; padding: 0;">
                        <?php foreach ($sub_topics as $t_idx => $st): 
                            $st_duration = get_post_meta($st->ID, '_smlms_duration', true) ?: '0.00';
                        ?>
                            <li style="border-bottom: 1px solid #f1f5f9;">
                                <a href="<?php echo esc_url(get_permalink($st->ID)); ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; color: #334155; text-decoration: none; font-size: 14px; font-weight: 600;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="dashicons dashicons-controls-play" style="color: #0284c7;"></span>
                                        <span><?php echo esc_html($st->post_title); ?></span>
                                    </div>
                                    <span style="color: #64748b; font-size: 13px; font-weight: normal;"><?php echo esc_html($st_duration); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Materials -->
        <div id="smlms-tab-materials-pane" class="smlms-stage-tab-pane">
            <div style="background: #ffffff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px;">
                <?php echo !empty($materials) ? wp_kses_post($materials) : '<p style="color: #64748b; margin: 0;">No supplementary materials provided.</p>'; ?>
            </div>
        </div>

    </div>

    <!-- Bottom Navigation Controls -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" style="color: #0284c7; text-decoration: none; font-weight: 600; font-size: 14px;">
            &larr; Back to Course
        </a>
        <button type="button" class="smlms-btn-action smlms-btn-complete" onclick="alert('Step Marked Complete!');" style="background: #019e7c; color: #fff; padding: 10px 24px; border-radius: 20px; border: none; font-weight: bold; cursor: pointer;">
            Mark Complete &#10004;
        </button>
    </div>

</div>