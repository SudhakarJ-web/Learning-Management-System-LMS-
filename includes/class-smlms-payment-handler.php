<?php
/**
 * Payment Completion Automatic Enrollment Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Payment_Handler {

    public static function init() {
        add_action('woocommerce_order_status_completed', [__CLASS__, 'handle_woocommerce_payment']);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'handle_woocommerce_payment']);
    }

    public static function handle_woocommerce_payment($order_id) {
        if (!$order_id) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        $user_id = $order->get_user_id();
        if (!$user_id) return;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            $course_id = get_post_meta($product_id, '_smlms_related_course_id', true);
            if (empty($course_id) && get_post_type($product_id) === 'smlms_course') {
                $course_id = $product_id;
            }

            if ($course_id) {
                SMLMS_DB::enroll_student($user_id, $course_id);
            }
        }
    }
}
SMLMS_Payment_Handler::init();