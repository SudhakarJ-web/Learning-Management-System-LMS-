<?php
/**
 * Sabin Mathew Custom Meta Box (For Lessons and Topics)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Item_Custom {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_box']);
    }

    public static function register_meta_box() {
        add_meta_box(
            'smlms_item_custom_meta',
            'Sabin Mathew Custom Meta',
            [__CLASS__, 'render_meta_box'],
            ['smlms_lesson', 'smlms_topic'],
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('smlms_item_custom_nonce_action', 'smlms_item_custom_nonce');

        $is_sample    = get_post_meta($post->ID, '_smlms_is_sample', true);
        $duration     = get_post_meta($post->ID, '_smlms_duration', true);
        $content_type = get_post_meta($post->ID, '_smlms_content_type', true);
        ?>

        <table class="form-table smlms-custom-meta-table">
            <tr>
                <th scope="row"><label for="smlms_is_sample">Sample Lesson</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="smlms_is_sample" id="smlms_is_sample" value="1" <?php checked($is_sample, '1'); ?>>
                        Allow Sample Preview (Non-enrolled users can view this lesson and all its topics in Focus Mode)
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smlms_duration">Estimated Duration</label></th>
                <td>
                    <input type="text" name="smlms_duration" id="smlms_duration" class="regular-text" value="<?php echo esc_attr($duration); ?>" placeholder="Leave empty for none (e.g. 5.00)">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smlms_content_type">Content Type</label></th>
                <td>
                    <select name="smlms_content_type" id="smlms_content_type" class="regular-text">
                        <option value="" <?php selected($content_type, ''); ?>>None</option>
                        <option value="video" <?php selected($content_type, 'video'); ?>>Video</option>
                        <option value="presentation" <?php selected($content_type, 'presentation'); ?>>Presentation</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
}

SMLMS_Meta_Item_Custom::init();