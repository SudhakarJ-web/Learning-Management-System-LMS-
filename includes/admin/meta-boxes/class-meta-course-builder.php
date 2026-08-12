<?php
/**
 * Sabin Mathew LMS - Admin Course Builder & Side Pickers
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Course_Builder {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_boxes']);
    }

    public static function register_meta_boxes() {
        // Main Builder Tree Meta Box
        add_meta_box(
            'smlms_course_builder_meta',
            'Sabin Mathew Builder',
            [__CLASS__, 'render_builder_meta'],
            'smlms_course',
            'normal',
            'high'
        );

        // Lessons Sidebar Picker Meta Box
        add_meta_box(
            'smlms_lessons_picker_meta',
            'Lessons',
            [__CLASS__, 'render_lessons_picker'],
            'smlms_course',
            'side',
            'default'
        );

        // Topics Sidebar Picker Meta Box
        add_meta_box(
            'smlms_topics_picker_meta',
            'Topics',
            [__CLASS__, 'render_topics_picker'],
            'smlms_course',
            'side',
            'default'
        );
    }

    /**
     * Render Main Drag-and-Drop Builder Tree Canvas
     */
    public static function render_builder_meta($post) {
        $tree_json = get_post_meta($post->ID, '_smlms_course_tree_json', true);
        $tree_data = !empty($tree_json) ? json_decode($tree_json, true) : [];
        if (!is_array($tree_data)) {
            $tree_data = [];
        }

        // Count total steps (Lessons + Topics)
        $total_steps = 0;
        foreach ($tree_data as $l) {
            $total_steps++;
            if (!empty($l['topics']) && is_array($l['topics'])) {
                $total_steps += count($l['topics']);
            }
        }
        ?>

        <div class="smlms-builder-wrapper">
            <div class="smlms-builder-header">
                <span class="smlms-step-count-label"><strong id="smlms-builder-step-count"><?php echo intval($total_steps); ?></strong> steps in this Course</span>
                <button type="button" id="smlms-builder-toggle-all" class="button-link smlms-toggle-link">Expand All &#9660;</button>
            </div>

            <div id="smlms-course-builder-tree" class="smlms-builder-tree-dropzone">
                <?php if (!empty($tree_data)): ?>
                    <?php foreach ($tree_data as $l_idx => $l_node): 
                        $lesson_id = intval($l_node['id'] ?? 0);
                        $lesson_post = get_post($lesson_id);
                        if (!$lesson_post) continue;
                    ?>
                        <div class="smlms-builder-lesson" data-id="<?php echo esc_attr($lesson_id); ?>">
                            <div class="smlms-lesson-bar">
                                <div class="smlms-lesson-bar-left">
                                    <span class="dashicons dashicons-menu handle-icon"></span>
                                    <span class="smlms-type-badge lesson-badge">L</span>
                                    <strong class="smlms-lesson-name"><?php echo esc_html($lesson_post->post_title); ?></strong>
                                </div>
                                <button type="button" class="button smlms-remove-lesson-btn">Remove</button>
                            </div>

                            <div class="smlms-builder-topic-list">
                                <?php if (!empty($l_node['topics']) && is_array($l_node['topics'])): ?>
                                    <?php foreach ($l_node['topics'] as $t_node): 
                                        $topic_id = intval($t_node['id'] ?? 0);
                                        $topic_post = get_post($topic_id);
                                        if (!$topic_post) continue;
                                    ?>
                                        <div class="smlms-builder-topic-item" data-id="<?php echo esc_attr($topic_id); ?>">
                                            <div class="smlms-topic-bar-left">
                                                <span class="dashicons dashicons-menu handle-icon"></span>
                                                <span class="smlms-type-badge topic-badge">T</span>
                                                <span class="smlms-topic-name"><?php echo esc_html($topic_post->post_title); ?></span>
                                            </div>
                                            <button type="button" class="smlms-remove-topic-btn">&times; Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <input type="hidden" id="_smlms_course_tree_json" name="_smlms_course_tree_json" value="<?php echo esc_attr($tree_json); ?>">
        </div>
        <?php
    }

    /**
     * Render Lessons Picker in Right Sidebar
     */
    public static function render_lessons_picker($post) {
        $lessons = get_posts([
            'post_type'      => 'smlms_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);
        ?>
        <div class="smlms-picker-wrapper">
            <input type="text" class="widefat smlms-picker-search" placeholder="Search Lessons...">
            <div class="smlms-picker-box" id="smlms-lessons-picker-list">
                <?php if (!empty($lessons)): ?>
                    <?php foreach ($lessons as $lesson): ?>
                        <label>
                            <input type="checkbox" value="<?php echo esc_attr($lesson->ID); ?>">
                            <span class="smlms-type-badge lesson-badge">L</span>
                            <span><?php echo esc_html($lesson->post_title); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="smlms-no-items">No lessons found.</p>
                <?php endif; ?>
            </div>
            <button type="button" id="smlms-add-lessons-btn" class="button button-primary smlms-btn-purple">Add to Builder</button>
        </div>
        <?php
    }

    /**
     * Render Topics Picker in Right Sidebar
     */
    public static function render_topics_picker($post) {
        $topics = get_posts([
            'post_type'      => 'smlms_topic',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);
        ?>
        <div class="smlms-picker-wrapper">
            <input type="text" class="widefat smlms-picker-search" placeholder="Search Topics...">
            <div class="smlms-picker-box" id="smlms-topics-picker-list">
                <?php if (!empty($topics)): ?>
                    <?php foreach ($topics as $topic): ?>
                        <label>
                            <input type="checkbox" value="<?php echo esc_attr($topic->ID); ?>">
                            <span class="smlms-type-badge topic-badge">T</span>
                            <span><?php echo esc_html($topic->post_title); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="smlms-no-items">No topics found.</p>
                <?php endif; ?>
            </div>
            <button type="button" id="smlms-add-topics-btn" class="button button-primary smlms-btn-purple">Add Topics to Lesson</button>
        </div>
        <?php
    }
}
SMLMS_Meta_Course_Builder::init();