<?php
/**
 * Sabin Mathew Course Custom Meta Box
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Course_Custom {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_box']);
    }

    public static function register_meta_box() {
        add_meta_box(
            'smlms_course_custom_meta',
            'Sabin Mathew Course Custom Meta',
            [__CLASS__, 'render_meta_box'],
            'smlms_course',
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('smlms_course_custom_nonce_action', 'smlms_course_custom_nonce');

        $headline     = get_post_meta($post->ID, '_smlms_course_headline', true) ?: '';
        $short_desc   = get_post_meta($post->ID, '_smlms_course_short_desc', true) ?: '';
        $duration     = get_post_meta($post->ID, '_smlms_duration', true) ?: '2 Weeks';
        $level        = get_post_meta($post->ID, '_smlms_level', true) ?: 'Advanced';
        $language     = get_post_meta($post->ID, '_smlms_language', true) ?: 'English';
        $num_lessons  = get_post_meta($post->ID, '_smlms_number_of_lessons', true) ?: '';
        $enrolled     = get_post_meta($post->ID, '_smlms_students_enrolled', true) ?: '1';
        $content_type = get_post_meta($post->ID, '_smlms_content_type', true) ?: 'video';
        $media_embed  = get_post_meta($post->ID, '_smlms_media_embed', true) ?: '';
        ?>

        <table class="form-table smlms-meta-table">
            <tr>
                <th scope="row"><label for="_smlms_course_headline">Course Headline</label></th>
                <td>
                    <input type="text" id="_smlms_course_headline" name="_smlms_course_headline" value="<?php echo esc_attr($headline); ?>" class="widefat">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="_smlms_course_short_desc">Course Short Description</label></th>
                <td>
                    <textarea id="_smlms_course_short_desc" name="_smlms_course_short_desc" class="widefat" rows="3"><?php echo esc_textarea($short_desc); ?></textarea>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="_smlms_duration">Estimated Duration</label></th>
                <td>
                    <input type="text" id="_smlms_duration" name="_smlms_duration" value="<?php echo esc_attr($duration); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="_smlms_level">Course Level</label></th>
                <td>
                    <select id="_smlms_level" name="_smlms_level" class="regular-text">
                        <option value="Beginner" <?php selected($level, 'Beginner'); ?>>Beginner</option>
                        <option value="Intermediate" <?php selected($level, 'Intermediate'); ?>>Intermediate</option>
                        <option value="Advanced" <?php selected($level, 'Advanced'); ?>>Advanced</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="_smlms_language">Course Language</label></th>
                <td>
                    <input type="text" id="_smlms_language" name="_smlms_language" value="<?php echo esc_attr($language); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="_smlms_number_of_lessons">Number of Lessons</label></th>
                <td>
                    <input type="text" id="_smlms_number_of_lessons" name="_smlms_number_of_lessons" value="<?php echo esc_attr($num_lessons); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="_smlms_students_enrolled">Students Enrolled</label></th>
                <td>
                    <input type="text" id="_smlms_students_enrolled" name="_smlms_students_enrolled" value="<?php echo esc_attr($enrolled); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="_smlms_content_type">Content Type</label></th>
                <td>
                    <select id="_smlms_content_type" name="_smlms_content_type" class="regular-text">
                        <option value="video" <?php selected($content_type, 'video'); ?>>Video</option>
                        <option value="presentation" <?php selected($content_type, 'presentation'); ?>>Presentation</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="_smlms_media_embed">Media Embed / Video Preview URL</label></th>
                <td>
                    <textarea id="_smlms_media_embed" name="_smlms_media_embed" class="widefat" rows="2" placeholder="e.g. Vimeo Video ID or YouTube Embed URL"><?php echo esc_textarea($media_embed); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }
}
SMLMS_Meta_Course_Custom::init();