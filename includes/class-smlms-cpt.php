<?php
/**
 * Admin Menu & CPT Registration Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_CPT {

    public function init() {
        add_action('init', [$this, 'register_cpts']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_custom_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        add_filter('parent_file', [$this, 'fix_parent_menu_highlight']);
    }

    /**
     * Register CPTs (Set show_in_menu to false for manual menu ordering)
     */
    public function register_cpts() {
        // 1. Courses CPT
        register_post_type('smlms_course', [
            'labels'       => [
                'name'          => 'Courses',
                'singular_name' => 'Course',
                'add_new_item'  => 'Add New Course',
                'edit_item'     => 'Edit Course'
            ],
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => ['slug' => 'courses'],
            'supports'     => ['title', 'editor', 'thumbnail', 'page-attributes'],
            'show_ui'      => true,
            'show_in_menu' => false, // Handled manually in register_admin_menu()
            'show_in_rest' => true,
        ]);

        // 2. Lessons CPT
        register_post_type('smlms_lesson', [
            'labels'       => [
                'name'          => 'Lessons',
                'singular_name' => 'Lesson',
                'add_new_item'  => 'Add New Lesson'
            ],
            'public'       => true,
            'supports'     => ['title', 'editor', 'page-attributes'],
            'show_ui'      => true,
            'show_in_menu' => false, // Handled manually in register_admin_menu()
            'show_in_rest' => true,
        ]);

        // 3. Topics CPT
        register_post_type('smlms_topic', [
            'labels'       => [
                'name'          => 'Topics',
                'singular_name' => 'Topic',
                'add_new_item'  => 'Add New Topic'
            ],
            'public'       => true,
            'supports'     => ['title', 'editor', 'page-attributes'],
            'show_ui'      => true,
            'show_in_menu' => false, // Handled manually in register_admin_menu()
            'show_in_rest' => true,
        ]);
    }

    /**
     * Register Main Parent Menu and Submenus in Exact Sequence
     */
    public function register_admin_menu() {
        // Main Parent Menu
        add_menu_page(
            'Sabin Mathew LMS',
            'Sabin Mathew LMS',
            'manage_options',
            'smlms_main_menu',
            [$this, 'render_setup_page'],
            'dashicons-welcome-learn-more',
            30
        );

        // 1. Setup (First item - replaces duplicate parent title)
        add_submenu_page(
            'smlms_main_menu',
            'Setup',
            'Setup',
            'manage_options',
            'smlms_main_menu',
            [$this, 'render_setup_page']
        );

        // 2. Courses
        add_submenu_page(
            'smlms_main_menu',
            'Courses',
            'Courses',
            'manage_options',
            'edit.php?post_type=smlms_course'
        );

        // 3. Lessons
        add_submenu_page(
            'smlms_main_menu',
            'Lessons',
            'Lessons',
            'manage_options',
            'edit.php?post_type=smlms_lesson'
        );

        // 4. Topics
        add_submenu_page(
            'smlms_main_menu',
            'Topics',
            'Topics',
            'manage_options',
            'edit.php?post_type=smlms_topic'
        );

        // 5. Orders
        add_submenu_page(
            'smlms_main_menu',
            'Orders',
            'Orders',
            'manage_options',
            'smlms_orders',
            [$this, 'render_orders_page']
        );

        // 6. Settings
        add_submenu_page(
            'smlms_main_menu',
            'Settings',
            'Settings',
            'manage_options',
            'smlms_settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Keep Sabin Mathew LMS menu highlighted when editing CPT posts
     */
    public function fix_parent_menu_highlight($parent_file) {
        global $current_screen;
        if ($current_screen && in_array($current_screen->post_type, ['smlms_course', 'smlms_lesson', 'smlms_topic'], true)) {
            return 'smlms_main_menu';
        }
        return $parent_file;
    }

    public function add_custom_meta_boxes() {
        add_meta_box(
            'smlms_topic_settings',
            'Topic Settings & Video Provider',
            [$this, 'render_topic_meta_box'],
            'smlms_topic',
            'normal',
            'high'
        );
    }

    public function render_topic_meta_box($post) {
        wp_nonce_field('smlms_save_topic_meta', 'smlms_topic_nonce');
        
        $parent_lesson = get_post_meta($post->ID, '_smlms_parent_lesson_id', true);
        $video_id      = get_post_meta($post->ID, '_smlms_video_id', true);
        $materials     = get_post_meta($post->ID, '_smlms_materials', true);

        $lessons = get_posts(['post_type' => 'smlms_lesson', 'numberposts' => -1]);
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
            <label><strong>Topic Materials & Amazon Purchase Links:</strong></label><br>
            <textarea name="smlms_materials" rows="4" class="widefat"><?php echo esc_textarea($materials); ?></textarea>
        </p>
        <?php
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['smlms_topic_nonce']) || !wp_verify_nonce($_POST['smlms_topic_nonce'], 'smlms_save_topic_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (isset($_POST['smlms_parent_lesson_id'])) {
            update_post_meta($post_id, '_smlms_parent_lesson_id', sanitize_text_field($_POST['smlms_parent_lesson_id']));
        }
        if (isset($_POST['smlms_video_id'])) {
            update_post_meta($post_id, '_smlms_video_id', sanitize_text_field($_POST['smlms_video_id']));
        }
        if (isset($_POST['smlms_materials'])) {
            update_post_meta($post_id, '_smlms_materials', $_POST['smlms_materials']);
        }
    }

    public function render_setup_page() {
        echo '<div class="wrap"><h1>LMS Setup</h1><p>Setup instructions and system status.</p></div>';
    }

    public function render_orders_page() {
        echo '<div class="wrap"><h1>LMS Orders</h1><p>Transactions from Stripe, Razorpay, PayU, and PayPal will appear here.</p></div>';
    }

    public function render_settings_page() {
        echo '<div class="wrap"><h1>LMS Settings</h1><p>Configure API keys and general options.</p></div>';
    }
}