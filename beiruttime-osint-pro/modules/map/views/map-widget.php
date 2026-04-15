<?php
/**
 * Map Widget View
 * 
 * @package BeirutTime_OSINT_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="osint-map-widget" class="osint-map-widget-container">
    <div id="osint-mini-map" class="osint-leaflet-map" style="height: 400px;"></div>
    
    <div class="osint-map-controls">
        <button id="osint-toggle-heatmap" class="button">
            <?php echo esc_html__('عرض الحرارة', 'beiruttime-osint-pro'); ?>
        </button>
        
        <select id="osint-map-threat-filter" class="osint-filter-select">
            <option value="all"><?php echo esc_html__('جميع المستويات', 'beiruttime-osint-pro'); ?></option>
            <option value="low"><?php echo esc_html__('منخفض', 'beiruttime-osint-pro'); ?></option>
            <option value="medium"><?php echo esc_html__('متوسط', 'beiruttime-osint-pro'); ?></option>
            <option value="high"><?php echo esc_html__('عالي', 'beiruttime-osint-pro'); ?></option>
            <option value="critical"><?php echo esc_html__('حرج', 'beiruttime-osint-pro'); ?></option>
        </select>
    </div>
    
    <div id="osint-map-event-count" class="osint-map-stats">
        <?php echo esc_html__('عدد الأحداث', 'beiruttime-osint-pro'); ?>: <span id="osint-map-count">0</span>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var mapWidget = {
        map: null,
        markers: [],
        heatmapLayer: null,
        
        init: function() {
            this.initMap();
            this.loadEvents();
            this.bindEvents();
        },
        
        initMap: function() {
            var center = osintMap.defaultCenter || [33.8938, 35.5018];
            var zoom = osintMap.defaultZoom || 7;
            
            this.map = L.map('osint-mini-map').setView(center, zoom);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);
        },
        
        loadEvents: function() {
            var threatLevel = $('#osint-map-threat-filter').val();
            
            $.ajax({
                url: osintMap.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'osint_map_get_events',
                    nonce: osintMap.nonce,
                    filters: {
                        threat_level: threatLevel !== 'all' ? threatLevel : null
                    }
                },
                success: $.proxy(this.handleEventsLoaded, this)
            });
        },
        
        handleEventsLoaded: function(response) {
            if (response.success) {
                this.clearMarkers();
                this.addMarkers(response.data.events);
                $('#osint-map-count').text(response.data.count);
            }
        },
        
        addMarkers: function(events) {
            var self = this;
            
            $.each(events, function(i, event) {
                var color = self.getThreatColor(event.threat_score);
                
                var marker = L.circleMarker([event.lat, event.lng], {
                    radius: 8,
                    fillColor: color,
                    color: '#000',
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(self.map);
                
                var popup = '<div class="osint-marker-popup">' +
                    '<h4>' + event.title + '</h4>' +
                    '<p><strong>' + osintMap.i18n.type + ':</strong> ' + event.event_type + '</p>' +
                    '<p><strong>Threat:</strong> ' + event.threat_score + '</p>' +
                    '<p><strong>Location:</strong> ' + (event.geo_city || '') + ', ' + (event.geo_region || '') + '</p>' +
                    '</div>';
                
                marker.bindPopup(popup);
                self.markers.push(marker);
            });
        },
        
        clearMarkers: function() {
            $.each(this.markers, function(i, marker) {
                self.map.removeLayer(marker);
            });
            this.markers = [];
        },
        
        getThreatColor: function(score) {
            if (score >= 80) return '#dc3545'; // Critical - Red
            if (score >= 60) return '#fd7e14'; // High - Orange
            if (score >= 30) return '#ffc107'; // Medium - Yellow
            return '#28a745'; // Low - Green
        },
        
        toggleHeatmap: function() {
            if (this.heatmapLayer) {
                this.map.removeLayer(this.heatmapLayer);
                this.heatmapLayer = null;
                $('#osint-toggle-heatmap').text(osintMap.i18n.showHeatmap);
            } else {
                $.ajax({
                    url: osintMap.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'osint_map_get_heatmap',
                        nonce: osintMap.nonce
                    },
                    success: $.proxy(function(response) {
                        if (response.success) {
                            this.heatmapLayer = L.heatLayer(response.data.points, {
                                radius: 25,
                                blur: 15,
                                maxZoom: 10
                            }).addTo(this.map);
                            $('#osint-toggle-heatmap').text(osintMap.i18n.hideHeatmap);
                        }
                    }, this)
                });
            }
        },
        
        bindEvents: function() {
            $('#osint-toggle-heatmap').on('click', $.proxy(this.toggleHeatmap, this));
            $('#osint-map-threat-filter').on('change', $.proxy(this.loadEvents, this));
        }
    };
    
    if (typeof L !== 'undefined') {
        mapWidget.init();
    }
});
</script>
