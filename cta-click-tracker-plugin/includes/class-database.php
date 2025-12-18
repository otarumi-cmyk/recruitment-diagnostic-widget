<?php
/**
 * データベース操作クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTA_Click_Tracker_Database {
    
    /**
     * テーブル作成
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'cta_tracker_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            article_url varchar(500) NOT NULL,
            cta_url varchar(500) NOT NULL,
            event_type varchar(20) NOT NULL,
            device varchar(20) DEFAULT 'desktop',
            session_id varchar(64) DEFAULT '',
            user_agent text,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY article_url (article_url(255)),
            KEY cta_url (cta_url(255)),
            KEY event_type (event_type),
            KEY device (device),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * ログ挿入（重複チェック付き）
     */
    public static function insert_log($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cta_tracker_logs';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            self::create_tables();
        }
        
        $article_url = esc_url_raw($data['article_url']);
        $cta_url = esc_url_raw($data['cta_url']);
        $event_type = sanitize_text_field($data['event_type']);
        $device = sanitize_text_field($data['device'] ?? 'desktop');
        $session_id = sanitize_text_field($data['session_id'] ?? '');
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        
        // 重複チェック: 同じセッション、同じ記事、同じCTA、同じイベントタイプで
        // 5分以内に既に記録されている場合は重複として扱う
        if (!empty($session_id)) {
            $duplicate_check = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                WHERE session_id = %s 
                AND article_url = %s 
                AND cta_url = %s 
                AND event_type = %s 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
                $session_id,
                $article_url,
                $cta_url,
                $event_type
            ));
            
            if ($duplicate_check > 0) {
                return false; // 重複のため記録しない
            }
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'article_url' => $article_url,
                'cta_url' => $cta_url,
                'event_type' => $event_type,
                'device' => $device,
                'session_id' => $session_id,
                'user_agent' => $user_agent,
                'ip_address' => $ip_address,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('CTA Tracker Insert Error: ' . $wpdb->last_error);
        }
        
        return $result;
    }
    
    /**
     * 記事別の集計データ取得
     */
    public static function get_stats_by_article($start_date = null, $end_date = null, $cta_url = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cta_tracker_logs';
        
        $where = "1=1";
        $params = array();
        
        if ($start_date) {
            $where .= " AND l.created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }
        
        if ($end_date) {
            $where .= " AND l.created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }
        
        if ($cta_url) {
            $where .= " AND l.cta_url = %s";
            $params[] = $cta_url;
        }
        
        $sql = "SELECT 
                    l.article_url,
                    l.cta_url,
                    SUM(CASE WHEN l.event_type = 'impression' THEN 1 ELSE 0 END) as impressions,
                    SUM(CASE WHEN l.event_type = 'click' THEN 1 ELSE 0 END) as clicks
                FROM $table_name l
                WHERE $where
                GROUP BY l.article_url, l.cta_url
                ORDER BY clicks DESC, impressions DESC";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $results = $wpdb->get_results($sql);
        
        // CTRを計算
        foreach ($results as $result) {
            $impressions = intval($result->impressions);
            $clicks = intval($result->clicks);
            $result->ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        }
        
        return $results;
    }
    
    /**
     * CTA別の集計データ取得
     */
    public static function get_stats_by_cta($start_date = null, $end_date = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cta_tracker_logs';
        
        $where = "1=1";
        $params = array();
        
        if ($start_date) {
            $where .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }
        
        if ($end_date) {
            $where .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }
        
        $sql = "SELECT 
                    cta_url,
                    SUM(CASE WHEN event_type = 'impression' THEN 1 ELSE 0 END) as impressions,
                    SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
                FROM $table_name
                WHERE $where
                GROUP BY cta_url
                ORDER BY clicks DESC, impressions DESC";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $results = $wpdb->get_results($sql);
        
        // CTRを計算
        foreach ($results as $result) {
            $impressions = intval($result->impressions);
            $clicks = intval($result->clicks);
            $result->ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        }
        
        return $results;
    }
    
    /**
     * 全体のサマリー取得
     */
    public static function get_summary($start_date = null, $end_date = null, $cta_url = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cta_tracker_logs';
        
        $where = "1=1";
        $params = array();
        
        if ($start_date) {
            $where .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }
        
        if ($end_date) {
            $where .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }
        
        if ($cta_url) {
            $where .= " AND cta_url = %s";
            $params[] = $cta_url;
        }
        
        $sql = "SELECT 
                    SUM(CASE WHEN event_type = 'impression' THEN 1 ELSE 0 END) as total_impressions,
                    SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as total_clicks
                FROM $table_name
                WHERE $where";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $result = $wpdb->get_row($sql);
        
        if ($result) {
            $impressions = intval($result->total_impressions);
            $clicks = intval($result->total_clicks);
            $result->overall_ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        }
        
        return $result;
    }
    
    /**
     * 前週のサマリー取得（前週比計算用）
     */
    public static function get_previous_period_summary($start_date, $end_date) {
        if (!$start_date || !$end_date) {
            return null;
        }
        
        // 期間の日数を計算
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $days = $start->diff($end)->days + 1;
        
        // 前週の開始日と終了日を計算
        $prev_start = clone $start;
        $prev_start->modify("-{$days} days");
        $prev_end = clone $start;
        $prev_end->modify('-1 day');
        
        return self::get_summary($prev_start->format('Y-m-d'), $prev_end->format('Y-m-d'));
    }
    
    /**
     * 日別の統計を取得（グラフ用）
     */
    public static function get_daily_stats($start_date, $end_date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cta_tracker_logs';
        
        $where = "1=1";
        $params = array();
        
        if ($start_date) {
            $where .= " AND DATE(created_at) >= %s";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $where .= " AND DATE(created_at) <= %s";
            $params[] = $end_date;
        }
        
        $sql = "SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN event_type = 'impression' THEN 1 ELSE 0 END) as impressions,
                    SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
                FROM $table_name
                WHERE $where
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * デバイス別の集計データ取得
     */
    public static function get_stats_by_device($start_date = null, $end_date = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cta_tracker_logs';
        
        $where = "1=1";
        $params = array();
        
        if ($start_date) {
            $where .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }
        
        if ($end_date) {
            $where .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }
        
        $sql = "SELECT 
                    device,
                    SUM(CASE WHEN event_type = 'impression' THEN 1 ELSE 0 END) as impressions,
                    SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
                FROM $table_name
                WHERE $where
                GROUP BY device
                ORDER BY clicks DESC";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $results = $wpdb->get_results($sql);
        
        // CTRを計算
        foreach ($results as $result) {
            $impressions = intval($result->impressions);
            $clicks = intval($result->clicks);
            $result->ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        }
        
        return $results;
    }
    
    /**
     * ログの削除（リセット）
     */
    public static function reset_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cta_tracker_logs';
        
        return $wpdb->query("TRUNCATE TABLE $table_name");
    }
}
