<?php
if (!defined('ABSPATH')) exit;

class SMLMS_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE {$wpdb->prefix}smlms_enrollments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            course_id bigint(20) UNSIGNED NOT NULL,
            enrolled_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(50) DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY user_course (user_id, course_id),
            KEY user_id (user_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}smlms_progress (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            topic_id bigint(20) UNSIGNED NOT NULL,
            watched_seconds int(11) DEFAULT 0,
            is_completed tinyint(1) DEFAULT 0,
            completed_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_topic (user_id, topic_id),
            KEY user_id (user_id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}smlms_orders (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            course_id bigint(20) UNSIGNED NOT NULL,
            gateway varchar(50) NOT NULL,
            gateway_order_id varchar(255) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}