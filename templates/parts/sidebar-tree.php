<?php
/**
 * LearnDash Style Sidebar Navigation Tree Part
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id           = get_current_user_id();
$course_id         = !empty($course_id) ? $course_id : SMLMS_DB::get_parent_course_id(get_the_ID());
$current_topic_id  = get_the_ID();
$hierarchy         = SMLMS_DB::get_course_hierarchy($course_id, $user_id);

$total_steps_count     = 0;
$completed_steps_count = 0;
?>

<div class="smlms-ld-tree-wrapper">
    <?php if (empty($hierarchy)): ?>
        <p class="smlms-no-lessons" style="color: #94a3b8; font-size: 13px;">No published lessons or steps available.</p>
    <?php else: ?>
        <?php foreach ($hierarchy as $l_index => $lesson): 
            $topics = !empty($lesson['topics']) ? $lesson['topics'] : [];
            $topic_count = count($topics);
            $total_steps_count += $topic_count;

            $contains_active = ($lesson['lesson_id'] == $current_topic_id);
            foreach ($topics as $t) {
                if ($t['id'] == $current_topic_id) {
                    $contains_active = true;
                }
                if (!empty($t['is_completed'])) {
                    $completed_steps_count++;
                }
            }
        ?>
            <div class="smlms-ld-lesson-card <?php echo $contains_active ? 'smlms-active-lesson' : ''; ?>">
                <div class="smlms-ld-lesson-header">
                    <a href="<?php echo esc_url($lesson['permalink']); ?>" class="smlms-ld-lesson-link">
                        <span class="smlms-circle-status"></span>
                        <span class="smlms-ld-lesson-title-text"><?php echo ($l_index + 1) . '. ' . esc_html($lesson['lesson_title']); ?></span>
                    </a>

                    <?php if ($topic_count > 0): ?>
                        <button type="button" class="smlms-ld-toggle-btn <?php echo $contains_active ? 'active' : ''; ?>">
                            &#9660;
                        </button>
                    <?php endif; ?>
                </div>

                <div class="smlms-ld-topics-wrapper" style="<?php echo $contains_active ? 'display: block;' : 'display: none;'; ?>">
                    <ul class="smlms-ld-topic-list">
                        <?php foreach ($topics as $t_index => $topic): 
                            $is_active = ($topic['id'] == $current_topic_id) ? 'smlms-current-topic' : '';
                            $is_completed = !empty($topic['is_completed']) ? 'is-completed' : '';
                        ?>
                            <li class="smlms-ld-topic-item <?php echo $is_active; ?> <?php echo $is_completed; ?>">
                                <a href="<?php echo esc_url($topic['permalink']); ?>" class="smlms-ld-topic-link">
                                    <span class="smlms-circle-status smlms-topic-circle"></span>
                                    <span class="smlms-topic-title-text"><?php echo ($l_index + 1) . '.' . ($t_index + 1) . ' ' . esc_html($topic['title']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var totalSteps = <?php echo $total_steps_count; ?>;
        var completedSteps = <?php echo $completed_steps_count; ?>;
        var percent = totalSteps > 0 ? Math.round((completedSteps / totalSteps) * 100) : 0;

        var pctText = document.getElementById('smlms-progress-percent-text');
        var stpText = document.getElementById('smlms-progress-steps-text');
        var barFill = document.getElementById('smlms-progress-fill');

        if (pctText) pctText.textContent = percent + '% COMPLETE';
        if (stpText) stpText.textContent = completedSteps + '/' + totalSteps + ' Steps';
        if (barFill) barFill.style.width = percent + '%';
    });
</script>