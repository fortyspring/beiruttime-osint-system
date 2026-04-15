<?php
/**
 * OSINT Export Module - Modular System
 * Data export and reporting capabilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSINT_Export_Module {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', [$this, 'register_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_osint_export_data', [$this, 'ajax_export_data']);
        add_action('wp_ajax_osint_generate_report', [$this, 'ajax_generate_report']);
        add_action('wp_ajax_osint_schedule_export', [$this, 'ajax_schedule_export']);
    }
    
    public function register_submenu() {
        add_submenu_page(
            'osint-pro-dashboard',
            'Export & Reports',
            'Export',
            'manage_options',
            'osint-pro-export',
            [$this, 'render_export']
        );
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'osint-pro-page_osint-pro-export') {
            return;
        }
        
        wp_enqueue_script('osint-export', OSINT_PRO_PLUGIN_URL . 'assets/js/export-module.js', ['jquery'], OSINT_PRO_VERSION, true);
        
        wp_localize_script('osint-export', 'osintExportConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_pro_dashboard_nonce'),
            'exportFormats' => [
                'json' => 'JSON',
                'csv' => 'CSV',
                'xml' => 'XML',
                'pdf' => 'PDF',
                'html' => 'HTML Report'
            ],
            'reportTypes' => [
                'daily' => 'Daily Summary',
                'weekly' => 'Weekly Overview',
                'monthly' => 'Monthly Analysis',
                'custom' => 'Custom Range',
                'threat' => 'Threat Intelligence',
                'executive' => 'Executive Brief'
            ]
        ]);
    }
    
    public function render_export() {
        ?>
        <div class="wrap osint-pro-export">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="osint-export-tabs">
                <button class="tab-btn active" data-tab="quick-export">Quick Export</button>
                <button class="tab-btn" data-tab="custom-export">Custom Export</button>
                <button class="tab-btn" data-tab="reports">Generate Report</button>
                <button class="tab-btn" data-tab="scheduled">Scheduled Exports</button>
            </div>
            
            <!-- Quick Export -->
            <div class="tab-content active" id="tab-quick-export">
                <div class="osint-export-card">
                    <h2>Quick Export</h2>
                    <p class="description">Export recent data with default settings</p>
                    
                    <div class="osint-quick-options">
                        <label>Data Source:
                            <select id="quick-export-source">
                                <option value="events">All Events</option>
                                <option value="alerts">Alerts</option>
                                <option value="searches">Search History</option>
                                <option value="analysis">Analysis Results</option>
                            </select>
                        </label>
                        
                        <label>Time Range:
                            <select id="quick-export-range">
                                <option value="today">Today</option>
                                <option value="week">Last 7 Days</option>
                                <option value="month">Last 30 Days</option>
                                <option value="all">All Time</option>
                            </select>
                        </label>
                        
                        <label>Format:
                            <select id="quick-export-format">
                                <option value="csv">CSV</option>
                                <option value="json">JSON</option>
                                <option value="xml">XML</option>
                            </select>
                        </label>
                    </div>
                    
                    <button class="button button-primary button-large" id="quick-export-run">
                        <span class="dashicons dashicons-download"></span> Export Now
                    </button>
                </div>
            </div>
            
            <!-- Custom Export -->
            <div class="tab-content" id="tab-custom-export">
                <div class="osint-export-card">
                    <h2>Custom Export</h2>
                    <p class="description">Configure detailed export parameters</p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label>Data Source</label></th>
                            <td>
                                <select id="custom-export-source">
                                    <option value="events">Events</option>
                                    <option value="alerts">Alerts</option>
                                    <option value="searches">Searches</option>
                                    <option value="analysis">Analysis</option>
                                    <option value="reports">Reports</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Date Range</label></th>
                            <td>
                                <input type="date" id="custom-export-start" /> to 
                                <input type="date" id="custom-export-end" />
                            </td>
                        </tr>
                        <tr>
                            <th><label>Filters</label></th>
                            <td>
                                <label><input type="checkbox" id="filter-critical" /> Critical Only</label>
                                <label><input type="checkbox" id="filter-high" /> High Priority</label>
                                <br />
                                <label>Category: 
                                    <select id="filter-category">
                                        <option value="">All Categories</option>
                                        <option value="threat">Threat</option>
                                        <option value="news">News</option>
                                        <option value="social">Social Media</option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Fields to Export</label></th>
                            <td>
                                <label><input type="checkbox" name="export-fields" value="id" checked /> ID</label>
                                <label><input type="checkbox" name="export-fields" value="title" checked /> Title</label>
                                <label><input type="checkbox" name="export-fields" value="description" checked /> Description</label>
                                <label><input type="checkbox" name="export-fields" value="severity" checked /> Severity</label>
                                <label><input type="checkbox" name="export-fields" value="source" checked /> Source</label>
                                <label><input type="checkbox" name="export-fields" value="category" checked /> Category</label>
                                <label><input type="checkbox" name="export-fields" value="created_at" checked /> Created Date</label>
                                <label><input type="checkbox" name="export-fields" value="metadata" /> Metadata</label>
                                <label><input type="checkbox" name="export-fields" value="location" /> Location</label>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Output Format</label></th>
                            <td>
                                <select id="custom-export-format">
                                    <option value="csv">CSV (Comma Separated)</option>
                                    <option value="json">JSON</option>
                                    <option value="xml">XML</option>
                                    <option value="pdf">PDF Report</option>
                                    <option value="html">HTML Report</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Options</label></th>
                            <td>
                                <label><input type="checkbox" id="export-include-header" checked /> Include Headers</label>
                                <label><input type="checkbox" id="export-compress" /> Compress (ZIP)</label>
                                <label><input type="checkbox" id="export-email" /> Email when complete</label>
                            </td>
                        </tr>
                    </table>
                    
                    <button class="button button-primary button-large" id="custom-export-run">
                        <span class="dashicons dashicons-download"></span> Generate Export
                    </button>
                </div>
            </div>
            
            <!-- Reports -->
            <div class="tab-content" id="tab-reports">
                <div class="osint-export-card">
                    <h2>Generate Report</h2>
                    <p class="description">Create comprehensive analysis reports</p>
                    
                    <div class="osint-report-options">
                        <label>Report Type:
                            <select id="report-type">
                                <option value="daily">Daily Summary</option>
                                <option value="weekly">Weekly Overview</option>
                                <option value="monthly">Monthly Analysis</option>
                                <option value="threat">Threat Intelligence</option>
                                <option value="executive">Executive Brief</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </label>
                        
                        <label>Include Sections:
                            <br />
                            <label><input type="checkbox" name="report-sections" value="summary" checked /> Executive Summary</label>
                            <label><input type="checkbox" name="report-sections" value="statistics" checked /> Statistics</label>
                            <label><input type="checkbox" name="report-sections" value="timeline" checked /> Timeline</label>
                            <label><input type="checkbox" name="report-sections" value="threats" checked /> Threat Analysis</label>
                            <label><input type="checkbox" name="report-sections" value="recommendations" checked /> Recommendations</label>
                            <label><input type="checkbox" name="report-sections" value="appendix" /> Appendix</label>
                        </label>
                        
                        <label>Template:
                            <select id="report-template">
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="brief">Brief</option>
                                <option value="custom">Custom</option>
                            </select>
                        </label>
                    </div>
                    
                    <button class="button button-primary button-large" id="report-generate">
                        <span class="dashicons dashicons-admin-page"></span> Generate Report
                    </button>
                </div>
                
                <div class="osint-recent-reports">
                    <h3>Recent Reports</h3>
                    <?php $this->render_recent_reports(); ?>
                </div>
            </div>
            
            <!-- Scheduled Exports -->
            <div class="tab-content" id="tab-scheduled">
                <div class="osint-export-card">
                    <h2>Scheduled Exports</h2>
                    <p class="description">Automate regular data exports</p>
                    
                    <button class="button button-secondary" id="schedule-new-export">
                        <span class="dashicons dashicons-plus-alt"></span> New Schedule
                    </button>
                    
                    <div id="osint-scheduled-list">
                        <?php $this->render_scheduled_exports(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_export_data() {
        check_ajax_referer('osint_pro_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Rate limiting
        if (!SOD_Rate_Limiter::is_allowed('export_data', 5)) {
            wp_send_json_error('Rate limit exceeded. Please wait before exporting again.');
        }
        
        $source = sanitize_text_field($_POST['source'] ?? 'events');
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
        
        // Use Data Handler
        if (!class_exists('SOD_Data_Handler')) {
            wp_send_json_error('Data handler not available');
        }
        
        $data_handler = SOD_Data_Handler::get_instance();
        $data = $this->fetch_export_data($source, $start_date, $end_date, $filters);
        
        if (empty($data)) {
            wp_send_json_error('No data found for export');
        }
        
        $exported = $data_handler->export_data($data, $format);
        
        // Log export
        SOD_Security_Logger::log('data_exported', [
            'source' => $source,
            'format' => $format,
            'records' => count($data),
            'user' => get_current_user_id()
        ]);
        
        wp_send_json_success([
            'data' => $exported,
            'format' => $format,
            'records' => count($data),
            'filename' => "osint_export_{$source}_" . date('Y-m-d_H-i-s') . ".{$format}"
        ]);
    }
    
    public function ajax_generate_report() {
        check_ajax_referer('osint_pro_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $type = sanitize_text_field($_POST['report_type'] ?? 'daily');
        $sections = isset($_POST['sections']) ? $_POST['sections'] : ['summary', 'statistics'];
        
        $report_data = $this->compile_report_data($type);
        $report = $this->format_report($report_data, $sections, $type);
        
        SOD_Security_Logger::log('report_generated', [
            'type' => $type,
            'sections' => $sections,
            'user' => get_current_user_id()
        ]);
        
        wp_send_json_success([
            'report' => $report,
            'type' => $type,
            'generated_at' => current_time('mysql')
        ]);
    }
    
    public function ajax_schedule_export() {
        check_ajax_referer('osint_pro_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $schedule = [
            'frequency' => sanitize_text_field($_POST['frequency'] ?? 'daily'),
            'source' => sanitize_text_field($_POST['source'] ?? 'events'),
            'format' => sanitize_text_field($_POST['format'] ?? 'csv'),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'next_run' => $this->calculate_next_run($_POST['frequency'] ?? 'daily')
        ];
        
        // Save schedule
        $schedules = get_option('osint_pro_scheduled_exports', []);
        $schedules[] = $schedule;
        update_option('osint_pro_scheduled_exports', $schedules);
        
        wp_send_json_success(['message' => 'Export scheduled successfully', 'schedule' => $schedule]);
    }
    
    private function fetch_export_data($source, $start_date, $end_date, $filters) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'osint_pro_' . $source;
        $where_clauses = [];
        $values = [];
        
        if ($start_date) {
            $where_clauses[] = "created_at >= %s";
            $values[] = $start_date;
        }
        
        if ($end_date) {
            $where_clauses[] = "created_at <= %s";
            $values[] = $end_date;
        }
        
        if (isset($filters['critical']) && $filters['critical']) {
            $where_clauses[] = "severity = 'critical'";
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        $prepared = $wpdb->prepare("SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC", $values);
        
        return $wpdb->get_results($prepared, ARRAY_A);
    }
    
    private function compile_report_data($type) {
        global $wpdb;
        $events_table = $wpdb->prefix . 'osint_pro_events';
        $alerts_table = $wpdb->prefix . 'osint_pro_alerts';
        
        $date_range = $this->get_date_range($type);
        
        return [
            'total_events' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$events_table} WHERE created_at >= %s AND created_at <= %s",
                $date_range['start'], $date_range['end']
            )),
            'critical_alerts' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$alerts_table} WHERE severity = 'critical' AND created_at >= %s AND created_at <= %s",
                $date_range['start'], $date_range['end']
            )),
            'by_severity' => $this->get_by_severity($events_table, $date_range),
            'by_source' => $this->get_by_source($events_table, $date_range),
            'top_threats' => $this->get_top_threats($events_table, $date_range)
        ];
    }
    
    private function format_report($data, $sections, $type) {
        $report = [];
        
        if (in_array('summary', $sections)) {
            $report['summary'] = sprintf(
                "Report Period: %s\nTotal Events: %d\nCritical Alerts: %d",
                $type,
                $data['total_events'],
                $data['critical_alerts']
            );
        }
        
        if (in_array('statistics', $sections)) {
            $report['statistics'] = [
                'by_severity' => $data['by_severity'],
                'by_source' => $data['by_source']
            ];
        }
        
        if (in_array('threats', $sections)) {
            $report['threats'] = $data['top_threats'];
        }
        
        return $report;
    }
    
    private function get_date_range($type) {
        $now = current_time('Y-m-d H:i:s');
        
        switch ($type) {
            case 'daily':
                return ['start' => date('Y-m-d 00:00:00'), 'end' => $now];
            case 'weekly':
                return ['start' => date('Y-m-d 00:00:00', strtotime('-7 days')), 'end' => $now];
            case 'monthly':
                return ['start' => date('Y-m-01 00:00:00'), 'end' => $now];
            default:
                return ['start' => date('Y-m-d 00:00:00', strtotime('-30 days')), 'end' => $now];
        }
    }
    
    private function get_by_severity($table, $date_range) {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT severity, COUNT(*) as count FROM {$table} 
             WHERE created_at >= %s AND created_at <= %s 
             GROUP BY severity",
            $date_range['start'], $date_range['end']
        ));
        return $results;
    }
    
    private function get_by_source($table, $date_range) {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT source, COUNT(*) as count FROM {$table} 
             WHERE created_at >= %s AND created_at <= %s 
             GROUP BY source ORDER BY count DESC LIMIT 10",
            $date_range['start'], $date_range['end']
        ));
        return $results;
    }
    
    private function get_top_threats($table, $date_range) {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT title, severity, created_at FROM {$table} 
             WHERE severity IN ('critical', 'high') 
             AND created_at >= %s AND created_at <= %s 
             ORDER BY created_at DESC LIMIT 10",
            $date_range['start'], $date_range['end']
        ));
        return $results;
    }
    
    private function calculate_next_run($frequency) {
        switch ($frequency) {
            case 'hourly':
                return date('Y-m-d H:i:s', strtotime('+1 hour'));
            case 'daily':
                return date('Y-m-d H:i:s', strtotime('+1 day'));
            case 'weekly':
                return date('Y-m-d H:i:s', strtotime('+1 week'));
            case 'monthly':
                return date('Y-m-d H:i:s', strtotime('+1 month'));
            default:
                return date('Y-m-d H:i:s', strtotime('+1 day'));
        }
    }
    
    private function render_recent_reports() {
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_reports';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            echo '<p>No reports available</p>';
            return;
        }
        
        $reports = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 5");
        
        if (empty($reports)) {
            echo '<p>No recent reports</p>';
            return;
        }
        
        echo '<ul class="osint-reports-list">';
        foreach ($reports as $report) {
            printf(
                '<li><a href="#" class="view-report" data-id="%d">%s (%s)</a> - %s</li>',
                esc_attr($report->id),
                esc_html($report->title),
                esc_html($report->type),
                esc_html($report->created_at)
            );
        }
        echo '</ul>';
    }
    
    private function render_scheduled_exports() {
        $schedules = get_option('osint_pro_scheduled_exports', []);
        
        if (empty($schedules)) {
            echo '<p>No scheduled exports</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Frequency</th><th>Source</th><th>Format</th><th>Next Run</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($schedules as $index => $schedule) {
            printf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><button class="button button-small delete-schedule" data-index="%d">Delete</button></td></tr>',
                esc_html($schedule['frequency']),
                esc_html($schedule['source']),
                esc_html($schedule['format']),
                esc_html($schedule['next_run']),
                esc_attr($index)
            );
        }
        
        echo '</tbody></table>';
    }
}
