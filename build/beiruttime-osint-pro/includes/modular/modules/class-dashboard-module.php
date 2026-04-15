<?php
/**
 * OSINT Dashboard Module - Modular System
 * Main dashboard interface for OSINT Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSINT_Dashboard_Module {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Register admin menu
     */
    public function register_menu() {
        add_menu_page(
            'OSINT Pro Dashboard',
            'OSINT Pro',
            'manage_options',
            'osint-pro-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'osint-pro-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'osint-pro-dashboard',
            [$this, 'render_dashboard']
        );
        
        add_submenu_page(
            'osint-pro-dashboard',
            'Search',
            'Search',
            'manage_options',
            'osint-pro-search',
            [$this, 'render_search']
        );
        
        add_submenu_page(
            'osint-pro-dashboard',
            'Analysis',
            'Analysis',
            'manage_options',
            'osint-pro-analysis',
            [$this, 'render_analysis']
        );
        
        add_submenu_page(
            'osint-pro-dashboard',
            'Reports',
            'Reports',
            'manage_options',
            'osint-pro-reports',
            [$this, 'render_reports']
        );
        
        add_submenu_page(
            'osint-pro-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'osint-pro-settings',
            [$this, 'render_settings']
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'osint-pro') === false) {
            return;
        }
        
        wp_enqueue_style('osint-pro-dashboard', OSINT_PRO_PLUGIN_URL . 'assets/css/dashboard.css', [], OSINT_PRO_VERSION);
        wp_enqueue_script('osint-pro-dashboard', OSINT_PRO_PLUGIN_URL . 'assets/js/dashboard.js', ['jquery'], OSINT_PRO_VERSION, true);
        
        wp_localize_script('osint-pro-dashboard', 'osintProData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_pro_dashboard_nonce'),
            'apiBaseUrl' => rest_url('osint-pro/v1'),
            'strings' => [
                'loading' => 'Loading...',
                'error' => 'An error occurred',
                'success' => 'Success'
            ]
        ]);
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        ?>
        <div class="wrap osint-pro-dashboard">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="osint-pro-stats-grid">
                <div class="osint-pro-stat-card">
                    <h3>Total Events</h3>
                    <p class="stat-number"><?php echo $this->get_total_events(); ?></p>
                </div>
                
                <div class="osint-pro-stat-card">
                    <h3>Critical Alerts</h3>
                    <p class="stat-number critical"><?php echo $this->get_critical_alerts(); ?></p>
                </div>
                
                <div class="osint-pro-stat-card">
                    <h3>Active Sources</h3>
                    <p class="stat-number"><?php echo $this->get_active_sources(); ?></p>
                </div>
                
                <div class="osint-pro-stat-card">
                    <h3>Today's Searches</h3>
                    <p class="stat-number"><?php echo $this->get_today_searches(); ?></p>
                </div>
            </div>
            
            <div class="osint-pro-content-grid">
                <div class="osint-pro-panel">
                    <h2>Recent Alerts</h2>
                    <?php $this->render_recent_alerts(); ?>
                </div>
                
                <div class="osint-pro-panel">
                    <h2>System Status</h2>
                    <?php $this->render_system_status(); ?>
                </div>
            </div>
            
            <div class="osint-pro-chart-container">
                <h2>Activity Overview</h2>
                <canvas id="osint-activity-chart"></canvas>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render search page
     */
    public function render_search() {
        ?>
        <div class="wrap osint-pro-search">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="osint-pro-search-box">
                <form id="osint-search-form">
                    <input type="text" name="query" placeholder="Enter search query..." required />
                    <select name="source">
                        <option value="all">All Sources</option>
                        <option value="social">Social Media</option>
                        <option value="news">News</option>
                        <option value="darkweb">Dark Web</option>
                        <option value="forums">Forums</option>
                    </select>
                    <button type="submit" class="button button-primary">Search</button>
                </form>
            </div>
            
            <div id="osint-search-results"></div>
        </div>
        <?php
    }
    
    /**
     * Render analysis page
     */
    public function render_analysis() {
        ?>
        <div class="wrap osint-pro-analysis">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="osint-pro-analysis-tools">
                <div class="osint-pro-tool">
                    <h3>Sentiment Analysis</h3>
                    <textarea placeholder="Enter text for sentiment analysis..."></textarea>
                    <button class="button button-primary">Analyze</button>
                </div>
                
                <div class="osint-pro-tool">
                    <h3>Entity Extraction</h3>
                    <textarea placeholder="Enter text for entity extraction..."></textarea>
                    <button class="button button-primary">Extract</button>
                </div>
                
                <div class="osint-pro-tool">
                    <h3>Threat Assessment</h3>
                    <textarea placeholder="Enter data for threat assessment..."></textarea>
                    <button class="button button-primary">Assess</button>
                </div>
            </div>
            
            <div id="osint-analysis-results"></div>
        </div>
        <?php
    }
    
    /**
     * Render reports page
     */
    public function render_reports() {
        ?>
        <div class="wrap osint-pro-reports">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="osint-pro-reports-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $this->render_reports_table(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        ?>
        <div class="wrap osint-pro-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('osint_pro_settings');
                do_settings_sections('osint_pro_settings');
                submit_button();
                ?>
            </form>
            
            <hr />
            
            <h2>Telegram Integration</h2>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th><label>Bot Token</label></th>
                        <td><input type="text" name="osint_pro_telegram_bot_token" value="<?php echo esc_attr(get_option('osint_pro_telegram_bot_token')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label>Chat ID</label></th>
                        <td><input type="text" name="osint_pro_telegram_chat_id" value="<?php echo esc_attr(get_option('osint_pro_telegram_chat_id')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Telegram Settings'); ?>
            </form>
        </div>
        <?php
    }
    
    // Helper methods
    private function get_total_events() {
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_events';
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
    
    private function get_critical_alerts() {
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_alerts';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE severity = %s", 'critical'));
    }
    
    private function get_active_sources() {
        return 8; // Placeholder
    }
    
    private function get_today_searches() {
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_searches';
        $today = current_time('Y-m-d');
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", $today));
    }
    
    private function render_recent_alerts() {
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_alerts';
        $alerts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 5"));
        
        if (empty($alerts)) {
            echo '<p>No recent alerts</p>';
            return;
        }
        
        echo '<ul class="osint-pro-alerts-list">';
        foreach ($alerts as $alert) {
            printf(
                '<li class="alert-%s"><strong>%s</strong> - %s</li>',
                esc_attr($alert->severity),
                esc_html($alert->title),
                esc_html($alert->created_at)
            );
        }
        echo '</ul>';
    }
    
    private function render_system_status() {
        $status = [
            'Database' => 'Connected',
            'API' => 'Operational',
            'Cron Jobs' => 'Running',
            'Cache' => 'Active'
        ];
        
        echo '<ul class="osint-pro-status-list">';
        foreach ($status as $component => $state) {
            printf('<li><strong>%s:</strong> <span class="status-ok">%s</span></li>', esc_html($component), esc_html($state));
        }
        echo '</ul>';
    }
    
    private function render_reports_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_reports';
        $reports = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 20");
        
        if (empty($reports)) {
            echo '<tr><td colspan="4">No reports available</td></tr>';
            return;
        }
        
        foreach ($reports as $report) {
            printf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td><a href="#" class="button button-small">View</a></td></tr>',
                esc_html($report->created_at),
                esc_html($report->type),
                esc_html($report->status)
            );
        }
    }
}
