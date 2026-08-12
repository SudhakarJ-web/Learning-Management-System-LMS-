<?php
/**
 * Display and Content Options Meta Box Handler (LearnDash Replica)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Display_Options {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_box']);
    }

    public static function register_meta_box() {
        add_meta_box(
            'smlms_display_options_meta',
            'Display and Content Options',
            [__CLASS__, 'render_meta_box'],
            ['smlms_lesson', 'smlms_topic'],
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('smlms_display_options_nonce_action', 'smlms_display_options_nonce_field');

        $is_topic                   = ($post->post_type === 'smlms_topic');
        $item_label                 = $is_topic ? 'Topic' : 'Lesson';
        
        // Retrieve Meta Values
        $materials_enabled          = get_post_meta($post->ID, '_smlms_materials_enabled', true) === '1';
        $materials                  = get_post_meta($post->ID, '_smlms_materials', true) ?: '';
        $video_progression_enabled = get_post_meta($post->ID, '_smlms_video_progression_enabled', true) === '1';
        $video_url                  = get_post_meta($post->ID, '_smlms_video_id', true) ?: (get_post_meta($post->ID, '_smlms_media_embed', true) ?: '');
        $display_timing             = get_post_meta($post->ID, '_smlms_display_timing', true) ?: 'before';
        $autostart                  = get_post_meta($post->ID, '_smlms_autostart', true) === '1';
        ?>

        <div class="smlms-display-options-container">
            <p class="smlms-display-options-subtitle">Controls the look and feel of the <?php echo strtolower($item_label); ?> and optional content settings</p>

            <!-- Row 1: Materials Toggle & Editor -->
            <div class="smlms-meta-section-row">
                <div class="smlms-meta-header-group">
                    <span class="smlms-meta-title"><?php echo esc_html($item_label); ?> Materials</span>
                    <span class="dashicons dashicons-editor-help smlms-help-tooltip" title="Enable to add downloadable files, PDFs, or extra reading materials."></span>
                    <label class="smlms-toggle-switch">
                        <input type="checkbox" id="_smlms_materials_enabled" name="_smlms_materials_enabled" value="1" <?php checked($materials_enabled, true); ?>>
                        <span class="smlms-toggle-slider"></span>
                    </label>
                    <span class="smlms-toggle-label-text">Any content added below is displayed on the <?php echo strtolower($item_label); ?> page</span>
                </div>

                <div id="smlms-materials-editor-wrap" class="smlms-sub-option-box" style="<?php echo $materials_enabled ? 'display: block;' : 'display: none;'; ?>">
                    <?php 
                    wp_editor($materials, '_smlms_materials', [
                        'textarea_name' => '_smlms_materials',
                        'textarea_rows' => 6,
                        'media_buttons' => true,
                        'teeny'         => false,
                        'quicktags'     => true,
                    ]); 
                    ?>
                </div>
            </div>

            <!-- Row 2: Video Progression Toggle & Sub-options -->
            <div class="smlms-meta-section-row">
                <div class="smlms-meta-header-group">
                    <span class="smlms-meta-title">Video Progression</span>
                    <span class="dashicons dashicons-editor-help smlms-help-tooltip" title="Tie video viewing to step completion and focus mode embeds."></span>
                    <label class="smlms-toggle-switch">
                        <input type="checkbox" id="_smlms_video_progression_enabled" name="_smlms_video_progression_enabled" value="1" <?php checked($video_progression_enabled, true); ?>>
                        <span class="smlms-toggle-slider"></span>
                    </label>
                    <span class="smlms-toggle-label-text">The below video is tied to course progression</span>
                </div>

                <div id="smlms-video-progression-box" class="smlms-sub-option-box" style="<?php echo $video_progression_enabled ? 'display: block;' : 'display: none;'; ?>">
                    
                    <!-- Video URL Field -->
                    <div class="smlms-sub-field-row">
                        <label for="_smlms_video_id" class="smlms-sub-field-label">Video URL</label>
                        <div class="smlms-sub-field-input">
                            <textarea id="_smlms_video_id" name="_smlms_video_id" class="widefat smlms-admin-textarea" rows="2" placeholder="e.g. https://vimeo.com/1181497065 or Vimeo ID / iframe embed code"><?php echo esc_textarea($video_url); ?></textarea>
                        </div>
                    </div>

                    <!-- Display Timing Radio Buttons -->
                    <div class="smlms-sub-field-row">
                        <label class="smlms-sub-field-label">Display Timing</label>
                        <div class="smlms-sub-field-input">
                            <label class="smlms-radio-block">
                                <input type="radio" name="_smlms_display_timing" value="before" <?php checked($display_timing, 'before'); ?>>
                                <strong>Before completed sub-steps</strong>
                                <p class="description">The video will be shown and must be fully watched before the user can access the <?php echo strtolower($item_label); ?>'s associated steps.</p>
                            </label>
                            
                            <label class="smlms-radio-block">
                                <input type="radio" name="_smlms_display_timing" value="after" <?php checked($display_timing, 'after'); ?>>
                                <strong>After completing sub-steps</strong>
                                <p class="description">The video will be visible after the user has completed the <?php echo strtolower($item_label); ?>'s associated steps.</p>
                            </label>
                        </div>
                    </div>

                    <!-- Autostart Toggle -->
                    <div class="smlms-sub-field-row smlms-autostart-row">
                        <label class="smlms-sub-field-label">Autostart</label>
                        <div class="smlms-sub-field-input smlms-inline-toggle">
                            <span class="dashicons dashicons-editor-help smlms-help-tooltip" title="Automatically start video playback on page load."></span>
                            <label class="smlms-toggle-switch">
                                <input type="checkbox" name="_smlms_autostart" value="1" <?php checked($autostart, true); ?>>
                                <span class="smlms-toggle-slider"></span>
                            </label>
                            <span class="smlms-toggle-label-text">The video now starts automatically on page load</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Materials Toggle Listener
            $('#_smlms_materials_enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#smlms-materials-editor-wrap').slideDown(150);
                } else {
                    $('#smlms-materials-editor-wrap').slideUp(150);
                }
            });

            // Video Progression Toggle Listener
            $('#_smlms_video_progression_enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#smlms-video-progression-box').slideDown(150);
                } else {
                    $('#smlms-video-progression-box').slideUp(150);
                }
            });
        });
        </script>
        <?php
    }
}
SMLMS_Meta_Display_Options::init();