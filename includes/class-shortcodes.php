<?php
/**
 * فئة الأكواد المختصرة (Shortcodes)
 */

namespace Beiruttime\OSINT\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class Shortcodes {
    
    public function __construct() {
        add_shortcode('osint_hybrid_dashboard', array($this, 'hybrid_dashboard_shortcode'));
        add_shortcode('osint_threat_radar', array($this, 'threat_radar_shortcode'));
        add_shortcode('osint_event_timeline', array($this, 'event_timeline_shortcode'));
    }
    
    public function hybrid_dashboard_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'show_chart' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="osint-hybrid-dashboard" data-limit="<?php echo esc_attr($atts['limit']); ?>">
            <h3><?php _e('لوحة الحرب المركبة', 'beiruttime-osint'); ?></h3>
            <div class="dashboard-content">
                <?php if ($atts['show_chart'] === 'true'): ?>
                <div id="hybrid-chart"></div>
                <?php endif; ?>
                <div id="hybrid-events"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function threat_radar_shortcode($atts = array()) {
        ob_start();
        ?>
        <div class="osint-threat-radar">
            <h3><?php _e('رادار التهديدات', 'beiruttime-osint'); ?></h3>
            <svg id="threat-radar-svg" width="400" height="400"></svg>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function event_timeline_shortcode($atts = array()) {
        ob_start();
        ?>
        <div class="osint-event-timeline">
            <h3><?php _e('الجدول الزمني للأحداث', 'beiruttime-osint'); ?></h3>
            <div id="timeline-container"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
