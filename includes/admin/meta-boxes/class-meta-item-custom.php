<?php
/**
 * Lesson & Topic Custom Meta Box Renderer
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
        wp_nonce_field('smlms_item_custom_meta_nonce', 'smlms_item_custom_meta_nonce_field');

        $is_lesson    = ($post->post_type === 'smlms_lesson');
        $is_sample    = get_post_meta($post->ID, '_smlms_is_sample', true) === '1';
        $duration     = get_post_meta($post->ID, '_smlms_duration', true) ?: '5.00';
        $content_type = get_post_meta($post->ID, '_smlms_content_type', true) ?: 'video';
        ?>

        <table class="form-table smlms-meta-table">
            <?php if ($is_lesson): ?>
                <tr>
                    <th scope="row"><label for="smlms_is_sample">Sample Lesson</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="smlms_is_sample" name="_smlms_is_sample" value="1" <?php checked($is_sample, true); ?>>
                            Allow Sample Preview (Non-enrolled users can view this lesson and all its topics in Focus Mode)
                        </label>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <th scope="row"><label for="smlms_duration">Estimated Duration</label></th>
                <td>
                    <input type="text" id="smlms_duration" name="_smlms_duration" class="regular-text" value="<?php echo esc_attr($duration); ?>" placeholder="e.g. 5.00">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="smlms_content_type">Content Type</label></th>
                <td>
                    <select id="smlms_content_type" name="_smlms_content_type" class="regular-text">
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