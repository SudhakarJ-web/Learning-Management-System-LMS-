<?php
/**
 * Interactive Sabin Mathew Builder, Custom Columns, & Meta Box Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_CPT {

    public function init() {
        add_action('init', [$this, 'register_cpts_and_taxonomies']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_custom_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        add_filter('parent_file', [$this, 'fix_parent_menu_highlight']);

        add_filter('manage_smlms_course_posts_columns', [$this, 'set_course_table_columns']);
        add_action('manage_smlms_course_posts_custom_column', [$this, 'render_course_table_columns'], 10, 2);
    }

    public function register_cpts_and_taxonomies() {
        register_taxonomy('smlms_course_category', ['smlms_course'], [
            'labels'       => ['name' => 'Course Categories', 'singular_name' => 'Course Category'],
            'hierarchical' => true,
            'show_ui'      => true,
            'show_in_rest' => true,
        ]);

        register_post_type('smlms_course', [
            'labels'       => ['name' => 'Courses', 'singular_name' => 'Course', 'add_new_item' => 'Add New Course'],
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => ['slug' => 'courses'],
            'supports'     => ['title', 'editor', 'thumbnail', 'author', 'comments', 'page-attributes'],
            'taxonomies'   => ['category', 'smlms_course_category'],
            'show_ui'      => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
        ]);

        register_post_type('smlms_lesson', [
            'labels'       => ['name' => 'Lessons', 'singular_name' => 'Lesson', 'add_new_item' => 'Add New Lesson'],
            'public'       => true,
            'supports'     => ['title', 'editor', 'page-attributes'],
            'show_ui'      => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
        ]);

        register_post_type('smlms_topic', [
            'labels'       => ['name' => 'Topics', 'singular_name' => 'Topic', 'add_new_item' => 'Add New Topic'],
            'public'       => true,
            'supports'     => ['title', 'editor', 'page-attributes'],
            'show_ui'      => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
        ]);
    }

    public function register_admin_menu() {
        add_menu_page('Sabin Mathew LMS', 'Sabin Mathew LMS', 'manage_options', 'smlms_main_menu', [$this, 'render_setup_page'], 'dashicons-welcome-learn-more', 30);
        add_submenu_page('smlms_main_menu', 'Setup', 'Setup', 'manage_options', 'smlms_main_menu', [$this, 'render_setup_page']);
        add_submenu_page('smlms_main_menu', 'Courses', 'Courses', 'manage_options', 'edit.php?post_type=smlms_course');
        add_submenu_page('smlms_main_menu', 'Lessons', 'Lessons', 'manage_options', 'edit.php?post_type=smlms_lesson');
        add_submenu_page('smlms_main_menu', 'Topics', 'Topics', 'manage_options', 'edit.php?post_type=smlms_topic');
        add_submenu_page('smlms_main_menu', 'Orders', 'Orders', 'manage_options', 'smlms_orders', [$this, 'render_orders_page']);
        add_submenu_page('smlms_main_menu', 'Settings', 'Settings', 'manage_options', 'smlms_settings', [$this, 'render_settings_page']);
    }

    public function set_course_table_columns($columns) {
        return [
            'cb'              => '<input type="checkbox" />',
            'title'           => 'Title',
            'price_type'      => 'Price Type',
            'author'          => 'Author',
            'categories'      => 'Categories',
            'course_category' => 'Course Categories',
            'views_30_days'   => 'Views: 30 days',
            'comments'        => '<span class="vers comment-grey-bubble" title="Comments"><span class="screen-reader-text">Comments</span></span>',
            'date'            => 'Date',
            'seo_details'     => 'SEO Details'
        ];
    }

    public function render_course_table_columns($column, $post_id) {
        switch ($column) {
            case 'price_type':
                $price_type = get_post_meta($post_id, '_smlms_price_type', true);
                $price      = get_post_meta($post_id, '_smlms_price', true);
                echo $price_type ? esc_html(ucfirst($price_type)) : ($price ? '$' . esc_html($price) : 'Closed');
                break;
            case 'categories':
                $terms = get_the_term_list($post_id, 'category', '', ', ', '');
                echo $terms ? $terms : '—';
                break;
            case 'course_category':
                $terms = get_the_term_list($post_id, 'smlms_course_category', '', ', ', '');
                echo $terms ? $terms : '—';
                break;
            case 'views_30_days':
                $views = get_post_meta($post_id, '_smlms_views_30_days', true);
                echo '<span class="dashicons dashicons-visibility" style="font-size:16px; color:#64748b;"></span> ' . intval($views);
                break;
            case 'seo_details':
                $score = get_post_meta($post_id, 'rank_math_seo_score', true);
                if ($score) {
                    $color = ($score >= 70) ? '#22c55e' : (($score >= 50) ? '#f59e0b' : '#ef4444');
                    echo '<div style="background-color:' . $color . '; color:#fff; padding:2px 8px; border-radius:4px; font-weight:bold; display:inline-block;">' . esc_html($score) . ' / 100</div>';
                } else {
                    echo '<span style="color:#94a3b8;">—</span>';
                }
                break;
        }
    }

    public function add_custom_meta_boxes() {
        // Course Boxes
        add_meta_box('smlms_course_builder_box', 'Sabin Mathew Builder', [$this, 'render_course_builder_meta_box'], 'smlms_course', 'normal', 'high');
        add_meta_box('smlms_course_custom_meta_box', 'Sabin Mathew Course Custom Meta', [$this, 'render_course_custom_meta_box'], 'smlms_course', 'normal', 'high');
        add_meta_box('smlms_course_enrollment_box', 'Course Enrollment', [$this, 'render_course_enrollment_box'], 'smlms_course', 'normal', 'default');
        add_meta_box('smlms_course_students_box', 'Course Students', [$this, 'render_course_students_box'], 'smlms_course', 'normal', 'default');

        add_meta_box('smlms_course_settings', 'Course Pricing & Type', [$this, 'render_course_meta_box'], 'smlms_course', 'side', 'default');
        add_meta_box('smlms_sidebar_lessons_box', 'Lessons', [$this, 'render_sidebar_lessons_box'], 'smlms_course', 'side', 'default');
        add_meta_box('smlms_sidebar_topics_box', 'Topics', [$this, 'render_sidebar_topics_box'], 'smlms_course', 'side', 'default');

        // Lesson & Topic Custom Meta Boxes
        add_meta_box('smlms_custom_item_meta_box', 'Sabin Mathew Custom Meta', [$this, 'render_custom_item_meta_box'], ['smlms_lesson', 'smlms_topic'], 'normal', 'high');
        add_meta_box('smlms_display_content_options', 'Display and Content Options', [$this, 'render_display_content_options'], ['smlms_lesson', 'smlms_topic'], 'normal', 'high');
    }

    public function render_course_enrollment_box($post) {
        wp_nonce_field('smlms_save_enrollment_meta', 'smlms_enrollment_nonce');

        $price_type = get_post_meta($post->ID, '_smlms_price_type', true) ?: 'closed';
        $price      = get_post_meta($post->ID, '_smlms_price', true);
        $button_url = get_post_meta($post->ID, '_smlms_button_url', true);
        ?>
        <div class="smlms-enrollment-panel">
            <p class="smlms-panel-subheading">Controls how students gain access to the course</p>

            <div class="smlms-radio-options-list">
                <label class="smlms-enroll-radio-row">
                    <input type="radio" name="smlms_price_type" value="open" <?php checked($price_type, 'open'); ?> class="smlms-enroll-mode-radio">
                    <div>
                        <strong>Open</strong>
                        <p class="description">The course is not protected. Any student can access its content without the need to be logged-in or enrolled.</p>
                    </div>
                </label>

                <label class="smlms-enroll-radio-row">
                    <input type="radio" name="smlms_price_type" value="free" <?php checked($price_type, 'free'); ?> class="smlms-enroll-mode-radio">
                    <div>
                        <strong>Free</strong>
                        <p class="description">The course is protected. Registration and enrollment are required in order to access the content.</p>
                    </div>
                </label>

                <label class="smlms-enroll-radio-row">
                    <input type="radio" name="smlms_price_type" value="buy_now" <?php checked($price_type, 'buy_now'); ?> class="smlms-enroll-mode-radio">
                    <div>
                        <strong>Buy now</strong>
                        <p class="description">The course is protected via the built-in payment gateway. Students need to purchase the course (one-time fee) in order to gain access.</p>
                    </div>
                </label>

                <label class="smlms-enroll-radio-row">
                    <input type="radio" name="smlms_price_type" value="recurring" <?php checked($price_type, 'recurring'); ?> class="smlms-enroll-mode-radio">
                    <div>
                        <strong>Recurring</strong>
                        <p class="description">The course is protected via the built-in payment gateway. Students need to purchase the course (recurring fee) in order to gain access.</p>
                    </div>
                </label>

                <label class="smlms-enroll-radio-row">
                    <input type="radio" name="smlms_price_type" value="closed" <?php checked($price_type, 'closed'); ?> class="smlms-enroll-mode-radio">
                    <div>
                        <strong>Closed</strong>
                        <p class="description">The course can only be accessed through admin enrollment (manual), group enrollment, or integration (shopping cart or membership) enrollment. No enrollment button will be displayed, unless a URL is set (optional).</p>
                    </div>
                </label>
            </div>

            <div id="smlms-enroll-subfields" class="smlms-enroll-subfields-box" style="<?php echo in_array($price_type, ['buy_now', 'recurring', 'closed']) ? '' : 'display:none;'; ?>">
                <div class="smlms-form-row">
                    <label><strong>Course Price</strong></label>
                    <input type="number" step="0.01" name="smlms_price" value="<?php echo esc_attr($price); ?>" class="regular-text" placeholder="e.g. 25">
                </div>
                <div class="smlms-form-row">
                    <label><strong>Button URL</strong> <span class="smlms-help-icon" title="Redirect URL for purchase button">?</span></label>
                    <input type="text" name="smlms_button_url" value="<?php echo esc_attr($button_url); ?>" class="widefat" placeholder="https://sabinmathew.com/cart/?add-to-cart=86">
                </div>
            </div>
        </div>
        <?php
    }

    public function render_course_students_box($post) {
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

    public function render_course_custom_meta_box($post) {
        wp_nonce_field('smlms_save_course_custom_meta', 'smlms_course_custom_nonce');

        $headline     = get_post_meta($post->ID, '_smlms_course_headline', true);
        $short_desc   = get_post_meta($post->ID, '_smlms_course_short_desc', true);
        $duration     = get_post_meta($post->ID, '_smlms_duration', true) ?: '4 Weeks';
        $level        = get_post_meta($post->ID, '_smlms_level', true) ?: 'Beginner';
        $language     = get_post_meta($post->ID, '_smlms_language', true) ?: 'English';
        $num_lessons  = get_post_meta($post->ID, '_smlms_num_lessons', true);
        $enrolled     = get_post_meta($post->ID, '_smlms_students_enrolled', true) ?: '17';
        $content_type = get_post_meta($post->ID, '_smlms_content_type', true) ?: 'Video';
        $media_embed  = get_post_meta($post->ID, '_smlms_media_embed', true);
        ?>
        <table class="form-table smlms-custom-meta-table">
            <tr>
                <th><label for="smlms_course_headline">Course Headline</label></th>
                <td><input type="text" id="smlms_course_headline" name="smlms_course_headline" value="<?php echo esc_attr($headline); ?>" class="widefat"></td>
            </tr>
            <tr>
                <th><label for="smlms_course_short_desc">Course Short Description</label></th>
                <td><textarea id="smlms_course_short_desc" name="smlms_course_short_desc" rows="3" class="widefat"><?php echo esc_textarea($short_desc); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="smlms_duration">Estimated Duration</label></th>
                <td><input type="text" id="smlms_duration" name="smlms_duration" value="<?php echo esc_attr($duration); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="smlms_level">Course Level</label></th>
                <td>
                    <select id="smlms_level" name="smlms_level" class="regular-text">
                        <option value="Beginner" <?php selected($level, 'Beginner'); ?>>Beginner</option>
                        <option value="Intermediate" <?php selected($level, 'Intermediate'); ?>>Intermediate</option>
                        <option value="Advanced" <?php selected($level, 'Advanced'); ?>>Advanced</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="smlms_language">Course Language</label></th>
                <td><input type="text" id="smlms_language" name="smlms_language" value="<?php echo esc_attr($language); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="smlms_num_lessons">Number of Lessons</label></th>
                <td><input type="text" id="smlms_num_lessons" name="smlms_num_lessons" value="<?php echo esc_attr($num_lessons); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="smlms_students_enrolled">Students Enrolled</label></th>
                <td><input type="text" id="smlms_students_enrolled" name="smlms_students_enrolled" value="<?php echo esc_attr($enrolled); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="smlms_content_type">Content Type</label></th>
                <td>
                    <select id="smlms_content_type" name="smlms_content_type" class="regular-text">
                        <option value="Video" <?php selected($content_type, 'Video'); ?>>Video</option>
                        <option value="Text" <?php selected($content_type, 'Text'); ?>>Text</option>
                        <option value="Audio" <?php selected($content_type, 'Audio'); ?>>Audio</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="smlms_media_embed">Media Embed / Video Preview URL</label></th>
                <td><textarea id="smlms_media_embed" name="smlms_media_embed" rows="2" class="widefat" placeholder="e.g. https://vimeo.com/1201710349"><?php echo esc_textarea($media_embed); ?></textarea></td>
            </tr>
        </table>
        <?php
    }

    public function render_custom_item_meta_box($post) {
        wp_nonce_field('smlms_save_custom_item_meta', 'smlms_custom_item_nonce');
        $duration     = get_post_meta($post->ID, '_smlms_duration', true);
        $content_type = get_post_meta($post->ID, '_smlms_content_type', true) ?: 'video';
        $is_sample    = get_post_meta($post->ID, '_smlms_is_sample', true) ?: '0';
        $is_lesson    = ($post->post_type === 'smlms_lesson');
        ?>
        <table class="form-table smlms-custom-meta-table">
            <?php if ($is_lesson): ?>
            <tr>
                <th><label for="smlms_is_sample">Sample Lesson</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="smlms_is_sample" name="smlms_is_sample" value="1" <?php checked($is_sample, '1'); ?>>
                        <strong>Allow Sample Preview</strong> (Non-enrolled users can view this lesson and all its topics in Focus Mode)
                    </label>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><label for="smlms_duration">Estimated Duration</label></th>
                <td><input type="text" id="smlms_duration" name="smlms_duration" value="<?php echo esc_attr($duration); ?>" placeholder="e.g. 2.24 or 7.03" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="smlms_content_type">Content Type</label></th>
                <td>
                    <select id="smlms_content_type" name="smlms_content_type" class="regular-text">
                        <option value="text" <?php selected($content_type, 'text'); ?>>Text</option>
                        <option value="video" <?php selected($content_type, 'video'); ?>>Video</option>
                        <option value="audio" <?php selected($content_type, 'audio'); ?>>Audio</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function render_display_content_options($post) {
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

    public function render_course_meta_box($post) {
        wp_nonce_field('smlms_save_course_meta', 'smlms_course_nonce');
        $price_type = get_post_meta($post->ID, '_smlms_price_type', true);
        $price      = get_post_meta($post->ID, '_smlms_price', true);
        ?>
        <p>
            <label><strong>Price Type:</strong></label><br>
            <select name="smlms_price_type" class="widefat">
                <option value="closed" <?php selected($price_type, 'closed'); ?>>Closed</option>
                <option value="free" <?php selected($price_type, 'free'); ?>>Free</option>
                <option value="buy_now" <?php selected($price_type, 'buy_now'); ?>>Buy Now</option>
            </select>
        </p>
        <p>
            <label><strong>Course Price ($):</strong></label><br>
            <input type="number" step="0.01" name="smlms_price" value="<?php echo esc_attr($price); ?>" class="widefat">
        </p>
        <?php
    }

    public function render_topic_parent_meta_box($post) {
        $parent_lesson = get_post_meta($post->ID, '_smlms_parent_lesson_id', true);
        $lessons       = get_posts(['post_type' => 'smlms_lesson', 'numberposts' => -1]);
        ?>
        <p>
            <label><strong>Parent Lesson:</strong></label><br>
            <select name="smlms_parent_lesson_id" class="widefat">
                <option value="">-- Select Parent Lesson --</option>
                <?php foreach ($lessons as $lesson): ?>
                    <option value="<?php echo $lesson->ID; ?>" <?php selected($parent_lesson, $lesson->ID); ?>>
                        <?php echo esc_html($lesson->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function render_lesson_parent_meta_box($post) {
        $parent_course = get_post_meta($post->ID, '_smlms_parent_course_id', true);
        $courses       = get_posts(['post_type' => 'smlms_course', 'numberposts' => -1]);
        ?>
        <p>
            <label><strong>Parent Course:</strong></label><br>
            <select name="smlms_parent_course_id" class="widefat">
                <option value="">-- Select Parent Course --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course->ID; ?>" <?php selected($parent_course, $course->ID); ?>>
                        <?php echo esc_html($course->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function render_course_builder_meta_box($post) {
        wp_nonce_field('smlms_save_builder_meta', 'smlms_builder_nonce');
        $raw_tree = get_post_meta($post->ID, '_smlms_course_tree_json', true);
        if (empty($raw_tree)) $raw_tree = '[]';
        ?>
        <div class="smlms-builder-container">
            <div class="smlms-builder-header">
                <span class="smlms-steps-count" id="smlms-steps-counter">0 steps in this Course</span>
                <a href="#" id="smlms-toggle-all-steps" class="smlms-toggle-all">Expand All &#9660;</a>
            </div>
            <div id="smlms-builder-canvas" class="smlms-builder-canvas"></div>
            <input type="hidden" name="smlms_course_tree_json" id="smlms_course_tree_json" value="<?php echo esc_attr($raw_tree); ?>">
        </div>
        <?php
    }

    public function render_sidebar_lessons_box($post) {
        $lessons = get_posts(['post_type' => 'smlms_lesson', 'posts_per_page' => -1, 'post_status' => 'publish']);
        ?>
        <div class="smlms-sidebar-picker-box" data-type="lesson">
            <input type="text" class="smlms-picker-search widefat" placeholder="Search Lessons...">
            <div class="smlms-picker-items">
                <?php foreach ($lessons as $lesson): ?>
                    <label class="smlms-picker-row">
                        <input type="checkbox" class="smlms-picker-cb" value="<?php echo $lesson->ID; ?>" data-title="<?php echo esc_attr($lesson->post_title); ?>">
                        <span class="smlms-badge smlms-badge-lesson">L</span>
                        <span class="smlms-item-title"><?php echo esc_html($lesson->post_title); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="smlms-picker-footer">
                <button type="button" class="button button-primary smlms-add-lessons-btn">Add to Builder</button>
            </div>
        </div>
        <?php
    }

    public function render_sidebar_topics_box($post) {
        $topics = get_posts(['post_type' => 'smlms_topic', 'posts_per_page' => -1, 'post_status' => 'publish']);
        ?>
        <div class="smlms-sidebar-picker-box" data-type="topic">
            <input type="text" class="smlms-picker-search widefat" placeholder="Search Topics...">
            <div class="smlms-picker-items">
                <?php foreach ($topics as $topic): ?>
                    <label class="smlms-picker-row">
                        <input type="checkbox" class="smlms-picker-cb" value="<?php echo $topic->ID; ?>" data-title="<?php echo esc_attr($topic->post_title); ?>">
                        <span class="smlms-badge smlms-badge-topic">T</span>
                        <span class="smlms-item-title"><?php echo esc_html($topic->post_title); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="smlms-picker-footer">
                <button type="button" class="button button-primary smlms-add-topics-btn">Add Topics to Lesson</button>
            </div>
        </div>
        <?php
    }

    public function save_meta_box_data($post_id) {
        global $wpdb;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

        // 1. Enrollment Settings
        if (isset($_POST['smlms_enrollment_nonce']) && wp_verify_nonce($_POST['smlms_enrollment_nonce'], 'smlms_save_enrollment_meta')) {
            if (isset($_POST['smlms_price_type'])) update_post_meta($post_id, '_smlms_price_type', sanitize_text_field($_POST['smlms_price_type']));
            if (isset($_POST['smlms_price'])) update_post_meta($post_id, '_smlms_price', sanitize_text_field($_POST['smlms_price']));
            if (isset($_POST['smlms_button_url'])) update_post_meta($post_id, '_smlms_button_url', esc_url_raw($_POST['smlms_button_url']));
        }

        // 2. Course Students Enrollment Sync
        if (isset($_POST['smlms_students_nonce']) && wp_verify_nonce($_POST['smlms_students_nonce'], 'smlms_save_students_meta')) {
            $assigned_ids = isset($_POST['smlms_enrolled_user_ids']) && is_array($_POST['smlms_enrolled_user_ids']) ? array_map('intval', $_POST['smlms_enrolled_user_ids']) : [];

            $wpdb->delete($wpdb->prefix . 'smlms_enrollments', ['course_id' => $post_id], ['%d']);

            foreach ($assigned_ids as $uid) {
                $wpdb->insert(
                    $wpdb->prefix . 'smlms_enrollments',
                    [
                        'user_id'     => $uid,
                        'course_id'   => $post_id,
                        'status'      => 'active',
                        'enrolled_at' => current_time('mysql')
                    ],
                    ['%d', '%d', '%s', '%s']
                );
            }

            update_post_meta($post_id, '_smlms_students_enrolled', count($assigned_ids));
        }

        // 3. Course Custom Meta
        if (isset($_POST['smlms_course_custom_nonce']) && wp_verify_nonce($_POST['smlms_course_custom_nonce'], 'smlms_save_course_custom_meta')) {
            $fields = ['smlms_course_headline', 'smlms_course_short_desc', 'smlms_duration', 'smlms_level', 'smlms_language', 'smlms_num_lessons', 'smlms_students_enrolled', 'smlms_content_type', 'smlms_media_embed'];
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, '_' . $field, sanitize_textarea_field($_POST[$field]));
                }
            }
        }

        // 4. Custom Item Meta (Lessons only for Sample status, plus Duration & Content Type)
        if (isset($_POST['smlms_custom_item_nonce']) && wp_verify_nonce($_POST['smlms_custom_item_nonce'], 'smlms_save_custom_item_meta')) {
            if (get_post_type($post_id) === 'smlms_lesson') {
                update_post_meta($post_id, '_smlms_is_sample', isset($_POST['smlms_is_sample']) ? '1' : '0');
            }
            if (isset($_POST['smlms_duration'])) update_post_meta($post_id, '_smlms_duration', sanitize_text_field($_POST['smlms_duration']));
            if (isset($_POST['smlms_content_type'])) update_post_meta($post_id, '_smlms_content_type', sanitize_text_field($_POST['smlms_content_type']));
        }

        // 5. Display and Content Options
        if (isset($_POST['smlms_options_nonce']) && wp_verify_nonce($_POST['smlms_options_nonce'], 'smlms_save_options_meta')) {
            update_post_meta($post_id, '_smlms_materials_enabled', isset($_POST['smlms_materials_enabled']) ? '1' : '0');
            if (isset($_POST['smlms_materials'])) update_post_meta($post_id, '_smlms_materials', $_POST['smlms_materials']);

            update_post_meta($post_id, '_smlms_video_enabled', isset($_POST['smlms_video_enabled']) ? '1' : '0');
            if (isset($_POST['smlms_video_id'])) update_post_meta($post_id, '_smlms_video_id', sanitize_textarea_field($_POST['smlms_video_id']));
        }

        // 6. Parents & Pricing
        if (isset($_POST['smlms_parent_lesson_id'])) update_post_meta($post_id, '_smlms_parent_lesson_id', sanitize_text_field($_POST['smlms_parent_lesson_id']));
        if (isset($_POST['smlms_parent_course_id'])) update_post_meta($post_id, '_smlms_parent_course_id', sanitize_text_field($_POST['smlms_parent_course_id']));
        if (isset($_POST['smlms_price_type'])) update_post_meta($post_id, '_smlms_price_type', sanitize_text_field($_POST['smlms_price_type']));
        if (isset($_POST['smlms_price'])) update_post_meta($post_id, '_smlms_price', sanitize_text_field($_POST['smlms_price']));

        // 7. Builder JSON
        if (isset($_POST['smlms_builder_nonce']) && wp_verify_nonce($_POST['smlms_builder_nonce'], 'smlms_save_builder_meta')) {
            if (isset($_POST['smlms_course_tree_json'])) {
                $json_str = wp_unslash($_POST['smlms_course_tree_json']);
                update_post_meta($post_id, '_smlms_course_tree_json', $json_str);

                $tree_data = json_decode($json_str, true);
                if (is_array($tree_data)) {
                    foreach ($tree_data as $lesson_node) {
                        $lesson_id = intval($lesson_node['id']);
                        update_post_meta($lesson_id, '_smlms_parent_course_id', $post_id);

                        if (!empty($lesson_node['topics']) && is_array($lesson_node['topics'])) {
                            foreach ($lesson_node['topics'] as $topic_node) {
                                $topic_id = intval($topic_node['id']);
                                update_post_meta($topic_id, '_smlms_parent_lesson_id', $lesson_id);
                            }
                        }
                    }
                }
            }
        }
    }

    public function fix_parent_menu_highlight($parent_file) {
        global $current_screen;
        if ($current_screen && in_array($current_screen->post_type, ['smlms_course', 'smlms_lesson', 'smlms_topic'], true)) {
            return 'smlms_main_menu';
        }
        return $parent_file;
    }

    public function render_setup_page() { echo '<div class="wrap"><h1>LMS Setup</h1></div>'; }
    public function render_orders_page() { echo '<div class="wrap"><h1>LMS Orders</h1></div>'; }
    public function render_settings_page() { echo '<div class="wrap"><h1>LMS Settings</h1></div>'; }
}