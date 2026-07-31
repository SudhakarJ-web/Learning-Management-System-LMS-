<?php if (!defined('ABSPATH')) exit; ?>

<div class="smlms-dashboard-wrapper">
    <h2>My Enrolled Courses</h2>

    <?php if (empty($enrolled_courses)): ?>
        <p>You are not enrolled in any courses yet.</p>
    <?php else: ?>
        <div class="smlms-course-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($enrolled_courses as $course): ?>
                <div class="smlms-course-card" style="border: 1px solid #ccc; padding: 15px; border-radius: 8px;">
                    <h3><?php echo esc_html($course->course_title); ?></h3>
                    <p><strong>Enrolled:</strong> <?php echo date('M d, Y', strtotime($course->enrolled_at)); ?></p>
                    <p><strong>Completed Topics:</strong> <?php echo intval($course->completed_topics); ?></p>
                    <a href="<?php echo get_permalink($course->course_id); ?>" class="button button-primary" style="display: inline-block; margin-top: 10px;">
                        Continue Course
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>