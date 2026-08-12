<?php
/**
 * Lesson and Topic Display Options Meta Box (Materials & Video Progression)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Display_Options {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register']);
    }

    public static function register() {
        add_meta_box('smlms_display_content_options', 'Display and Content Options', [__CLASS__, 'render'], ['smlms_lesson', 'smlms_topic'], 'normal', 'high');
    }

    public static function render($post) {
        wp_nonce_field('smlms_save_options_meta', 'smlms_options_nonce');
        $is_lesson         = ($post->post_type === 'smlms_lesson');
        $item_label        = $is_lesson ? 'Lesson' : 'Topic';

        $materials_enabled = get_post_meta($post->ID, '_smlms_materials_enabled', true) ?: '1';
        $materials_content = get_post_meta($post->ID, '_smlms_materials', true);

        $video_enabled      = get_post_meta($post->ID, '_smlms_video_enabled', true) ?: '1';
        $video_url          = get_post_meta($post->ID, '_smlms_video_id', true);
        ?>
        <div class="smlms-options-panel">
            <p class="smlms-panel-subheading">Controls optional content settings for this <?php echo strtolower($item_label); ?></p>

            <div class="smlms-option-group">
                <div class="smlms-option-header">
                    <strong><?php echo $item_label; ?> Materials</strong>
                    <label class="smlms-switch">
                        <input type="checkbox" name="smlms_materials_enabled" value="1" <?php checked($materials_enabled, '1'); ?> class="smlms-toggle-trigger" data-target="#smlms-materials-body">
                        <span class="smlms-slider"></span>
                    </label>
                </div>
                <div id="smlms-materials-body" class="smlms-option-body" style="<?php echo ($materials_enabled === '1') ? '' : 'display:none;'; ?>">
                    <?php wp_editor($materials_content, 'smlms_materials_editor', ['textarea_name' => 'smlms_materials', 'textarea_rows' => 5, 'media_buttons' => true]); ?>
                </div>
            </div>

            <div class="smlms-option-group">
                <div class="smlms-option-header">
                    <strong>Video Progression</strong>
                    <label class="smlms-switch">
                        <input type="checkbox" name="smlms_video_enabled" value="1" <?php checked($video_enabled, '1'); ?> class="smlms-toggle-trigger" data-target="#smlms-video-body">
                        <span class="smlms-slider"></span>
                    </label>
                </div>
                <div id="smlms-video-body" class="smlms-option-body" style="<?php echo ($video_enabled === '1') ? '' : 'display:none;'; ?>">
                    <div class="smlms-field-row">
                        <label><strong>Video URL / Vimeo ID</strong></label>
                        <textarea name="smlms_video_id" rows="2" class="widefat" placeholder="Input URL or Vimeo ID here"><?php echo esc_textarea($video_url); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
SMLMS_Meta_Display_Options::init();