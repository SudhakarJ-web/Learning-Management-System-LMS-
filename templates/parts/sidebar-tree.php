<?php
/**
 * Sidebar Navigation Hierarchy Part
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id   = get_current_user_id();
$hierarchy = SMLMS_DB::get_course_hierarchy($course_id, $user_id);
$total_steps_count = 0;
$completed_steps_count = 0;
?>

<nav class="smlms-course-navigation">
    <div class="smlms-lesson-navigation">
        <?php if (empty($hierarchy)): ?>
            <p class="smlms-no-lessons">No published lessons available.</p>
        <?php else: ?>
            <?php foreach ($hierarchy as $l_index => $lesson): 
                $topics = $lesson['topics'];
                $topic_count = count($topics);
                $total_steps_count += $topic_count;

                // Check if current lesson contains active topic
                $contains_active = false;
                foreach ($topics as $t) {
                    if ($t['id'] == $current_topic_id) {
                        $contains_active = true;
                    }
                    if ($t['is_completed']) {
                        $completed_steps_count++;
                    }
                }
                $expanded_class = $contains_active ? 'expanded' : '';
            ?>
                <div class="smlms-lesson-item <?php echo $contains_active ? 'ld-is-current-lesson' : ''; ?>">
                    <div class="smlms-lesson-item-preview">
                        <a href="<?php echo esc_url(get_permalink($lesson['lesson_id'])); ?>" class="smlms-lesson-heading">
                            <span class="smlms-status-circle"></span>
                            <span class="smlms-lesson-title-text"><?php echo ($l_index + 1) . '. ' . esc_html($lesson['lesson_title']); ?></span>
                        </a>

                        <?php if ($topic_count > 0): ?>
                            <button type="button" class="smlms-expand-btn <?php echo $expanded_class; ?>" title="Expand Topics">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <span class="smlms-expand-text"><?php echo $topic_count; ?> Topics</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="smlms-topic-list-wrapper" style="<?php echo $contains_active ? 'display: block;' : 'display: none;'; ?>">
                        <ul class="smlms-topic-table-items">
                            <?php foreach ($topics as $t_index => $topic): 
                                $is_active = ($topic['id'] == $current_topic_id) ? 'ld-is-current-item' : '';
                                $is_completed = $topic['is_completed'] ? 'is-completed' : '';
                            ?>
                                <li class="smlms-topic-table-item <?php echo $is_active; ?> <?php echo $is_completed; ?>">
                                    <a href="<?php echo esc_url($topic['permalink']); ?>" class="smlms-topic-row-link">
                                        <span class="smlms-topic-status-icon"></span>
                                        <span class="smlms-topic-row-title"><?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic['title']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var totalSteps = <?php echo $total_steps_count; ?>;
        var completedSteps = <?php echo $completed_steps_count; ?>;
        var percent = totalSteps > 0 ? Math.round((completedSteps / totalSteps) * 100) : 0;

        var pctText = document.getElementById('smlms-progress-percent-text');
        var stpText = document.getElementById('smlms-progress-steps-text');
        var barFill = document.getElementById('smlms-progress-fill');

        if (pctText) pctText.textContent = percent + '% Complete';
        if (stpText) stpText.textContent = completedSteps + '/' + totalSteps + ' Steps';
        if (barFill) barFill.style.width = percent + '%';
    });
</script>