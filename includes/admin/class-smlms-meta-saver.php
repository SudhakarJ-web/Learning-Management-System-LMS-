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

        // 1. Save Course Builder JSON & Synchronize Relationships
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

        // 2. Save Access Settings
        if (isset($_POST['_smlms_access_type'])) {
            update_post_meta($post_id, '_smlms_access_type', sanitize_text_field($_POST['_smlms_access_type']));
        }

        if (isset($_POST['_smlms_price'])) {
            update_post_meta($post_id, '_smlms_price', sanitize_text_field($_POST['_smlms_price']));
        }

        if (isset($_POST['_smlms_custom_checkout_url'])) {
            update_post_meta($post_id, '_smlms_custom_checkout_url', esc_url_raw($_POST['_smlms_custom_checkout_url']));
        }

        // 3. Save Course Custom Meta
        if (isset($_POST['_smlms_course_headline'])) {
            update_post_meta($post_id, '_smlms_course_headline', sanitize_text_field($_POST['_smlms_course_headline']));
        }
        if (isset($_POST['_smlms_course_short_desc'])) {
            update_post_meta($post_id, '_smlms_course_short_desc', sanitize_textarea_field($_POST['_smlms_course_short_desc']));
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
        if (isset($_POST['_smlms_number_of_lessons'])) {
            update_post_meta($post_id, '_smlms_number_of_lessons', sanitize_text_field($_POST['_smlms_number_of_lessons']));
        }
        if (isset($_POST['_smlms_students_enrolled'])) {
            update_post_meta($post_id, '_smlms_students_enrolled', intval($_POST['_smlms_students_enrolled']));
        }
        if (isset($_POST['_smlms_content_type'])) {
            update_post_meta($post_id, '_smlms_content_type', sanitize_text_field($_POST['_smlms_content_type']));
        }
        if (isset($_POST['_smlms_media_embed'])) {
            update_post_meta($post_id, '_smlms_media_embed', wp_kses_post($_POST['_smlms_media_embed']));
        }

        // 4. Save Course Student Assignments (Dual Listbox)
        if (isset($_POST['smlms_assigned_user_ids'])) {
            $raw_uids      = sanitize_text_field($_POST['smlms_assigned_user_ids']);
            $new_uids      = !empty($raw_uids) ? array_map('intval', explode(',', $raw_uids)) : [];
            $existing_uids = SMLMS_DB::get_enrolled_user_ids($post_id);

            // Enroll new users
            foreach ($new_uids as $uid) {
                if (!in_array($uid, $existing_uids)) {
                    SMLMS_DB::enroll_student($uid, $post_id);
                }
            }

            // Revoke removed users
            foreach ($existing_uids as $uid) {
                if (!in_array($uid, $new_uids)) {
                    SMLMS_DB::unenroll_student($uid, $post_id);
                }
            }
        }
    }

    public static function save_item_meta($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Save Custom Item Meta (Sample status, Duration, Content Type)
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

        // Save Display and Content Options Meta
        if (isset($_POST['smlms_display_options_nonce_field']) && wp_verify_nonce($_POST['smlms_display_options_nonce_field'], 'smlms_display_options_nonce_action')) {
            
            // Materials Toggle & Content
            $materials_enabled = isset($_POST['_smlms_materials_enabled']) ? '1' : '0';
            update_post_meta($post_id, '_smlms_materials_enabled', $materials_enabled);

            if (isset($_POST['_smlms_materials'])) {
                update_post_meta($post_id, '_smlms_materials', wp_unslash($_POST['_smlms_materials']));
            }

            // Video Progression Toggle & Video URL
            $video_progression_enabled = isset($_POST['_smlms_video_progression_enabled']) ? '1' : '0';
            update_post_meta($post_id, '_smlms_video_progression_enabled', $video_progression_enabled);

            if (isset($_POST['_smlms_video_id'])) {
                $raw_video = sanitize_textarea_field($_POST['_smlms_video_id']);
                update_post_meta($post_id, '_smlms_video_id', $raw_video);
                update_post_meta($post_id, '_smlms_media_embed', $raw_video);
            }

            if (isset($_POST['_smlms_display_timing'])) {
                update_post_meta($post_id, '_smlms_display_timing', sanitize_text_field($_POST['_smlms_display_timing']));
            }

            $autostart = isset($_POST['_smlms_autostart']) ? '1' : '0';
            update_post_meta($post_id, '_smlms_autostart', $autostart);
        }
    }
}
SMLMS_Meta_Saver::init();