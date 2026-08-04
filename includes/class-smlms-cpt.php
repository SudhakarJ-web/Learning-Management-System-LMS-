<?php
/**
 * Interactive Sabin Mathew Builder, Custom Columns, & CPT Handler
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

        // Custom Columns for Courses List Table
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
        add_meta_box('smlms_course_builder_box', 'Sabin Mathew Builder', [$this, 'render_course_builder_meta_box'], 'smlms_course', 'normal', 'high');
        add_meta_box('smlms_course_settings', 'Course Pricing & Type', [$this, 'render_course_meta_box'], 'smlms_course', 'side', 'default');
        add_meta_box('smlms_sidebar_lessons_box', 'Lessons', [$this, 'render_sidebar_lessons_box'], 'smlms_course', 'side', 'default');
        add_meta_box('smlms_sidebar_topics_box', 'Topics', [$this, 'render_sidebar_topics_box'], 'smlms_course', 'side', 'default');
        add_meta_box('smlms_topic_settings', 'Topic Settings & Video Provider', [$this, 'render_topic_meta_box'], 'smlms_topic', 'normal', 'high');
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

    public function render_topic_meta_box($post) {
        wp_nonce_field('smlms_save_topic_meta', 'smlms_topic_nonce');
        $parent_lesson = get_post_meta($post->ID, '_smlms_parent_lesson_id', true);
        $video_id      = get_post_meta($post->ID, '_smlms_video_id', true);
        $materials     = get_post_meta($post->ID, '_smlms_materials', true);
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
        <p>
            <label><strong>Cloudflare / Vimeo Video ID:</strong></label><br>
            <input type="text" name="smlms_video_id" value="<?php echo esc_attr($video_id); ?>" class="widefat">
        </p>
        <p>
            <label><strong>Topic Materials:</strong></label><br>
            <textarea name="smlms_materials" rows="4" class="widefat"><?php echo esc_textarea($materials); ?></textarea>
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
                <?php if (empty($lessons)): ?>
                    <p class="smlms-no-items">No published lessons found.</p>
                <?php else: ?>
                    <?php foreach ($lessons as $lesson): ?>
                        <label class="smlms-picker-row">
                            <input type="checkbox" class="smlms-picker-cb" value="<?php echo $lesson->ID; ?>" data-title="<?php echo esc_attr($lesson->post_title); ?>">
                            <span class="smlms-badge smlms-badge-lesson">L</span>
                            <span class="smlms-item-title"><?php echo esc_html($lesson->post_title); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                <?php if (empty($topics)): ?>
                    <p class="smlms-no-items">No published topics found.</p>
                <?php else: ?>
                    <?php foreach ($topics as $topic): ?>
                        <label class="smlms-picker-row">
                            <input type="checkbox" class="smlms-picker-cb" value="<?php echo $topic->ID; ?>" data-title="<?php echo esc_attr($topic->post_title); ?>">
                            <span class="smlms-badge smlms-badge-topic">T</span>
                            <span class="smlms-item-title"><?php echo esc_html($topic->post_title); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="smlms-picker-footer">
                <button type="button" class="button button-primary smlms-add-topics-btn">Add Topics to Lesson</button>
            </div>
        </div>
        <?php
    }

    public function save_meta_box_data($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (isset($_POST['smlms_builder_nonce']) && wp_verify_nonce($_POST['smlms_builder_nonce'], 'smlms_save_builder_meta')) {
            if (isset($_POST['smlms_course_tree_json'])) {
                $json_str = wp_unslash($_POST['smlms_course_tree_json']);
                update_post_meta($post_id, '_smlms_course_tree_json', $json_str);

                $tree_data = json_decode($json_str, true);
                if (is_array($tree_data)) {
                    foreach ($tree_data as $l_index => $lesson_node) {
                        $lesson_id = intval($lesson_node['id']);
                        update_post_meta($lesson_id, '_smlms_parent_course_id', $post_id);
                        wp_update_post(['ID' => $lesson_id, 'menu_order' => $l_index + 1]);

                        if (!empty($lesson_node['topics']) && is_array($lesson_node['topics'])) {
                            foreach ($lesson_node['topics'] as $t_index => $topic_node) {
                                $topic_id = intval($topic_node['id']);
                                update_post_meta($topic_id, '_smlms_parent_lesson_id', $lesson_id);
                                wp_update_post(['ID' => $topic_id, 'menu_order' => $t_index + 1]);
                            }
                        }
                    }
                }
            }
        }

        if (isset($_POST['smlms_course_nonce']) && wp_verify_nonce($_POST['smlms_course_nonce'], 'smlms_save_course_meta')) {
            if (isset($_POST['smlms_price_type'])) update_post_meta($post_id, '_smlms_price_type', sanitize_text_field($_POST['smlms_price_type']));
            if (isset($_POST['smlms_price'])) update_post_meta($post_id, '_smlms_price', sanitize_text_field($_POST['smlms_price']));
        }

        if (isset($_POST['smlms_topic_nonce']) && wp_verify_nonce($_POST['smlms_topic_nonce'], 'smlms_save_topic_meta')) {
            if (isset($_POST['smlms_parent_lesson_id'])) update_post_meta($post_id, '_smlms_parent_lesson_id', sanitize_text_field($_POST['smlms_parent_lesson_id']));
            if (isset($_POST['smlms_video_id'])) update_post_meta($post_id, '_smlms_video_id', sanitize_text_field($_POST['smlms_video_id']));
            if (isset($_POST['smlms_materials'])) update_post_meta($post_id, '_smlms_materials', $_POST['smlms_materials']);
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