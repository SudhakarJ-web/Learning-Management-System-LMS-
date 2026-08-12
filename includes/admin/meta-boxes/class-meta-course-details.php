<?php
/**
 * Course Details & Access Settings Meta Box
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Course_Details {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_box']);
    }

    public static function register_meta_box() {
        add_meta_box(
            'smlms_course_details_meta',
            'Course Access & Enrollment Settings',
            [__CLASS__, 'render_meta_box'],
            'smlms_course',
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('smlms_course_details_nonce_action', 'smlms_course_details_nonce');

        $access_type = get_post_meta($post->ID, '_smlms_access_type', true) ?: 'closed';
        $price       = get_post_meta($post->ID, '_smlms_price', true) ?: '25';
        $button_url  = get_post_meta($post->ID, '_smlms_custom_checkout_url', true) ?: '';

        if (empty($button_url)) {
            $button_url = get_post_meta($post->ID, '_smlms_button_url', true) ?: '';
        }

        $options = [
            'open' => [
                'title' => 'Open',
                'badge' => 'Unprotected',
                'desc'  => 'The course is public. Any student can view content without logging in or enrolling.',
                'icon'  => 'dashicons-lock'
            ],
            'free' => [
                'title' => 'Free',
                'badge' => 'Registration Required',
                'desc'  => 'The course is free, but students must register and enroll to access content.',
                'icon'  => 'dashicons-welcome-write-blog'
            ],
            'buy_now' => [
                'title' => 'Buy Now',
                'badge' => 'One-time Payment',
                'desc'  => 'Protected via gateway. Students purchase a one-time fee for lifetime access.',
                'icon'  => 'dashicons-cart'
            ],
            'recurring' => [
                'title' => 'Recurring',
                'badge' => 'Subscription',
                'desc'  => 'Protected via gateway. Students pay a recurring subscription fee for access.',
                'icon'  => 'dashicons-update'
            ],
            'closed' => [
                'title' => 'Closed',
                'badge' => 'Admin / Integration Access',
                'desc'  => 'Only accessible via admin assignment or external cart integration.',
                'icon'  => 'dashicons-lock'
            ]
        ];
        ?>

        <div class="smlms-access-type-wrapper">
            <p class="smlms-access-subtitle">Select how students gain access to this course:</p>

            <div class="smlms-access-grid">
                <?php foreach ($options as $key => $opt): ?>
                    <label class="smlms-access-card <?php echo ($access_type === $key) ? 'active' : ''; ?>">
                        <input type="radio" name="_smlms_access_type" value="<?php echo esc_attr($key); ?>" <?php checked($access_type, $key); ?>>
                        <div class="smlms-access-card-inner">
                            <div class="smlms-access-card-header">
                                <span class="dashicons <?php echo esc_attr($opt['icon']); ?>"></span>
                                <strong class="smlms-access-card-title"><?php echo esc_html($opt['title']); ?></strong>
                            </div>
                            <span class="smlms-access-badge"><?php echo esc_html($opt['badge']); ?></span>
                            <p class="smlms-access-desc"><?php echo esc_html($opt['desc']); ?></p>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="smlms-access-field-row">
                <label for="_smlms_price" class="smlms-field-label">Course Price ($)</label>
                <input type="text" id="_smlms_price" name="_smlms_price" value="<?php echo esc_attr($price); ?>" class="widefat smlms-input">
            </div>

            <div class="smlms-access-field-row">
                <label for="_smlms_custom_checkout_url" class="smlms-field-label">Custom Checkout / Enrollment Button URL</label>
                <input type="url" id="_smlms_custom_checkout_url" name="_smlms_custom_checkout_url" value="<?php echo esc_attr($button_url); ?>" class="widefat smlms-input" placeholder="https://...">
                <span class="description">Direct checkout URL triggered when non-enrolled users click "ENROLL NOW".</span>
            </div>
        </div>
        <?php
    }
}
SMLMS_Meta_Course_Details::init();