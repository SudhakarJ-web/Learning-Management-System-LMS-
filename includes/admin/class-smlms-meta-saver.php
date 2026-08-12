<?php
/**
 * Admin Meta Box Saver Handler & DB Relationship Synchronizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Saver {

    public static function init() {
        add_action('save_post_smlms_course', [__CLASS__, 'save_course_meta'], 10, 2);
        add_action('save_post_smlms_lesson', [__CLASS__, 'save_item_meta'], 10, 2);
        add_action('save_post_smlms_topic', [__CLASS__, 'save_item_meta'], 10, 2);
    }

    public static function save_course_meta($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Save Course Builder JSON & Synchronize Parent Relationships
        if (isset($_POST['_smlms_course_tree_json'])) {
            $raw_json = wp_unslash($_POST['_smlms_course_tree_json']);
            update_post_meta($post_id, '_smlms_course_tree_json', $raw_json);

            $tree_data = json_decode($raw_json, true);
            if (is_array($tree_data)) {
                foreach ($tree_data as $lesson_node) {
                    $lesson_id = intval($lesson_node['id'] ?? 0);
                    if ($lesson_id > 0) {
                        update_post_meta($lesson_id, '_smlms_parent_course_id', $post_id);

                        if (!empty($lesson_node['topics']) && is_array($lesson_node['topics'])) {
                            foreach ($lesson_node['topics'] as $topic_node) {
                                $topic_id = intval($topic_node['id'] ?? 0);
                                if ($topic_id > 0) {
                                    update_post_meta($topic_id, '_smlms_parent_lesson_id', $lesson_id);
                                    update_post_meta($topic_id, '_smlms_parent_course_id', $post_id);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Save Course Details Meta
        if (isset($_POST['_smlms_price'])) {
            update_post_meta($post_id, '_smlms_price', sanitize_text_field($_POST['_smlms_price']));
        }
        if (isset($_POST['_smlms_duration'])) {
            update_post_meta($post_id, '_smlms_duration', sanitize_text_field($_POST['_smlms_duration']));
        }
        if (isset($_POST['_smlms_level'])) {
            update_post_meta($post_id, '_smlms_level', sanitize_text_field($_POST['_smlms_level']));
        }
        if (isset($_POST['_smlms_language'])) {
            update_post_meta($post_id, '_smlms_language', sanitize_text_field($_POST['_smlms_language']));
        }
        if (isset($_POST['_smlms_students_enrolled'])) {
            update_post_meta($post_id, '_smlms_students_enrolled', intval($_POST['_smlms_students_enrolled']));
        }
        if (isset($_POST['_smlms_media_embed'])) {
            update_post_meta($post_id, '_smlms_media_embed', wp_kses_post($_POST['_smlms_media_embed']));
        }
    }

    public static function save_item_meta($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Custom Meta Nonce Verification
        if (isset($_POST['smlms_item_custom_meta_nonce_field']) && wp_verify_nonce($_POST['smlms_item_custom_meta_nonce_field'], 'smlms_item_custom_meta_nonce')) {
            $is_sample = isset($_POST['_smlms_is_sample']) ? '1' : '0';
            update_post_meta($post_id, '_smlms_is_sample', $is_sample);

            if (isset($_POST['_smlms_duration'])) {
                update_post_meta($post_id, '_smlms_duration', sanitize_text_field($_POST['_smlms_duration']));
            }
            if (isset($_POST['_smlms_content_type'])) {
                update_post_meta($post_id, '_smlms_content_type', sanitize_text_field($_POST['_smlms_content_type']));
            }
        }

        // Display Options Nonce Verification
        if (isset($_POST['smlms_display_options_nonce_field']) && wp_verify_nonce($_POST['smlms_display_options_nonce_field'], 'smlms_display_options_nonce')) {
            $materials_enabled = isset($_POST['_smlms_materials_enabled']) ? '1' : '0';
            update_post_meta($post_id, '_smlms_materials_enabled', $materials_enabled);

            if (isset($_POST['_smlms_materials'])) {
                update_post_meta($post_id, '_smlms_materials', wp_kses_post($_POST['_smlms_materials']));
            }

            $video_enabled = isset($_POST['_smlms_video_enabled']) ? '1' : '0';
            update_post_meta($post_id, '_smlms_video_enabled', $video_enabled);

            if (isset($_POST['_smlms_video_id'])) {
                update_post_meta($post_id, '_smlms_video_id', sanitize_text_field($_POST['_smlms_video_id']));
            }
        }
    }
}
SMLMS_Meta_Saver::init();