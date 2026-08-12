<?php
/**
 * Course Students Admin Meta Box (Dual Listbox Interface)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Students {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_box']);
    }

    public static function register_meta_box() {
        add_meta_box(
            'smlms_course_students_meta',
            'Course Students',
            [__CLASS__, 'render_meta_box'],
            'smlms_course',
            'normal',
            'default' // Set to 'default' so it renders below Course Access & Enrollment Settings ('high')
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('smlms_course_students_nonce_action', 'smlms_course_students_nonce');

        $course_id         = $post->ID;
        $enrolled_user_ids = SMLMS_DB::get_enrolled_user_ids($course_id);

        // Fetch all WordPress users
        $all_users = get_users([
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'fields'  => ['ID', 'display_name', 'user_login', 'user_email']
        ]);

        $unassigned_users = [];
        $assigned_users   = [];

        foreach ($all_users as $user) {
            $is_enrolled = in_array($user->ID, $enrolled_user_ids);
            $label       = sprintf('%s (%s)', $user->display_name, $user->user_login);

            if ($is_enrolled) {
                $assigned_users[$user->ID] = $label;
            } else {
                $unassigned_users[$user->ID] = $label;
            }
        }

        $assigned_ids_str = implode(',', array_keys($assigned_users));
        ?>

        <div class="smlms-dual-listbox-wrapper">
            <p class="smlms-dual-listbox-notice">
                Students enrolled via Groups using this Course are excluded from the listings below and should be managed via the Group admin screen.
            </p>

            <input type="hidden" name="smlms_assigned_user_ids" id="smlms_assigned_user_ids" value="<?php echo esc_attr($assigned_ids_str); ?>">

            <div class="smlms-dual-listbox-grid">
                
                <!-- Left Box: All Unassigned Users -->
                <div class="smlms-listbox-column">
                    <input type="text" id="smlms_search_all_users" class="smlms-listbox-search" placeholder="Search All Course Users...">
                    <select id="smlms_all_users_select" class="smlms-listbox-select" multiple size="12">
                        <?php foreach ($unassigned_users as $uid => $ulabel): ?>
                            <option value="<?php echo esc_attr($uid); ?>"><?php echo esc_html($ulabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Center: Transfer Arrows -->
                <div class="smlms-listbox-actions">
                    <button type="button" id="smlms_btn_assign_user" class="smlms-circle-arrow-btn" title="Assign Access">
                        &rarr;
                    </button>
                    <button type="button" id="smlms_btn_unassign_user" class="smlms-circle-arrow-btn" title="Revoke Access">
                        &larr;
                    </button>
                </div>

                <!-- Right Box: Assigned Course Users -->
                <div class="smlms-listbox-column">
                    <input type="text" id="smlms_search_assigned_users" class="smlms-listbox-search" placeholder="Search Assigned Course Users...">
                    <select id="smlms_assigned_users_select" class="smlms-listbox-select" multiple size="12">
                        <?php foreach ($assigned_users as $uid => $ulabel): ?>
                            <option value="<?php echo esc_attr($uid); ?>"><?php echo esc_html($ulabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            
            function updateAssignedHiddenInput() {
                var assignedIds = [];
                $('#smlms_assigned_users_select option').each(function() {
                    assignedIds.push($(this).val());
                });
                $('#smlms_assigned_user_ids').val(assignedIds.join(','));
            }

            // Assign Access (Move Right ->)
            $('#smlms_btn_assign_user').on('click', function() {
                $('#smlms_all_users_select option:selected').each(function() {
                    $(this).appendTo('#smlms_assigned_users_select');
                });
                updateAssignedHiddenInput();
            });

            // Revoke Access (Move Left <-)
            $('#smlms_btn_unassign_user').on('click', function() {
                $('#smlms_assigned_users_select option:selected').each(function() {
                    $(this).appendTo('#smlms_all_users_select');
                });
                updateAssignedHiddenInput();
            });

            // Live Search Filter - All Users
            $('#smlms_search_all_users').on('keyup', function() {
                var term = $(this).val().toLowerCase();
                $('#smlms_all_users_select option').each(function() {
                    var txt = $(this).text().toLowerCase();
                    $(this).toggle(txt.indexOf(term) > -1);
                });
            });

            // Live Search Filter - Assigned Users
            $('#smlms_search_assigned_users').on('keyup', function() {
                var term = $(this).val().toLowerCase();
                $('#smlms_assigned_users_select option').each(function() {
                    var txt = $(this).text().toLowerCase();
                    $(this).toggle(txt.indexOf(term) > -1);
                });
            });
        });
        </script>
        <?php
    }
}
SMLMS_Meta_Students::init();