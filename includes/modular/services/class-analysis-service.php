<?php
/**
 * Analysis Service - Modular System
 * Advanced analysis service for OSINT data processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOD_Analysis_Service {
    
    private static $instance = null;
    private $analysis_cache = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Perform comprehensive analysis on data
     */
    public function analyze($data, $options = []) {
        $cache_key = md5(serialize($data) . serialize($options));
        
        // Check cache
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        $results = [
            'sentiment' => $this->analyze_sentiment($data),
            'entities' => $this->extract_entities($data),
            'keywords' => $this->extract_keywords($data),
            'threat_level' => $this->assess_threat($data),
            'relationships' => $this->map_relationships($data),
            'timeline' => $this->build_timeline($data)
        ];
        
        // Cache results for 10 minutes
        $this->analysis_cache[$cache_key] = $results;
        wp_cache_set('analysis_' . $cache_key, $results, 'osint_pro', 600);
        
        return $results;
    }
    
    /**
     * Sentiment analysis
     */
    public function analyze_sentiment($data) {
        if (empty($data)) {
            return ['score' => 0, 'label' => 'neutral', 'confidence' => 0];
        }
        
        $text = is_array($data) ? implode(' ', $data) : (string)$data;
        
        // Simple sentiment scoring (can be enhanced with ML models)
        $positive_words = ['good', 'great', 'excellent', 'positive', 'success', 'safe', 'secure'];
        $negative_words = ['bad', 'terrible', 'awful', 'negative', 'failure', 'danger', 'threat', 'attack'];
        
        $words = str_word_count(strtolower($text), 1);
        $positive_count = count(array_intersect($words, $positive_words));
        $negative_count = count(array_intersect($words, $negative_words));
        
        $total = $positive_count + $negative_count;
        if ($total === 0) {
            return ['score' => 0, 'label' => 'neutral', 'confidence' => 0.5];
        }
        
        $score = ($positive_count - $negative_count) / $total;
        $label = $score > 0.2 ? 'positive' : ($score < -0.2 ? 'negative' : 'neutral');
        $confidence = min(abs($score), 1);
        
        return [
            'score' => round($score, 3),
            'label' => $label,
            'confidence' => round($confidence, 3),
            'positive_count' => $positive_count,
            'negative_count' => $negative_count
        ];
    }
    
    /**
     * Extract entities (names, organizations, locations, etc.)
     */
    public function extract_entities($data) {
        if (empty($data)) {
            return [];
        }
        
        $text = is_array($data) ? implode(' ', $data) : (string)$data;
        $entities = [];
        
        // Extract emails
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $emails);
        $entities['emails'] = array_unique($emails[0]);
        
        // Extract URLs
        preg_match_all('/https?:\/\/[^\s<>"{}|\\^`\[\]]+/', $text, $urls);
        $entities['urls'] = array_unique($urls[0]);
        
        // Extract IP addresses
        preg_match_all('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $text, $ips);
        $entities['ip_addresses'] = array_unique($ips[0]);
        
        // Extract phone numbers (international format)
        preg_match_all('/\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}/', $text, $phones);
        $entities['phone_numbers'] = array_unique($phones[0]);
        
        // Extract dates
        preg_match_all('/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b|\b\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}\b/', $text, $dates);
        $entities['dates'] = array_unique($dates[0]);
        
        // Log extraction
        SOD_Security_Logger::log('entities_extracted', [
            'counts' => array_map('count', $entities)
        ]);
        
        return $entities;
    }
    
    /**
     * Extract keywords
     */
    public function extract_keywords($data, $limit = 10) {
        if (empty($data)) {
            return [];
        }
        
        $text = is_array($data) ? implode(' ', $data) : (string)$data;
        
        // Remove stopwords
        $stopwords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = preg_split('/\s+/', strtolower($text));
        $words = array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 3 && !in_array($word, $stopwords);
        });
        
        // Count frequency
        $frequency = array_count_values($words);
        arsort($frequency);
        
        return array_slice(array_keys($frequency), 0, $limit);
    }
    
    /**
     * Assess threat level
     */
    public function assess_threat($data) {
        $threat_indicators = [
            'critical' => ['attack', 'breach', 'exploit', 'malware', 'ransomware', 'zero-day'],
            'high' => ['vulnerability', 'threat', 'risk', 'danger', 'intrusion', 'unauthorized'],
            'medium' => ['suspicious', 'anomaly', 'unusual', 'concern', 'warning'],
            'low' => ['notice', 'information', 'update', 'advisory']
        ];
        
        $text = is_array($data) ? implode(' ', $data) : (string)$data;
        $text_lower = strtolower($text);
        
        $scores = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0
        ];
        
        foreach ($threat_indicators as $level => $indicators) {
            foreach ($indicators as $indicator) {
                if (strpos($text_lower, $indicator) !== false) {
                    $scores[$level]++;
                }
            }
        }
        
        // Determine highest threat level
        $threat_level = 'low';
        $max_score = 0;
        
        foreach (['critical', 'high', 'medium', 'low'] as $level) {
            if ($scores[$level] > $max_score) {
                $max_score = $scores[$level];
                $threat_level = $level;
            }
        }
        
        return [
            'level' => $threat_level,
            'score' => $max_score,
            'breakdown' => $scores,
            'requires_action' => in_array($threat_level, ['critical', 'high'])
        ];
    }
    
    /**
     * Map relationships between entities
     */
    public function map_relationships($data) {
        if (empty($data)) {
            return [];
        }
        
        $entities = $this->extract_entities($data);
        $relationships = [];
        
        // Analyze co-occurrence
        $text = is_array($data) ? implode(' ', $data) : (string)$data;
        $sentences = preg_split('/[.!?]+/', $text);
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            // Find which entities appear together
            foreach ($entities as $type1 => $items1) {
                foreach ($items1 as $item1) {
                    foreach ($entities as $type2 => $items2) {
                        if ($type1 === $type2) continue;
                        
                        foreach ($items2 as $item2) {
                            if (strpos($sentence, $item1) !== false && strpos($sentence, $item2) !== false) {
                                $key = $item1 . '|' . $item2;
                                if (!isset($relationships[$key])) {
                                    $relationships[$key] = [
                                        'entity1' => $item1,
                                        'type1' => $type1,
                                        'entity2' => $item2,
                                        'type2' => $type2,
                                        'co_occurrences' => 0,
                                        'contexts' => []
                                    ];
                                }
                                $relationships[$key]['co_occurrences']++;
                                if (count($relationships[$key]['contexts']) < 3) {
                                    $relationships[$key]['contexts'][] = substr($sentence, 0, 100);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return array_values($relationships);
    }
    
    /**
     * Build timeline from data
     */
    public function build_timeline($data) {
        if (empty($data)) {
            return [];
        }
        
        $timeline = [];
        $text = is_array($data) ? implode(' ', $data) : (string)$data;
        
        // Extract dates and surrounding context
        preg_match_all('/(\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b|\b\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}\b)(.{0,100})/i', $text, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $date = $match[1];
            $context = trim($match[2]);
            
            $timeline[] = [
                'date' => $date,
                'timestamp' => strtotime($date),
                'event' => $context,
                'relevance' => $this->calculate_relevance($context)
            ];
        }
        
        // Sort by date
        usort($timeline, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });
        
        return $timeline;
    }
    
    /**
     * Calculate relevance score
     */
    private function calculate_relevance($text) {
        $important_words = ['alert', 'warning', 'critical', 'urgent', 'important', 'notice', 'update'];
        $score = 0;
        
        foreach ($important_words as $word) {
            if (stripos($text, $word) !== false) {
                $score += 10;
            }
        }
        
        return min($score, 100);
    }
    
    /**
     * Clear analysis cache
     */
    public function clear_cache() {
        $this->analysis_cache = [];
        wp_cache_flush_group('osint_pro');
    }
}
