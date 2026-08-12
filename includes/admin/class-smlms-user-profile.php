<?php
/**
 * Native SMLMS User Profile Enrollment Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_User_Profile {

    public static function init() {
        add_action('show_user_profile', [__CLASS__, 'render_enrollment_manager']);
        add_action('edit_user_profile', [__CLASS__, 'render_enrollment_manager']);
        
        add_action('personal_options_update', [__CLASS__, 'save_enrollment_manager']);
        add_action('edit_user_profile_update', [__CLASS__, 'save_enrollment_manager']);
    }

    public static function render_enrollment_manager($user) {
        global $wpdb;
        wp_nonce_field('smlms_user_enrollment_nonce', 'smlms_user_enrollment_nonce_field');

        // Fetch user's active enrolled courses
        $enrolled_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT course_id FROM {$wpdb->prefix}smlms_enrollments WHERE user_id = %d AND status = 'active'",
            $user->ID
        ));
        if (!is_array($enrolled_ids)) $enrolled_ids = [];

        // Fetch all published courses
        $all_courses = get_posts([
            'post_type'   => 'smlms_course',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby'     => 'title',
            'order'       => 'ASC'
        ]);

        $assigned = [];
        $unassigned = [];

        foreach ($all_courses as $course) {
            if (in_array($course->ID, $enrolled_ids)) {
                $assigned[$course->ID] = $course->post_title;
            } else {
                $unassigned[$course->ID] = $course->post_title;
            }
        }
        ?>
        <h2 style="margin-top: 30px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Sabin Mathew LMS - Course Access</h2>
        <table class="form-table">
            <tr>
                <th><label>Manage Enrollments</label></th>
                <td>
                    <div class="smlms-dual-selector-wrapper" style="max-width: 800px; display: flex; align-items: center; gap: 15px;">
                        <div class="smlms-selector-column" style="flex: 1;">
                            <strong>Available Courses</strong><br/>
                            <input type="text" class="smlms-user-search-input widefat" placeholder="Search Courses..." data-target="#smlms-unassigned-users-select" style="margin-bottom: 5px;">
                            <select id="smlms-unassigned-users-select" class="smlms-dual-listbox" multiple size="10" style="width: 100%; min-height: 200px;">
                                <?php foreach ($unassigned as $cid => $ctitle): ?>
                                    <option value="<?php echo $cid; ?>"><?php echo esc_html($ctitle); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="smlms-selector-actions" style="display: flex; flex-direction: column; gap: 10px;">
                            <button type="button" id="smlms-btn-assign-users" class="button button-secondary" title="Grant Access">&rarr;</button>
                            <button type="button" id="smlms-btn-remove-users" class="button button-secondary" title="Revoke Access">&larr;</button>
                        </div>

                        <div class="smlms-selector-column" style="flex: 1;">
                            <strong>Enrolled Courses</strong><br/>
                            <input type="text" class="smlms-user-search-input widefat" placeholder="Search Enrolled..." data-target="#smlms-assigned-users-select" style="margin-bottom: 5px;">
                            <select id="smlms-assigned-users-select" class="smlms-dual-listbox" multiple size="10" style="width: 100%; min-height: 200px;">
                                <?php foreach ($assigned as $cid => $ctitle): ?>
                                    <option value="<?php echo $cid; ?>"><?php echo esc_html($ctitle); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="smlms-assigned-hidden-inputs">
                        <?php foreach ($assigned as $cid => $ctitle): ?>
                            <input type="hidden" name="smlms_enrolled_course_ids[]" value="<?php echo $cid; ?>">
                        <?php endforeach; ?>
                    </div>
                    <p class="description">Select courses and use the arrows to grant or revoke course access for this specific user.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function save_enrollment_manager($user_id) {
        if (!current_user_can('edit_user', $user_id)) return false;
        if (!isset($_POST['smlms_user_enrollment_nonce_field']) || !wp_verify_nonce($_POST['smlms_user_enrollment_nonce_field'], 'smlms_user_enrollment_nonce')) return false;

        global $wpdb;

        // Capture previous state to update enrollment counts accurately
        $old_course_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT course_id FROM {$wpdb->prefix}smlms_enrollments WHERE user_id = %d AND status = 'active'",
            $user_id
        ));

        $new_course_ids = isset($_POST['smlms_enrolled_course_ids']) ? array_map('intval', $_POST['smlms_enrolled_course_ids']) : [];

        // Clear existing enrollments for this user
        $wpdb->delete($wpdb->prefix . 'smlms_enrollments', ['user_id' => $user_id], ['%d']);

        // Insert newly assigned courses
        foreach ($new_course_ids as $cid) {
            $wpdb->insert(
                $wpdb->prefix . 'smlms_enrollments',
                [
                    'user_id'     => $user_id,
                    'course_id'   => $cid,
                    'status'      => 'active',
                    'enrolled_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s']
            );
        }

        // Resync student enrollment meta counts on affected courses
        $affected_courses = array_unique(array_merge($old_course_ids, $new_course_ids));
        foreach ($affected_courses as $cid) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}smlms_enrollments WHERE course_id = %d AND status = 'active'", $cid));
            update_post_meta($cid, '_smlms_students_enrolled', $count);
        }
    }
}
SMLMS_User_Profile::init();