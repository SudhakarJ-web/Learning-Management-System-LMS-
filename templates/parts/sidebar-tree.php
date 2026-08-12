<?php
/**
 * Focus Mode Sidebar Curriculum Tree Part (LearnDash Style)
 */

if (!defined('ABSPATH')) exit;

$current_post_id = get_the_ID();
$course_id       = isset($course_id) && $course_id ? $course_id : SMLMS_DB::get_parent_course_id($current_post_id);
$user_id         = get_current_user_id();
$hierarchy       = $course_id ? SMLMS_DB::get_course_hierarchy($course_id, $user_id) : [];
?>

<div class="smlms-sidebar-tree-wrapper">
    <?php if (empty($hierarchy)): ?>
        <p style="padding: 15px; color: #64748b; font-size: 13px;">No curriculum steps available.</p>
    <?php else: ?>
        <div class="smlms-tree-lesson-cards">
            <?php foreach ($hierarchy as $l_index => $lesson): 
                $is_current_lesson = ($lesson['lesson_id'] == $current_post_id);
                $has_topics        = !empty($lesson['topics']);
                $is_active_branch  = $is_current_lesson;

                if ($has_topics && !$is_active_branch) {
                    foreach ($lesson['topics'] as $topic) {
                        if ($topic['id'] == $current_post_id) {
                            $is_active_branch = true;
                            break;
                        }
                    }
                }
            ?>
                <div class="smlms-tree-lesson-card <?php echo $is_active_branch ? 'active-branch' : ''; ?>">
                    <div class="smlms-tree-lesson-header <?php echo $is_current_lesson ? 'current-step' : ''; ?>">
                        <a href="<?php echo esc_url($lesson['permalink']); ?>" class="smlms-tree-lesson-link">
                            <span class="smlms-status-circle"></span>
                            <span class="smlms-tree-title"><?php echo ($l_index + 1) . '. ' . esc_html($lesson['lesson_title']); ?></span>
                        </a>

                        <?php if ($has_topics): ?>
                            <button type="button" class="smlms-sidebar-toggle-btn <?php echo $is_active_branch ? 'open' : ''; ?>">
                                &#9660;
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($has_topics): ?>
                        <div class="smlms-tree-topic-list" style="<?php echo $is_active_branch ? 'display: block;' : 'display: none;'; ?>">
                            <?php foreach ($lesson['topics'] as $t_index => $topic): 
                                $is_current_topic = ($topic['id'] == $current_post_id);
                            ?>
                                <div class="smlms-tree-topic-item <?php echo $is_current_topic ? 'current-step' : ''; ?>">
                                    <a href="<?php echo esc_url($topic['permalink']); ?>" class="smlms-tree-topic-link">
                                        <span class="smlms-status-circle small"></span>
                                        <span class="smlms-tree-title"><?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic['title']); ?></span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>