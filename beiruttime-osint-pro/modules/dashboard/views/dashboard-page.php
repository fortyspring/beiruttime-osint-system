<?php
/**
 * Dashboard Page View
 * 
 * @package BeirutTime_OSINT_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap osint-dashboard-wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('لوحة التحكم الاستخباراتية', 'beiruttime-osint-pro'); ?>
    </h1>
    
    <button type="button" class="page-title-action" id="osint-refresh-dashboard">
        <?php echo esc_html__('تحديث', 'beiruttime-osint-pro'); ?>
    </button>
    
    <hr class="wp-header-end">
    
    <div id="osint-dashboard-loading" class="osint-loading-spinner">
        <span class="spinner is-active"></span>
        <p><?php echo esc_html__('جاري تحميل البيانات...', 'beiruttime-osint-pro'); ?></p>
    </div>
    
    <div id="osint-dashboard-content" class="osint-dashboard-grid">
        <!-- Quick Stats Widget -->
        <div class="osint-widget-card" data-widget-id="quick_stats">
            <div class="osint-widget-header">
                <h3><?php echo esc_html__('إحصائيات سريعة', 'beiruttime-osint-pro'); ?></h3>
                <span class="osint-widget-actions">
                    <button class="osint-widget-toggle" aria-label="Toggle widget">▼</button>
                </span>
            </div>
            <div class="osint-widget-body">
                <div class="osint-stats-grid">
                    <div class="osint-stat-item">
                        <span class="osint-stat-value" id="stat-total-events">0</span>
                        <span class="osint-stat-label"><?php echo esc_html__('إجمالي الأحداث', 'beiruttime-osint-pro'); ?></span>
                    </div>
                    <div class="osint-stat-item">
                        <span class="osint-stat-value" id="stat-today-events">0</span>
                        <span class="osint-stat-label"><?php echo esc_html__('أحداث اليوم', 'beiruttime-osint-pro'); ?></span>
                    </div>
                    <div class="osint-stat-item">
                        <span class="osint-stat-value" id="stat-high-threat">0</span>
                        <span class="osint-stat-label"><?php echo esc_html__('تهديد عالي', 'beiruttime-osint-pro'); ?></span>
                    </div>
                    <div class="osint-stat-item">
                        <span class="osint-stat-value" id="stat-active-alerts">0</span>
                        <span class="osint-stat-label"><?php echo esc_html__('تنبيهات نشطة', 'beiruttime-osint-pro'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Alerts Widget -->
        <div class="osint-widget-card" data-widget-id="alerts">
            <div class="osint-widget-header">
                <h3><?php echo esc_html__('التنبيهات الأخيرة', 'beiruttime-osint-pro'); ?></h3>
                <span class="osint-widget-actions">
                    <button class="osint-widget-toggle" aria-label="Toggle widget">▼</button>
                </span>
            </div>
            <div class="osint-widget-body">
                <div id="osint-recent-alerts-list" class="osint-alerts-list">
                    <!-- Alerts will be loaded here -->
                </div>
            </div>
        </div>
        
        <!-- Trends Chart Widget -->
        <div class="osint-widget-card osint-widget-full-width" data-widget-id="trends">
            <div class="osint-widget-header">
                <h3><?php echo esc_html__('اتجاهات الأحداث', 'beiruttime-osint-pro'); ?></h3>
                <span class="osint-widget-actions">
                    <select id="osint-trend-period" class="osint-period-selector">
                        <option value="7"><?php echo esc_html__('آخر 7 أيام', 'beiruttime-osint-pro'); ?></option>
                        <option value="30" selected><?php echo esc_html__('آخر 30 يوم', 'beiruttime-osint-pro'); ?></option>
                        <option value="90"><?php echo esc_html__('آخر 90 يوم', 'beiruttime-osint-pro'); ?></option>
                    </select>
                    <button class="osint-widget-toggle" aria-label="Toggle widget">▼</button>
                </span>
            </div>
            <div class="osint-widget-body">
                <canvas id="osint-trends-chart" height="100"></canvas>
            </div>
        </div>
        
        <!-- Map Widget -->
        <div class="osint-widget-card osint-widget-full-width" data-widget-id="map">
            <div class="osint-widget-header">
                <h3><?php echo esc_html__('الخريطة الجغرافية', 'beiruttime-osint-pro'); ?></h3>
                <span class="osint-widget-actions">
                    <button class="osint-widget-toggle" aria-label="Toggle widget">▼</button>
                </span>
            </div>
            <div class="osint-widget-body">
                <div id="osint-map-container" class="osint-map-wrapper">
                    <!-- Map will be rendered here -->
                </div>
            </div>
        </div>
        
        <!-- Hybrid Warfare Analysis Widget -->
        <div class="osint-widget-card" data-widget-id="hybrid-analysis">
            <div class="osint-widget-header">
                <h3><?php echo esc_html__('تحليل الحرب المركبة', 'beiruttime-osint-pro'); ?></h3>
                <span class="osint-widget-actions">
                    <button class="osint-widget-toggle" aria-label="Toggle widget">▼</button>
                </span>
            </div>
            <div class="osint-widget-body">
                <div id="osint-hybrid-layers-chart">
                    <!-- Hybrid warfare layers chart -->
                </div>
            </div>
        </div>
        
        <!-- Threat Distribution Widget -->
        <div class="osint-widget-card" data-widget-id="threat-dist">
            <div class="osint-widget-header">
                <h3><?php echo esc_html__('توزيع التهديدات', 'beiruttime-osint-pro'); ?></h3>
                <span class="osint-widget-actions">
                    <button class="osint-widget-toggle" aria-label="Toggle widget">▼</button>
                </span>
            </div>
            <div class="osint-widget-body">
                <canvas id="osint-threat-chart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div id="osint-dashboard-last-update" class="osint-last-update">
        <?php echo esc_html__('آخر تحديث', 'beiruttime-osint-pro'); ?>: <span id="osint-update-time">--:--:--</span>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var dashboardModule = {
        init: function() {
            this.bindEvents();
            this.loadData();
            this.startAutoRefresh();
        },
        
        bindEvents: function() {
            $('#osint-refresh-dashboard').on('click', $.proxy(this.refreshData, this));
            $('#osint-trend-period').on('change', $.proxy(this.loadTrendData, this));
            
            $('.osint-widget-toggle').on('click', function() {
                var card = $(this).closest('.osint-widget-card');
                card.toggleClass('collapsed');
                $(this).text(card.hasClass('collapsed') ? '▶' : '▼');
            });
        },
        
        loadData: function() {
            $('#osint-dashboard-loading').show();
            $('#osint-dashboard-content').hide();
            
            $.ajax({
                url: osintDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'osint_dashboard_get_data',
                    nonce: osintDashboard.nonce,
                    filters: {
                        days: $('#osint-trend-period').val()
                    }
                },
                success: $.proxy(this.handleDataLoaded, this),
                error: $.proxy(this.handleDataError, this)
            });
        },
        
        handleDataLoaded: function(response) {
            if (response.success) {
                this.updateStats(response.data.stats);
                this.updateAlerts(response.data.alerts);
                this.renderCharts(response.data);
                this.updateLastUpdateTime();
                
                $('#osint-dashboard-loading').hide();
                $('#osint-dashboard-content').fadeIn();
            } else {
                this.handleDataError();
            }
        },
        
        handleDataError: function() {
            $('#osint-dashboard-loading').hide();
            alert(osintDashboard.i18n.error);
        },
        
        updateStats: function(stats) {
            $('#stat-total-events').text(stats.total_events);
            $('#stat-today-events').text(stats.today_events);
            $('#stat-high-threat').text(stats.high_threat_events);
            $('#stat-active-alerts').text(stats.active_alerts);
        },
        
        updateAlerts: function(alerts) {
            var html = '';
            if (alerts.length === 0) {
                html = '<p class="osint-no-alerts">' + osintDashboard.i18n.noAlerts + '</p>';
            } else {
                html = '<ul class="osint-alerts-ul">';
                $.each(alerts, function(i, alert) {
                    var priorityClass = 'priority-' + (alert.alert_priority || 'normal');
                    html += '<li class="' + priorityClass + '">';
                    html += '<span class="alert-title">' + alert.title + '</span>';
                    html += '<span class="alert-meta">';
                    html += '<span class="threat-score">Threat: ' + alert.threat_score + '</span>';
                    html += '<span class="alert-time">' + alert.event_timestamp + '</span>';
                    html += '</span>';
                    html += '</li>';
                });
                html += '</ul>';
            }
            $('#osint-recent-alerts-list').html(html);
        },
        
        renderCharts: function(data) {
            // Render trends chart
            this.renderTrendsChart(data.trends);
            
            // Render threat distribution
            this.renderThreatChart(data.trends.threat_distribution);
        },
        
        renderTrendsChart: function(trends) {
            var ctx = document.getElementById('osint-trends-chart').getContext('2d');
            
            if (this.trendsChart) {
                this.trendsChart.destroy();
            }
            
            var labels = trends.events_per_day.map(function(item) { return item.date; });
            var counts = trends.events_per_day.map(function(item) { return parseInt(item.count); });
            
            this.trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'عدد الأحداث',
                        data: counts,
                        borderColor: '#1e73be',
                        backgroundColor: 'rgba(30, 115, 190, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },
        
        renderThreatChart: function(distribution) {
            var ctx = document.getElementById('osint-threat-chart').getContext('2d');
            
            if (this.threatChart) {
                this.threatChart.destroy();
            }
            
            this.threatChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['منخفض', 'متوسط', 'عالي', 'حرج'],
                    datasets: [{
                        data: [
                            distribution.low || 0,
                            distribution.medium || 0,
                            distribution.high || 0,
                            distribution.critical || 0
                        ],
                        backgroundColor: ['#28a745', '#ffc107', '#fd7e14', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },
        
        refreshData: function() {
            this.clearCache();
            this.loadData();
        },
        
        clearCache: function() {
            $.ajax({
                url: osintDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'osint_dashboard_refresh',
                    nonce: osintDashboard.nonce
                }
            });
        },
        
        startAutoRefresh: function() {
            var interval = osintDashboard.refreshInterval * 1000;
            setInterval($.proxy(this.loadData, this), interval);
        },
        
        updateLastUpdateTime: function() {
            var now = new Date();
            var timeStr = now.toLocaleTimeString('ar-SA');
            $('#osint-update-time').text(timeStr);
        },
        
        loadTrendData: function() {
            this.loadData();
        }
    };
    
    dashboardModule.init();
});
</script>
