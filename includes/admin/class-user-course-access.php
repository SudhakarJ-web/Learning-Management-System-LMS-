<?php
/**
 * Admin User Profile Course Access Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_User_Course_Access {

    public static function init() {
        add_action('show_user_profile', [__CLASS__, 'render_user_course_access']);
        add_action('edit_user_profile', [__CLASS__, 'render_user_course_access']);
        add_action('personal_options_update', [__CLASS__, 'save_user_course_access']);
        add_action('edit_user_profile_update', [__CLASS__, 'save_user_course_access']);
    }

    public static function render_user_course_access($user) {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_nonce_field('smlms_user_course_access_nonce', 'smlms_user_course_access_nonce_field');

        $user_id           = $user->ID;
        $enrolled_courses  = SMLMS_DB::get_user_enrolled_courses($user_id);
        $all_courses_posts = get_posts([
            'post_type'      => 'smlms_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);

        $available_courses = [];
        $assigned_courses  = [];

        foreach ($all_courses_posts as $c_post) {
            if (in_array($c_post->ID, $enrolled_courses)) {
                $assigned_courses[$c_post->ID] = $c_post->post_title;
            } else {
                $available_courses[$c_post->ID] = $c_post->post_title;
            }
        }

        $assigned_courses_str = implode(',', array_keys($assigned_courses));
        ?>

        <h2>Sabin Mathew LMS - Course Access</h2>

        <table class="form-table">
            <tr>
                <th><label>Manage Enrollments</label></th>
                <td>
                    <input type="hidden" name="smlms_user_assigned_course_ids" id="smlms_user_assigned_course_ids" value="<?php echo esc_attr($assigned_courses_str); ?>">

                    <div class="smlms-user-dual-listbox-grid">
                        
                        <!-- Available Courses Column -->
                        <div class="smlms-user-listbox-col">
                            <label class="smlms-sub-label">Available Courses</label>
                            <input type="text" id="smlms_search_avail_courses" class="smlms-listbox-search" placeholder="Search Courses...">
                            <select id="smlms_avail_courses_select" class="smlms-listbox-select" multiple size="8">
                                <?php foreach ($available_courses as $cid => $ctitle): ?>
                                    <option value="<?php echo esc_attr($cid); ?>"><?php echo esc_html($ctitle); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="smlms-user-listbox-actions">
                            <button type="button" id="smlms_btn_user_assign_course" class="button smlms-btn-square">&rarr;</button>
                            <button type="button" id="smlms_btn_user_unassign_course" class="button smlms-btn-square">&larr;</button>
                        </div>

                        <!-- Enrolled Courses Column -->
                        <div class="smlms-user-listbox-col">
                            <label class="smlms-sub-label">Enrolled Courses</label>
                            <input type="text" id="smlms_search_enrolled_courses" class="smlms-listbox-search" placeholder="Search Enrolled...">
                            <select id="smlms_enrolled_courses_select" class="smlms-listbox-select" multiple size="8">
                                <?php foreach ($assigned_courses as $cid => $ctitle): ?>
                                    <option value="<?php echo esc_attr($cid); ?>"><?php echo esc_html($ctitle); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                    <p class="description">Select courses and use the arrows to grant or revoke course access for this specific user.</p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            function syncUserCourseHidden() {
                var cIds = [];
                $('#smlms_enrolled_courses_select option').each(function() {
                    cIds.push($(this).val());
                });
                $('#smlms_user_assigned_course_ids').val(cIds.join(','));
            }

            $('#smlms_btn_user_assign_course').on('click', function() {
                $('#smlms_avail_courses_select option:selected').each(function() {
                    $(this).appendTo('#smlms_enrolled_courses_select');
                });
                syncUserCourseHidden();
            });

            $('#smlms_btn_user_unassign_course').on('click', function() {
                $('#smlms_enrolled_courses_select option:selected').each(function() {
                    $(this).appendTo('#smlms_avail_courses_select');
                });
                syncUserCourseHidden();
            });

            $('#smlms_search_avail_courses').on('keyup', function() {
                var term = $(this).val().toLowerCase();
                $('#smlms_avail_courses_select option').each(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(term) > -1);
                });
            });

            $('#smlms_search_enrolled_courses').on('keyup', function() {
                var term = $(this).val().toLowerCase();
                $('#smlms_enrolled_courses_select option').each(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(term) > -1);
                });
            });
        });
        </script>
        <?php
    }

    public static function save_user_course_access($user_id) {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['smlms_user_course_access_nonce_field']) || !wp_verify_nonce($_POST['smlms_user_course_access_nonce_field'], 'smlms_user_course_access_nonce')) {
            return;
        }

        if (isset($_POST['smlms_user_assigned_course_ids'])) {
            $raw_ids      = sanitize_text_field($_POST['smlms_user_assigned_course_ids']);
            $new_cids     = !empty($raw_ids) ? array_map('intval', explode(',', $raw_ids)) : [];
            $existing_ids = SMLMS_DB::get_user_enrolled_courses($user_id);

            // Grant missing courses
            foreach ($new_cids as $cid) {
                if (!in_array($cid, $existing_ids)) {
                    SMLMS_DB::enroll_student($user_id, $cid);
                }
            }

            // Revoke removed courses
            foreach ($existing_ids as $cid) {
                if (!in_array($cid, $new_cids)) {
                    SMLMS_DB::unenroll_student($user_id, $cid);
                }
            }
        }
    }
}
SMLMS_User_Course_Access::init();