<?php
/**
 * Single Course Landing Page - Curriculum Tab Part (LearnDash Replica)
 */

if (!defined('ABSPATH')) exit;

$hierarchy  = $context['hierarchy'] ?? [];
$has_access = $context['has_access'] ?? false;
?>

<div id="tab-curriculum" class="smlms-course-tab-pane active">
    <div class="smlms-curriculum-wrapper">
        
        <!-- Header Row with Section Title and Collapse Button -->
        <div class="smlms-curriculum-header-row">
            <h2 class="smlms-curriculum-title">Course Content</h2>
            <button type="button" id="smlms-frontend-expand-all" class="smlms-btn-collapse-all" data-collapsed="false">
                &#9650; Collapse All
            </button>
        </div>

        <?php if (empty($hierarchy)): ?>
            <p class="smlms-no-curriculum-notice">No curriculum steps have been published for this course yet.</p>
        <?php else: ?>
            <div class="smlms-curriculum-tree-container">
                <?php foreach ($hierarchy as $l_index => $lesson): 
                    $lesson_id            = $lesson['lesson_id'];
                    $lesson_title         = $lesson['lesson_title'];
                    $lesson_url           = $lesson['permalink'];
                    $topics               = $lesson['topics'] ?? [];
                    $topic_count          = count($topics);
                    $is_sample_l          = get_post_meta($lesson_id, '_smlms_is_sample', true) === '1';
                    $can_view_lesson      = $has_access || $is_sample_l;
                    $is_standalone_lesson = ($topic_count === 0);
                    
                    // Normalize Content Type
                    $raw_content_type     = strtolower(trim((string) get_post_meta($lesson_id, '_smlms_content_type', true)));
                    $lesson_type          = ($raw_content_type === 'presentation') ? 'presentation' : 'video';
                    $lesson_duration      = $lesson['duration'] ?? (get_post_meta($lesson_id, '_smlms_duration', true) ?: '5.00');
                ?>
                    <div class="smlms-lesson-card">
                        
                        <!-- Lesson Header Row -->
                        <div class="smlms-lesson-card-header">
                            <div class="smlms-lesson-title-wrap">
                                <?php if ($is_standalone_lesson): ?>
                                    <span class="smlms-topic-play-icon" title="<?php echo esc_attr(ucfirst($lesson_type)); ?>">
                                        <span class="dashicons <?php echo ($lesson_type === 'presentation') ? 'dashicons-media-interactive' : 'dashicons-controls-play'; ?>"></span>
                                    </span>
                                <?php else: ?>
                                    <span class="dashicons <?php echo ($lesson_type === 'presentation') ? 'dashicons-slides' : 'dashicons-desktop'; ?> smlms-lesson-icon" title="<?php echo esc_attr(ucfirst($lesson_type)); ?>"></span>
                                <?php endif; ?>

                                <?php if ($can_view_lesson): ?>
                                    <a href="<?php echo esc_url($lesson_url); ?>" class="smlms-lesson-link">
                                        <?php echo ($l_index + 1) . '. ' . esc_html($lesson_title); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="smlms-lesson-locked-title">
                                        <?php echo ($l_index + 1) . '. ' . esc_html($lesson_title); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="smlms-lesson-meta-wrap">
                                <?php if ($is_sample_l): ?>
                                    <span class="smlms-badge-sample">SAMPLE LESSON</span>
                                <?php endif; ?>

                                <?php if ($topic_count > 0): ?>
                                    <span class="smlms-topic-count">
                                        <?php echo $topic_count . ' ' . ($topic_count === 1 ? 'Topic' : 'Topics'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="smlms-topic-duration"><?php echo esc_html($lesson_duration); ?></span>
                                <?php endif; ?>

                                <?php if (!$can_view_lesson && !$has_access): ?>
                                    <div class="smlms-tooltip-holder">
                                        <span class="dashicons dashicons-lock smlms-lock-icon"></span>
                                        <span class="smlms-tooltip-text">You don't currently have access to this content</span>
                                    </div>
                                <?php else: ?>
                                    <span class="smlms-status-circle-icon"></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Child Topics Drawer List -->
                        <?php if (!empty($topics)): ?>
                            <div class="smlms-lesson-card-body">
                                <ul class="smlms-topic-list-group">
                                    <?php foreach ($topics as $t_index => $topic): 
                                        $topic_id          = $topic['id'];
                                        $topic_title       = $topic['title'];
                                        $topic_url         = $topic['permalink'];
                                        $duration          = $topic['duration'] ?? '5.00';
                                        
                                        $can_view_topic    = $has_access || $is_sample_l;
                                        
                                        $raw_t_type        = strtolower(trim((string) get_post_meta($topic_id, '_smlms_content_type', true)));
                                        $topic_type        = ($raw_t_type === 'presentation') ? 'presentation' : 'video';
                                        $topic_icon_inner  = ($topic_type === 'presentation') ? 'dashicons-media-interactive' : 'dashicons-controls-play';
                                    ?>
                                        <li class="smlms-topic-item">
                                            <div class="smlms-topic-title-wrap">
                                                <span class="smlms-topic-play-icon" title="<?php echo esc_attr(ucfirst($topic_type)); ?>">
                                                    <span class="dashicons <?php echo esc_attr($topic_icon_inner); ?>"></span>
                                                </span>
                                                
                                                <?php if ($can_view_topic): ?>
                                                    <a href="<?php echo esc_url($topic_url); ?>" class="smlms-topic-link">
                                                        <?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic_title); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="smlms-topic-locked-title">
                                                        <?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic_title); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="smlms-topic-meta-wrap">
                                                <span class="smlms-topic-duration"><?php echo esc_html($duration); ?></span>

                                                <?php if (!$can_view_topic && !$has_access): ?>
                                                    <div class="smlms-tooltip-holder">
                                                        <span class="dashicons dashicons-lock smlms-lock-icon"></span>
                                                        <span class="smlms-tooltip-text">You don't currently have access to this content</span>
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
            </div>
        <?php endif; ?>

    </div>
</div>