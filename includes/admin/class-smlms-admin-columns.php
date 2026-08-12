<?php
/**
 * Admin CPT Table Columns Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Admin_Columns {

    public static function init() {
        add_filter('manage_smlms_course_posts_columns', [__CLASS__, 'set_course_columns']);
        add_action('manage_smlms_course_posts_custom_column', [__CLASS__, 'render_course_columns'], 10, 2);
    }

    public static function set_course_columns($columns) {
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

    public static function render_course_columns($column, $post_id) {
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
}
SMLMS_Admin_Columns::init();