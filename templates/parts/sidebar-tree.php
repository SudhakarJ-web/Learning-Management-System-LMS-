<?php
if (!defined('ABSPATH')) exit;

// Expects $course_id and $current_topic_id passed from parent scope
$user_id   = get_current_user_id();
$hierarchy = SMLMS_DB::get_course_hierarchy($course_id, $user_id);
?>

<div class="smlms-tree-wrapper">
    <?php if (empty($hierarchy)): ?>
        <p class="smlms-empty-notice">No lessons published for this course yet.</p>
    <?php else: ?>
        <?php foreach ($hierarchy as $lesson_index => $lesson): ?>
            <div class="smlms-lesson-group">
                <div class="smlms-lesson-title">
                    <span>Lesson <?php echo $lesson_index + 1; ?>: <?php echo esc_html($lesson['lesson_title']); ?></span>
                </div>
                <ul class="smlms-topic-list">
                    <?php foreach ($lesson['topics'] as $topic): 
                        $is_active = ($topic['id'] == $current_topic_id) ? 'smlms-topic-active' : '';
                        $is_completed = $topic['is_completed'] ? 'smlms-topic-completed' : '';
                    ?>
                        <li class="smlms-topic-item <?php echo $is_active; ?> <?php echo $is_completed; ?>">
                            <a href="<?php echo esc_url($topic['permalink']); ?>" 
                               class="smlms-topic-link" 
                               data-topic-id="<?php echo esc_attr($topic['id']); ?>">
                                <span class="smlms-status-icon">
                                    <?php echo $topic['is_completed'] ? '&#10004;' : '&#9654;'; ?>
                                </span>
                                <span class="smlms-topic-text"><?php echo esc_html($topic['title']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>