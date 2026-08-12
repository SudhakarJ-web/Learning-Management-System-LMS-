<?php
/**
 * Instructors Tab Content Part
 */

if (!defined('ABSPATH')) exit;

$course_id     = $context['course_id'];
$author_id     = get_post_field('post_author', $course_id);
$author_name   = get_the_author_meta('display_name', $author_id);
$author_avatar = get_avatar_url($author_id, ['size' => 120]);
$author_bio    = get_the_author_meta('description', $author_id);
?>

<div id="smlms-tab-instructors" class="smlms-course-tab-pane">
    <div class="smlms-instructor-card">
        <img src="<?php echo esc_url($author_avatar); ?>" class="smlms-instructor-img" alt="<?php echo esc_attr($author_name); ?>">
        <div>
            <h3 style="margin: 0; font-size: 18px; font-weight: 600;"><?php echo esc_html($author_name); ?></h3>
            <p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b; font-weight: 400;"><?php echo esc_html($author_bio ? $author_bio : 'Robotics and industrial automation professional.'); ?></p>
        </div>
    </div>
</div>