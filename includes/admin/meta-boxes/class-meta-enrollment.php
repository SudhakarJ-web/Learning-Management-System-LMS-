<?php
/**
 * Course Enrollment Access Settings Meta Box - SaaS Card Redesign
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Meta_Enrollment {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register']);
    }

    public static function register() {
        add_meta_box(
            'smlms_course_enrollment_box', 
            'Course Access & Enrollment Settings', 
            [__CLASS__, 'render'], 
            'smlms_course', 
            'normal', 
            'high'
        );
    }

    public static function render($post) {
        wp_nonce_field('smlms_save_enrollment_meta', 'smlms_enrollment_nonce');

        $price_type = get_post_meta($post->ID, '_smlms_price_type', true) ?: 'closed';
        $price      = get_post_meta($post->ID, '_smlms_price', true);
        $button_url = get_post_meta($post->ID, '_smlms_button_url', true);

        $modes = [
            'open' => [
                'title' => 'Open',
                'badge' => 'Unprotected',
                'desc'  => 'The course is public. Any student can view content without logging in or enrolling.',
                'icon'  => 'dashicons-unlock'
            ],
            'free' => [
                'title' => 'Free',
                'badge' => 'Registration Required',
                'desc'  => 'The course is free, but students must register and enroll to access content.',
                'icon'  => 'dashicons-id-alt'
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
            ],
        ];
        ?>
        <div class="smlms-enrollment-card-container">
            <p class="smlms-admin-subheading">Select how students gain access to this course:</p>

            <div class="smlms-mode-cards-grid">
                <?php foreach ($modes as $key => $mode): 
                    $is_active = ($price_type === $key);
                ?>
                    <label class="smlms-mode-card <?php echo $is_active ? 'active' : ''; ?>">
                        <div class="smlms-mode-card-header">
                            <input type="radio" name="smlms_price_type" value="<?php echo esc_attr($key); ?>" <?php checked($price_type, $key); ?> class="smlms-enroll-mode-radio">
                            <span class="dashicons <?php echo esc_attr($mode['icon']); ?> smlms-mode-icon"></span>
                            <span class="smlms-mode-title"><?php echo esc_html($mode['title']); ?></span>
                        </div>
                        <span class="smlms-mode-badge"><?php echo esc_html($mode['badge']); ?></span>
                        <p class="smlms-mode-desc"><?php echo esc_html($mode['desc']); ?></p>
                    </label>
                <?php endforeach; ?>
            </div>

            <div id="smlms-enroll-subfields" class="smlms-subfields-panel" style="<?php echo in_array($price_type, ['buy_now', 'recurring', 'closed']) ? '' : 'display:none;'; ?>">
                <div class="smlms-subfields-row">
                    <div class="smlms-input-field-group">
                        <label for="smlms_price"><strong>Course Price ($)</strong></label>
                        <div class="smlms-input-with-prefix">
                            <span class="smlms-prefix">$</span>
                            <input type="number" step="0.01" id="smlms_price" name="smlms_price" value="<?php echo esc_attr($price); ?>" placeholder="25.00" class="widefat">
                        </div>
                    </div>

                    <div class="smlms-input-field-group flex-2">
                        <label for="smlms_button_url"><strong>Custom Checkout / Enrollment Button URL</strong></label>
                        <input type="url" id="smlms_button_url" name="smlms_button_url" value="<?php echo esc_attr($button_url); ?>" placeholder="https://sabinmathew.com/cart/?add-to-cart=13875" class="widefat">
                        <span class="description">Direct checkout URL triggered when non-enrolled users click "ENROLL NOW".</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
SMLMS_Meta_Enrollment::init();