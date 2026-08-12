<?php
/**
 * Course Assigned Students Dual List Box Meta Box
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Students {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register']);
    }

    public static function register() {
        add_meta_box('smlms_course_students_box', 'Course Students', [__CLASS__, 'render'], 'smlms_course', 'normal', 'default');
    }

    public static function render($post) {
        global $wpdb;
        wp_nonce_field('smlms_save_students_meta', 'smlms_students_nonce');

        $enrolled_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}smlms_enrollments WHERE course_id = %d AND status = 'active'",
            $post->ID
        ));
        if (!is_array($enrolled_ids)) $enrolled_ids = [];

        $all_users = get_users(['number' => 500, 'orderby' => 'display_name', 'order' => 'ASC']);

        $unassigned_users = [];
        $assigned_users   = [];

        foreach ($all_users as $u) {
            $user_info = $u->display_name . ' (' . $u->user_login . ')';
            if (in_array($u->ID, $enrolled_ids)) {
                $assigned_users[$u->ID] = $user_info;
            } else {
                $unassigned_users[$u->ID] = $user_info;
            }
        }
        ?>
        <div class="smlms-students-panel">
            <p class="smlms-panel-subheading">Students enrolled via Groups using this Course are excluded from the listings below and should be managed via the Group admin screen.</p>

            <div class="smlms-dual-selector-wrapper">
                <div class="smlms-selector-column">
                    <input type="text" class="smlms-user-search-input widefat" placeholder="Search All Course Users..." data-target="#smlms-unassigned-users-select">
                    <select id="smlms-unassigned-users-select" class="smlms-dual-listbox" multiple size="10">
                        <?php foreach ($unassigned_users as $uid => $uname): ?>
                            <option value="<?php echo $uid; ?>"><?php echo esc_html($uname); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="smlms-selector-actions">
                    <button type="button" id="smlms-btn-assign-users" class="button button-secondary" title="Assign Selected">&rarr;</button>
                    <button type="button" id="smlms-btn-remove-users" class="button button-secondary" title="Remove Selected">&larr;</button>
                </div>

                <div class="smlms-selector-column">
                    <input type="text" class="smlms-user-search-input widefat" placeholder="Search Assigned Course Users..." data-target="#smlms-assigned-users-select">
                    <select id="smlms-assigned-users-select" class="smlms-dual-listbox" multiple size="10">
                        <?php foreach ($assigned_users as $uid => $uname): ?>
                            <option value="<?php echo $uid; ?>"><?php echo esc_html($uname); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="smlms-assigned-hidden-inputs">
                <?php foreach ($assigned_users as $uid => $uname): ?>
                    <input type="hidden" name="smlms_enrolled_user_ids[]" value="<?php echo $uid; ?>">
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
SMLMS_Meta_Students::init();