<?php
/**
 * فئة نظام الإشعارات
 */

namespace Beiruttime\OSINT\Notifications;

if (!defined('ABSPATH')) {
    exit;
}

class Notification_System {
    
    private $channels = array('admin', 'email', 'webhook');
    
    public function __construct() {
        add_action('admin_init', array($this, 'check_alerts'));
    }
    
    public function send($type, $message, $data = array()) {
        foreach ($this->channels as $channel) {
            $method = 'send_via_' . $channel;
            if (method_exists($this, $method)) {
                $this->$method($type, $message, $data);
            }
        }
    }
    
    private function send_via_admin($type, $message, $data) {
        set_transient('osint_notification_' . md5($message), array(
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'time' => time()
        ), HOUR_IN_SECONDS);
    }
    
    private function send_via_email($type, $message, $data) {
        $to = get_option('admin_email');
        $subject = sprintf('[OSINT %s] %s', strtoupper($type), $message);
        wp_mail($to, $subject, $message);
    }
    
    private function send_via_webhook($type, $message, $data) {
        $webhook_url = get_option('osint_webhook_url');
        if (!$webhook_url) return;
        
        wp_remote_post($webhook_url, array(
            'body' => json_encode(array(
                'type' => $type,
                'message' => $message,
                'data' => $data,
                'timestamp' => current_time('mysql')
            )),
            'headers' => array('Content-Type' => 'application/json')
        ));
    }
    
    public function check_alerts() {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        $alerts = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE alert_flag = 1 AND alert_status = 'pending' LIMIT 5"
        );
        
        foreach ($alerts as $alert) {
            $this->send('alert', $alert->title, array('id' => $alert->id));
            $wpdb->update($table, array('alert_status' => 'sent'), array('id' => $alert->id));
        }
    }
    
    public function render_admin_notifications() {
        $transients = get_transient('osint_notification_%');
        if ($transients) {
            foreach ($transients as $notification) {
                echo '<div class="notice notice-' . esc_attr($notification['type']) . ' is-dismissible">';
                echo '<p>' . esc_html($notification['message']) . '</p>';
                echo '</div>';
            }
        }
    }
}
