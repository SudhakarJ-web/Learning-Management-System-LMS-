<?php
if (!defined('ABSPATH')) exit;

class SMLMS_REST_API {

    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // Telemetry Heartbeat
        register_rest_route('smlms/v1', '/progress/heartbeat', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_heartbeat'],
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);
    }

    public function handle_heartbeat($request) {
        $user_id         = get_current_user_id();
        $topic_id        = intval($request->get_param('topic_id'));
        $watched_seconds = intval($request->get_param('watched_seconds'));
        $total_duration  = intval($request->get_param('total_duration'));

        // Determine completion status (watched >= 90%)
        $is_completed = 0;
        if ($total_duration > 0 && ($watched_seconds / $total_duration) >= 0.90) {
            $is_completed = 1;
        }

        SMLMS_DB::save_topic_progress($user_id, $topic_id, $watched_seconds, $is_completed);

        return rest_ensure_response([
            'success'      => true,
            'can_complete' => (bool)$is_completed
        ]);
    }
}