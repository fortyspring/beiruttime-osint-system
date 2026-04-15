<?php
/**
 * OSINT Analysis Module - Modular System
 * Advanced analysis tools and workflows
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSINT_Analysis_Module {
    
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
        add_action('wp_ajax_osint_run_analysis', [$this, 'ajax_run_analysis']);
        add_action('wp_ajax_osint_batch_analyze', [$this, 'ajax_batch_analyze']);
    }
    
    public function register_submenu() {
        add_submenu_page(
            'osint-pro-dashboard',
            'Advanced Analysis',
            'Analysis Tools',
            'manage_options',
            'osint-pro-analysis-tools',
            [$this, 'render_analysis_tools']
        );
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'osint-pro-page_osint-pro-analysis-tools') {
            return;
        }
        
        wp_enqueue_script('osint-analysis', OSINT_PRO_PLUGIN_URL . 'assets/js/analysis-module.js', ['jquery'], OSINT_PRO_VERSION, true);
        
        wp_localize_script('osint-analysis', 'osintAnalysisConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_pro_dashboard_nonce'),
            'analysisTypes' => [
                'sentiment' => 'Sentiment Analysis',
                'entities' => 'Entity Extraction',
                'keywords' => 'Keyword Extraction',
                'threat' => 'Threat Assessment',
                'relationships' => 'Relationship Mapping',
                'timeline' => 'Timeline Building',
                'comprehensive' => 'Comprehensive Analysis'
            ]
        ]);
    }
    
    public function render_analysis_tools() {
        ?>
        <div class="wrap osint-pro-analysis-tools">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="osint-analysis-workspace">
                <div class="osint-analysis-input-panel">
                    <h2>Input Data</h2>
                    
                    <div class="osint-input-tabs">
                        <button class="tab-btn active" data-tab="text">Text Input</button>
                        <button class="tab-btn" data-tab="file">File Upload</button>
                        <button class="tab-btn" data-tab="url">URL Fetch</button>
                        <button class="tab-btn" data-tab="database">From Database</button>
                    </div>
                    
                    <div class="tab-content active" id="tab-text">
                        <textarea id="osint-analysis-text" placeholder="Enter or paste text for analysis..." rows="10"></textarea>
                    </div>
                    
                    <div class="tab-content" id="tab-file">
                        <input type="file" id="osint-analysis-file" accept=".txt,.csv,.json,.xml" />
                        <p class="description">Supported formats: TXT, CSV, JSON, XML (Max 10MB)</p>
                    </div>
                    
                    <div class="tab-content" id="tab-url">
                        <input type="url" id="osint-analysis-url" placeholder="https://example.com/page" />
                        <button class="button" id="osint-fetch-url">Fetch Content</button>
                    </div>
                    
                    <div class="tab-content" id="tab-database">
                        <select id="osint-db-source">
                            <option value="events">Events Table</option>
                            <option value="alerts">Alerts Table</option>
                            <option value="reports">Reports Table</option>
                        </select>
                        <input type="number" id="osint-db-limit" placeholder="Limit" value="100" />
                        <button class="button" id="osint-load-db">Load Data</button>
                    </div>
                    
                    <div class="osint-analysis-options">
                        <h3>Analysis Options</h3>
                        <label><input type="checkbox" name="analysis_type" value="sentiment" checked /> Sentiment Analysis</label>
                        <label><input type="checkbox" name="analysis_type" value="entities" checked /> Entity Extraction</label>
                        <label><input type="checkbox" name="analysis_type" value="keywords" checked /> Keyword Extraction</label>
                        <label><input type="checkbox" name="analysis_type" value="threat" checked /> Threat Assessment</label>
                        <label><input type="checkbox" name="analysis_type" value="relationships" /> Relationship Mapping</label>
                        <label><input type="checkbox" name="analysis_type" value="timeline" /> Timeline Building</label>
                    </div>
                    
                    <button class="button button-primary button-large" id="osint-run-analysis">
                        <span class="dashicons dashicons-visibility"></span> Run Analysis
                    </button>
                </div>
                
                <div class="osint-analysis-results-panel">
                    <h2>Analysis Results</h2>
                    
                    <div id="osint-analysis-loading" style="display:none;">
                        <div class="osint-spinner"></div>
                        <p>Analyzing data...</p>
                    </div>
                    
                    <div id="osint-analysis-output">
                        <p class="description">Results will appear here after analysis</p>
                    </div>
                    
                    <div class="osint-analysis-actions">
                        <button class="button" id="osint-export-results">Export Results</button>
                        <button class="button" id="osint-save-report">Save as Report</button>
                        <button class="button" id="osint-clear-results">Clear</button>
                    </div>
                </div>
            </div>
            
            <div class="osint-analysis-history">
                <h2>Recent Analyses</h2>
                <?php $this->render_analysis_history(); ?>
            </div>
        </div>
        <?php
    }
    
    public function ajax_run_analysis() {
        check_ajax_referer('osint_pro_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Rate limiting
        if (!SOD_Rate_Limiter::is_allowed('analysis_run', 10)) {
            wp_send_json_error('Rate limit exceeded. Please wait before running another analysis.');
        }
        
        $data = isset($_POST['data']) ? $_POST['data'] : '';
        $types = isset($_POST['types']) ? $_POST['types'] : ['sentiment', 'entities', 'keywords', 'threat'];
        
        if (empty($data)) {
            wp_send_json_error('No data provided for analysis');
        }
        
        // Use Analysis Service
        if (!class_exists('SOD_Analysis_Service')) {
            wp_send_json_error('Analysis service not available');
        }
        
        $service = SOD_Analysis_Service::get_instance();
        $results = [];
        
        foreach ($types as $type) {
            switch ($type) {
                case 'sentiment':
                    $results['sentiment'] = $service->analyze_sentiment($data);
                    break;
                case 'entities':
                    $results['entities'] = $service->extract_entities($data);
                    break;
                case 'keywords':
                    $results['keywords'] = $service->extract_keywords($data);
                    break;
                case 'threat':
                    $results['threat'] = $service->assess_threat($data);
                    break;
                case 'relationships':
                    $results['relationships'] = $service->map_relationships($data);
                    break;
                case 'timeline':
                    $results['timeline'] = $service->build_timeline($data);
                    break;
            }
        }
        
        // Log the analysis
        SOD_Security_Logger::log('analysis_run', [
            'types' => $types,
            'data_length' => strlen(is_array($data) ? implode('', $data) : $data),
            'user' => get_current_user_id()
        ]);
        
        wp_send_json_success([
            'results' => $results,
            'timestamp' => current_time('mysql'),
            'analysis_id' => uniqid('analysis_')
        ]);
    }
    
    public function ajax_batch_analyze() {
        check_ajax_referer('osint_pro_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $items = isset($_POST['items']) ? $_POST['items'] : [];
        $type = sanitize_text_field($_POST['analysis_type'] ?? 'sentiment');
        
        if (empty($items)) {
            wp_send_json_error('No items provided');
        }
        
        $service = SOD_Analysis_Service::get_instance();
        $results = [];
        
        foreach ($items as $item) {
            if (!SOD_Rate_Limiter::is_allowed('batch_analysis', 50)) {
                break;
            }
            
            switch ($type) {
                case 'sentiment':
                    $results[] = $service->analyze_sentiment($item);
                    break;
                case 'threat':
                    $results[] = $service->assess_threat($item);
                    break;
            }
        }
        
        wp_send_json_success(['results' => $results, 'processed' => count($results)]);
    }
    
    private function render_analysis_history() {
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_analysis_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            echo '<p>No analysis history available</p>';
            return;
        }
        
        $history = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 10");
        
        if (empty($history)) {
            echo '<p>No recent analyses</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Date</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($history as $record) {
            printf(
                '<tr><td>%s</td><td>%s</td><td><span class="status-%s">%s</span></td><td><a href="#" class="button button-small view-analysis" data-id="%d">View</a></td></tr>',
                esc_html($record->created_at),
                esc_html($record->analysis_types),
                esc_attr($record->status),
                esc_html($record->status),
                esc_attr($record->id)
            );
        }
        
        echo '</tbody></table>';
    }
}
