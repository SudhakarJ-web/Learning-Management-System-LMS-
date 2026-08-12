<?php
/**
 * Focus Mode - Sidebar Hierarchy Tree Template
 */

if (!defined('ABSPATH')) exit;

$current_page_id = get_the_ID();
$course_id       = $context['course_id'] ?? SMLMS_DB::get_parent_course_id($current_page_id);
$user_id         = get_current_user_id();
$is_enrolled     = $user_id ? SMLMS_DB::is_user_enrolled($user_id, $course_id) : false;
$access_type     = $course_id ? (get_post_meta($course_id, '_smlms_access_type', true) ?: 'closed') : 'closed';
$hierarchy       = $course_id ? SMLMS_DB::get_course_hierarchy($course_id, $user_id) : [];
?>

<div class="smlms-sidebar-tree-wrapper">
    <?php if (!empty($hierarchy)): ?>
        <ul class="smlms-sidebar-nav-list">
            <?php foreach ($hierarchy as $l_index => $lesson): 
                $lesson_id            = $lesson['lesson_id'];
                $lesson_title         = $lesson['lesson_title'];
                $lesson_url           = $lesson['permalink'];
                $topics               = $lesson['topics'] ?? [];
                $has_topics           = !empty($topics);
                $is_current_lesson    = ($current_page_id === $lesson_id);
                $is_sample_l          = get_post_meta($lesson_id, '_smlms_is_sample', true) === '1';
                $can_view_lesson      = ($access_type === 'open') || current_user_can('manage_options') || $is_enrolled || $is_sample_l;
                
                // Check if any child topic is currently active
                $has_active_child = false;
                if ($has_topics) {
                    foreach ($topics as $t) {
                        if ($t['id'] === $current_page_id) {
                            $has_active_child = true;
                            break;
                        }
                    }
                }
            ?>
                <li class="smlms-sidebar-lesson-item <?php echo $has_active_child ? 'child-active' : ''; ?>">
                    <div class="smlms-sidebar-row <?php echo $is_current_lesson ? 'active-step' : ''; ?>">
                        <div class="smlms-sidebar-row-left">
                            <span class="smlms-status-circle <?php echo $is_current_lesson ? 'active-circle' : ''; ?>"></span>
                            
                            <?php if ($can_view_lesson): ?>
                                <a href="<?php echo esc_url($lesson_url); ?>" class="smlms-sidebar-title-link <?php echo $is_current_lesson ? 'active-text' : ''; ?>">
                                    <?php echo ($l_index + 1) . '. ' . esc_html($lesson_title); ?>
                                </a>
                            <?php else: ?>
                                <span class="smlms-sidebar-locked-title">
                                    <?php echo ($l_index + 1) . '. ' . esc_html($lesson_title); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="smlms-sidebar-row-right">
                            <?php if (!$can_view_lesson): ?>
                                <span class="dashicons dashicons-lock smlms-sidebar-lock-icon"></span>
                            <?php endif; ?>

                            <?php if ($has_topics): ?>
                                <button type="button" class="smlms-sidebar-toggle-btn <?php echo ($is_current_lesson || $has_active_child) ? 'open' : ''; ?>" title="Toggle Topics">
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Child Topics Sub-list -->
                    <?php if ($has_topics): ?>
                        <ul class="smlms-sidebar-topic-sublist" style="<?php echo ($is_current_lesson || $has_active_child) ? 'display: block;' : 'display: none;'; ?>">
                            <?php foreach ($topics as $t_index => $topic): 
                                $topic_id         = $topic['id'];
                                $topic_title      = $topic['title'];
                                $topic_url        = $topic['permalink'];
                                $is_current_topic = ($current_page_id === $topic_id);
                                $can_view_topic   = ($access_type === 'open') || current_user_can('manage_options') || $is_enrolled || $is_sample_l;
                            ?>
                                <li class="smlms-sidebar-topic-item">
                                    <div class="smlms-sidebar-row smlms-topic-row <?php echo $is_current_topic ? 'active-step' : ''; ?>">
                                        <div class="smlms-sidebar-row-left">
                                            <span class="smlms-status-circle <?php echo $is_current_topic ? 'active-circle' : ''; ?>"></span>
                                            
                                            <?php if ($can_view_topic): ?>
                                                <a href="<?php echo esc_url($topic_url); ?>" class="smlms-sidebar-title-link <?php echo $is_current_topic ? 'active-text' : ''; ?>">
                                                    <?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic_title); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="smlms-sidebar-locked-title">
                                                    <?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic_title); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="smlms-sidebar-row-right">
                                            <?php if (!$can_view_topic): ?>
                                                <span class="dashicons dashicons-lock smlms-sidebar-lock-icon"></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>