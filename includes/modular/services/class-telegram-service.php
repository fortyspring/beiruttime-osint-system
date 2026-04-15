<?php
/**
 * Telegram Service - Modular System
 * Handles Telegram bot integration for notifications and alerts
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOD_Telegram_Service {
    
    private static $instance = null;
    private $bot_token;
    private $chat_id;
    private $api_url = 'https://api.telegram.org/bot';
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->bot_token = get_option('osint_pro_telegram_bot_token', '');
        $this->chat_id = get_option('osint_pro_telegram_chat_id', '');
    }
    
    /**
     * Check if Telegram is configured
     */
    public function is_configured() {
        return !empty($this->bot_token) && !empty($this->chat_id);
    }
    
    /**
     * Send message to Telegram
     */
    public function send_message($message, $parse_mode = 'HTML') {
        if (!$this->is_configured()) {
            SOD_Security_Logger::log('telegram_not_configured', [
                'attempt' => 'send_message'
            ]);
            return false;
        }
        
        // Rate limiting
        if (!SOD_Rate_Limiter::is_allowed('telegram_send', 60)) {
            SOD_Security_Logger::log('telegram_rate_limit', [
                'action' => 'send_message'
            ]);
            return false;
        }
        
        $url = $this->api_url . $this->bot_token . '/sendMessage';
        
        $data = [
            'chat_id' => $this->chat_id,
            'text' => $message,
            'parse_mode' => $parse_mode
        ];
        
        $response = wp_remote_post($url, [
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            SOD_Security_Logger::log('telegram_error', [
                'error' => $response->get_error_message(),
                'action' => 'send_message'
            ]);
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['ok']) && $body['ok'] === true) {
            SOD_Security_Logger::log('telegram_sent', [
                'message_length' => strlen($message),
                'chat_id' => $this->chat_id
            ]);
            return true;
        }
        
        SOD_Security_Logger::log('telegram_failed', [
            'response' => $body,
            'action' => 'send_message'
        ]);
        return false;
    }
    
    /**
     * Send alert notification
     */
    public function send_alert($alert_data) {
        $title = isset($alert_data['title']) ? $alert_data['title'] : '⚠️ OSINT Alert';
        $severity = isset($alert_data['severity']) ? $alert_data['severity'] : 'medium';
        $message = isset($alert_data['message']) ? $alert_data['message'] : '';
        $details = isset($alert_data['details']) ? $alert_data['details'] : [];
        
        $emoji = $this->get_severity_emoji($severity);
        
        $formatted_message = sprintf(
            "%s <b>%s</b>\n\n" .
            "<b>Severity:</b> %s\n" .
            "<b>Time:</b> %s\n\n" .
            "%s",
            $emoji,
            $title,
            ucfirst($severity),
            current_time('Y-m-d H:i:s'),
            esc_html($message)
        );
        
        if (!empty($details)) {
            $formatted_message .= "\n\n<b>Details:</b>\n";
            foreach ($details as $key => $value) {
                $formatted_message .= sprintf("• <b>%s:</b> %s\n", esc_html($key), esc_html($value));
            }
        }
        
        $formatted_message .= "\n<i>OSINT Pro Security System</i>";
        
        return $this->send_message($formatted_message);
    }
    
    /**
     * Send daily report
     */
    public function send_daily_report($report_data) {
        $date = current_time('Y-m-d');
        $total_events = isset($report_data['total_events']) ? $report_data['total_events'] : 0;
        $critical_alerts = isset($report_data['critical_alerts']) ? $report_data['critical_alerts'] : 0;
        $sources_active = isset($report_data['sources_active']) ? $report_data['sources_active'] : 0;
        
        $message = sprintf(
            "📊 <b>Daily OSINT Report</b>\n" .
            "<b>Date:</b> %s\n\n" .
            "<b>Summary:</b>\n" .
            "• Total Events: %d\n" .
            "• Critical Alerts: %d\n" .
            "• Active Sources: %d\n\n",
            $date,
            $total_events,
            $critical_alerts,
            $sources_active
        );
        
        if (isset($report_data['top_threats']) && !empty($report_data['top_threats'])) {
            $message .= "<b>Top Threats:</b>\n";
            foreach (array_slice($report_data['top_threats'], 0, 5) as $threat) {
                $message .= sprintf("• %s (Level: %s)\n", 
                    esc_html($threat['name']), 
                    esc_html($threat['level'])
                );
            }
        }
        
        $message .= "\n<i>OSINT Pro - Automated Report</i>";
        
        return $this->send_message($message);
    }
    
    /**
     * Get severity emoji
     */
    private function get_severity_emoji($severity) {
        $emojis = [
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            'info' => 'ℹ️'
        ];
        
        return isset($emojis[$severity]) ? $emojis[$severity] : '⚠️';
    }
    
    /**
     * Test Telegram connection
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return ['success' => false, 'message' => 'Telegram not configured'];
        }
        
        $url = $this->api_url . $this->bot_token . '/getMe';
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false, 
                'message' => 'Connection failed: ' . $response->get_error_message()
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['ok']) && $body['ok'] === true) {
            // Send test message
            $test_sent = $this->send_message("✅ OSINT Pro connection test successful!");
            
            return [
                'success' => true,
                'message' => 'Connected to Telegram Bot: ' . ($body['result']['username'] ?? 'Unknown'),
                'test_message_sent' => $test_sent
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid bot token'];
    }
    
    /**
     * Send file to Telegram
     */
    public function send_file($file_path, $caption = '') {
        if (!$this->is_configured()) {
            return false;
        }
        
        if (!file_exists($file_path)) {
            SOD_Security_Logger::log('telegram_file_not_found', ['path' => $file_path]);
            return false;
        }
        
        $url = $this->api_url . $this->bot_token . '/sendDocument';
        
        $data = [
            'chat_id' => $this->chat_id,
            'document' => new CURLFile($file_path),
            'caption' => $caption
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            SOD_Security_Logger::log('telegram_file_sent', [
                'file' => basename($file_path)
            ]);
            return true;
        }
        
        SOD_Security_Logger::log('telegram_file_failed', [
            'file' => basename($file_path),
            'response' => $response
        ]);
        return false;
    }
}
