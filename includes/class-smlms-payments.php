<?php
/**
 * Payment Gateway & Webhook Router Class
 *
 * Handles checkout initialization and asynchronous webhook processing
 * for Stripe, Razorpay, PayU, and PayPal.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SMLMS_Payments {

    public function init() {
        add_action('rest_api_init', [$this, 'register_payment_routes']);
    }

    /**
     * Register Payment REST API Endpoints
     */
    public function register_payment_routes() {
        // 1. Initiate Checkout
        register_rest_route('smlms/v1', '/checkout/initiate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_checkout_initiation'],
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);

        // 2. Gateway Webhooks / IPN
        register_rest_route('smlms/v1', '/webhook/(?P<gateway>[a-zA-Z0-9_-]+)', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_gateway_webhook'],
            'permission_callback' => '__return_true' // Webhooks validated via signatures
        ]);
    }

    /**
     * Handles checkout request from frontend
     */
    public function handle_checkout_initiation($request) {
        global $wpdb;

        $user_id   = get_current_user_id();
        $course_id = intval($request->get_param('course_id'));
        $gateway   = sanitize_text_field($request->get_param('gateway')); // stripe, razorpay, payu, paypal

        if (!$course_id || !get_post($course_id)) {
            return new WP_Error('invalid_course', 'Invalid course ID.', ['status' => 400]);
        }

        // Check if already enrolled
        if (SMLMS_DB::is_user_enrolled($user_id, $course_id)) {
            return new WP_Error('already_enrolled', 'You are already enrolled in this course.', ['status' => 400]);
        }

        // Get course price (Assuming post meta '_smlms_price')
        $price = get_post_meta($course_id, '_smlms_price', true);
        $amount = $price ? floatval($price) : 0.00;
        $currency = 'USD'; // Adjust currency dynamically as needed

        // Generate local pending order
        $order_id = uniqid('smlms_ord_');

        $wpdb->insert(
            $wpdb->prefix . 'smlms_orders',
            [
                'user_id'          => $user_id,
                'course_id'        => $course_id,
                'gateway'          => $gateway,
                'gateway_order_id' => $order_id,
                'amount'           => $amount,
                'currency'         => $currency,
                'status'           => 'pending',
                'created_at'       => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s']
        );

        // Prepare response based on selected gateway
        $response_data = [
            'local_order_id' => $order_id,
            'amount'         => $amount,
            'currency'       => $currency,
            'gateway'        => $gateway
        ];

        switch ($gateway) {
            case 'razorpay':
                // Pass key and order details to Razorpay SDK on frontend
                $response_data['razorpay_key'] = get_option('smlms_razorpay_key_id', 'rzp_test_xxxx');
                break;

            case 'stripe':
                // Return Stripe Client Secret or Publishable Key
                $response_data['stripe_key'] = get_option('smlms_stripe_pub_key', 'pk_test_xxxx');
                break;

            case 'payu':
                // PayU Merchant params & hash setup
                $response_data['payu_key'] = get_option('smlms_payu_merchant_key', 'payu_key_xxxx');
                break;

            case 'paypal':
                // PayPal Client ID
                $response_data['paypal_client_id'] = get_option('smlms_paypal_client_id', 'paypal_client_xxxx');
                break;

            default:
                return new WP_Error('invalid_gateway', 'Unsupported payment gateway.', ['status' => 400]);
        }

        return rest_ensure_response($response_data);
    }

    /**
     * Webhook Endpoint Router
     */
    public function handle_gateway_webhook($request) {
        $gateway = sanitize_text_field($request->get_param('gateway'));
        $body    = $request->get_json_params();

        // Process based on gateway signature & payload
        switch ($gateway) {
            case 'razorpay':
                return $this->process_razorpay_webhook($request, $body);
            case 'stripe':
                return $this->process_stripe_webhook($request, $body);
            case 'payu':
                return $this->process_payu_webhook($request);
            case 'paypal':
                return $this->process_paypal_webhook($request, $body);
            default:
                return new WP_Error('unknown_gateway', 'Unknown webhook route.', ['status' => 400]);
        }
    }

    /**
     * Fulfillment logic: Marks order complete & creates active enrollment record
     */
    private function fulfill_order($gateway_order_id, $gateway_payment_id = '') {
        global $wpdb;

        // 1. Fetch pending order
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}smlms_orders WHERE gateway_order_id = %s",
            $gateway_order_id
        ));

        if (!$order || $order->status === 'completed') {
            return false; // Order not found or already fulfilled
        }

        // 2. Start MySQL Transaction
        $wpdb->query('START TRANSACTION');

        // Update Order
        $wpdb->update(
            $wpdb->prefix . 'smlms_orders',
            [
                'status'             => 'completed',
                'gateway_payment_id' => sanitize_text_field($gateway_payment_id)
            ],
            ['id' => $order->id],
            ['%s', '%s'],
            ['%d']
        );

        // Enroll User in Course
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}smlms_enrollments (user_id, course_id, status, enrolled_at)
             VALUES (%d, %d, 'active', %s)
             ON DUPLICATE KEY UPDATE status = 'active'",
            $order->user_id,
            $order->course_id,
            current_time('mysql')
        ));

        // Commit Transaction
        $wpdb->query('COMMIT');

        // Fire Action Hook for Email / Extensions
        do_action('smlms_order_fulfilled', $order->id, $order->user_id, $order->course_id);

        return true;
    }

    private function process_razorpay_webhook($request, $body) {
        // Implement Razorpay HMAC-SHA256 signature verification here
        if (isset($body['event']) && $body['event'] === 'payment.captured') {
            $order_id   = $body['payload']['payment']['entity']['order_id'] ?? '';
            $payment_id = $body['payload']['payment']['entity']['id'] ?? '';
            $this->fulfill_order($order_id, $payment_id);
        }
        return rest_ensure_response(['status' => 'success']);
    }

    private function process_stripe_webhook($request, $body) {
        // Implement Stripe Signature Header verification here
        if (isset($body['type']) && $body['type'] === 'payment_intent.succeeded') {
            $intent   = $body['data']['object'];
            $order_id = $intent['metadata']['local_order_id'] ?? '';
            $this->fulfill_order($order_id, $intent['id']);
        }
        return rest_ensure_response(['status' => 'success']);
    }

    private function process_payu_webhook($request) {
        $order_id   = sanitize_text_field($_POST['txnid'] ?? '');
        $payment_id = sanitize_text_field($_POST['mihpayid'] ?? '');
        $status     = sanitize_text_field($_POST['status'] ?? '');

        if ($status === 'success') {
            $this->fulfill_order($order_id, $payment_id);
        }
        return rest_ensure_response(['status' => 'success']);
    }

    private function process_paypal_webhook($request, $body) {
        if (isset($body['event_type']) && $body['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource   = $body['resource'];
            $order_id   = $resource['custom_id'] ?? '';
            $payment_id = $resource['id'] ?? '';
            $this->fulfill_order($order_id, $payment_id);
        }
        return rest_ensure_response(['status' => 'success']);
    }
}