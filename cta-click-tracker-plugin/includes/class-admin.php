<?php
/**
 * 管理画面クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTA_Click_Tracker_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        add_action('wp_ajax_cta_tracker_export_csv', array($this, 'ajax_export_csv'));
        add_action('wp_ajax_cta_tracker_save_url', array($this, 'ajax_save_url'));
        add_action('wp_ajax_cta_tracker_delete_url', array($this, 'ajax_delete_url'));
        add_action('wp_ajax_cta_tracker_get_urls', array($this, 'ajax_get_urls'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'CTAクリック率計測',
            'CTA計測',
            'manage_options',
            'cta-click-tracker',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'cta-click-tracker',
            'ダッシュボード',
            'ダッシュボード',
            'manage_options',
            'cta-click-tracker',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'cta-click-tracker',
            'CTA URL管理',
            'CTA URL管理',
            'manage_options',
            'cta-click-tracker-urls',
            array($this, 'render_cta_urls')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'cta-click-tracker') === false) {
            return;
        }
        
        wp_enqueue_style(
            'cta-tracker-admin',
            CTA_CLICK_TRACKER_URL . 'assets/css/admin.css',
            array(),
            CTA_CLICK_TRACKER_VERSION
        );
        
        wp_enqueue_script(
            'cta-tracker-admin',
            CTA_CLICK_TRACKER_URL . 'assets/js/admin.js',
            array('jquery'),
            CTA_CLICK_TRACKER_VERSION,
            true
        );
        
        wp_localize_script('cta-tracker-admin', 'ctaTrackerAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cta_tracker_admin'),
        ));
    }
    
    private function get_date_range($period, $custom_start = '', $custom_end = '') {
        switch ($period) {
            case 'today':
                return array(date('Y-m-d'), date('Y-m-d'));
            case 'week':
                return array(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
            case 'month':
                return array(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
            case 'custom':
                return array($custom_start ?: date('Y-m-d', strtotime('-7 days')), $custom_end ?: date('Y-m-d'));
            default:
                return array(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
        }
    }
    
    public function render_dashboard() {
        $period = sanitize_text_field($_GET['period'] ?? 'week');
        list($start_date, $end_date) = $this->get_date_range($period, $_GET['start_date'] ?? '', $_GET['end_date'] ?? '');
        
        // 期間を文字列に変換
        $period_label = '';
        switch ($period) {
            case 'today':
                $period_label = '今日';
                break;
            case 'week':
                $period_label = '過去7日間';
                break;
            case 'month':
                $period_label = '過去30日間';
                break;
            case 'custom':
                $period_label = $start_date . ' ～ ' . $end_date;
                break;
        }
        
        // データ取得
        $summary = CTA_Click_Tracker_Database::get_summary($start_date, $end_date);
        $prev_summary = CTA_Click_Tracker_Database::get_previous_period_summary($start_date, $end_date);
        
        // 前週比を計算
        $trends = array();
        if ($prev_summary) {
            $trends['impressions'] = $this->calculate_trend($summary->total_impressions ?? 0, $prev_summary->total_impressions ?? 0);
            $trends['clicks'] = $this->calculate_trend($summary->total_clicks ?? 0, $prev_summary->total_clicks ?? 0);
            $trends['ctr'] = $this->calculate_trend($summary->overall_ctr ?? 0, $prev_summary->overall_ctr ?? 0);
        }
        
        $article_stats = CTA_Click_Tracker_Database::get_stats_by_article($start_date, $end_date);
        $cta_stats = CTA_Click_Tracker_Database::get_stats_by_cta($start_date, $end_date);
        $device_stats = CTA_Click_Tracker_Database::get_stats_by_device($start_date, $end_date);
        
        // グラフ用の日別データ取得
        $daily_stats = CTA_Click_Tracker_Database::get_daily_stats($start_date, $end_date);
        
        include CTA_CLICK_TRACKER_PATH . 'includes/views/dashboard.php';
    }
    
    public function ajax_export_csv() {
        check_ajax_referer('cta_tracker_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません');
        }
        
        $start_date = sanitize_text_field($_GET['start_date'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = sanitize_text_field($_GET['end_date'] ?? '') ?: date('Y-m-d');
        
        $stats = CTA_Click_Tracker_Database::get_stats_by_article($start_date, $end_date);
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="cta-tracking-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('記事URL', 'CTA URL', '表示数', 'クリック数', 'CTR(%)'));
        
        foreach ($stats as $stat) {
            fputcsv($output, array(
                $stat->article_url,
                $stat->cta_url,
                $stat->impressions,
                $stat->clicks,
                $stat->ctr
            ));
        }
        
        fclose($output);
        exit;
    }
    
    private function calculate_trend($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? array('value' => 100, 'direction' => 'up') : array('value' => 0, 'direction' => 'neutral');
        }
        
        $change = (($current - $previous) / $previous) * 100;
        $direction = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral');
        
        return array(
            'value' => round(abs($change), 1),
            'direction' => $direction
        );
    }
    
    public function render_cta_urls() {
        // POSTリクエストの処理
        if (isset($_POST['action']) && $_POST['action'] === 'save_cta_url') {
            check_admin_referer('cta_tracker_save_url');
            
            $url_id = isset($_POST['url_id']) ? intval($_POST['url_id']) : 0;
            $cta_url = isset($_POST['cta_url']) ? esc_url_raw($_POST['cta_url']) : '';
            $cta_name = isset($_POST['cta_name']) ? sanitize_text_field($_POST['cta_name']) : '';
            $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
            
            if (empty($cta_url)) {
                $message = '<div class="notice notice-error"><p>CTA URLは必須です。</p></div>';
            } else {
                $urls = get_option('cta_tracker_urls', array());
                
                if ($url_id > 0) {
                    // 更新
                    foreach ($urls as $key => $url) {
                        if ($url['id'] == $url_id) {
                            $urls[$key] = array(
                                'id' => $url_id,
                                'url' => $cta_url,
                                'name' => $cta_name,
                                'description' => $description,
                                'created_at' => $url['created_at'],
                                'updated_at' => current_time('mysql')
                            );
                            break;
                        }
                    }
                    $message = '<div class="notice notice-success"><p>CTA URLを更新しました。</p></div>';
                } else {
                    // 新規追加
                    $new_id = !empty($urls) ? max(array_column($urls, 'id')) + 1 : 1;
                    $urls[] = array(
                        'id' => $new_id,
                        'url' => $cta_url,
                        'name' => $cta_name,
                        'description' => $description,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    );
                    $message = '<div class="notice notice-success"><p>CTA URLを追加しました。</p></div>';
                }
                
                update_option('cta_tracker_urls', $urls);
            }
        }
        
        // 削除処理
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['url_id'])) {
            check_admin_referer('cta_tracker_delete_url');
            
            $url_id = intval($_GET['url_id']);
            $urls = get_option('cta_tracker_urls', array());
            
            foreach ($urls as $key => $url) {
                if ($url['id'] == $url_id) {
                    unset($urls[$key]);
                    break;
                }
            }
            
            $urls = array_values($urls); // インデックスを再構築
            update_option('cta_tracker_urls', $urls);
            
            $message = '<div class="notice notice-success"><p>CTA URLを削除しました。</p></div>';
        }
        
        $urls = get_option('cta_tracker_urls', array());
        $edit_url_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_url = null;
        
        if ($edit_url_id > 0) {
            foreach ($urls as $url) {
                if ($url['id'] == $edit_url_id) {
                    $edit_url = $url;
                    break;
                }
            }
        }
        
        include CTA_CLICK_TRACKER_PATH . 'includes/views/cta-urls.php';
    }
    
    public function ajax_save_url() {
        check_ajax_referer('cta_tracker_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $url_id = isset($_POST['url_id']) ? intval($_POST['url_id']) : 0;
        $cta_url = isset($_POST['cta_url']) ? esc_url_raw($_POST['cta_url']) : '';
        $cta_name = isset($_POST['cta_name']) ? sanitize_text_field($_POST['cta_name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        
        if (empty($cta_url)) {
            wp_send_json_error('CTA URLは必須です');
        }
        
        $urls = get_option('cta_tracker_urls', array());
        
        if ($url_id > 0) {
            // 更新
            foreach ($urls as $key => $url) {
                if ($url['id'] == $url_id) {
                    $urls[$key] = array(
                        'id' => $url_id,
                        'url' => $cta_url,
                        'name' => $cta_name,
                        'description' => $description,
                        'created_at' => $url['created_at'],
                        'updated_at' => current_time('mysql')
                    );
                    break;
                }
            }
        } else {
            // 新規追加
            $new_id = !empty($urls) ? max(array_column($urls, 'id')) + 1 : 1;
            $urls[] = array(
                'id' => $new_id,
                'url' => $cta_url,
                'name' => $cta_name,
                'description' => $description,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );
        }
        
        update_option('cta_tracker_urls', $urls);
        wp_send_json_success(array('message' => '保存しました'));
    }
    
    public function ajax_delete_url() {
        check_ajax_referer('cta_tracker_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $url_id = isset($_POST['url_id']) ? intval($_POST['url_id']) : 0;
        
        if ($url_id <= 0) {
            wp_send_json_error('無効なIDです');
        }
        
        $urls = get_option('cta_tracker_urls', array());
        
        foreach ($urls as $key => $url) {
            if ($url['id'] == $url_id) {
                unset($urls[$key]);
                break;
            }
        }
        
        $urls = array_values($urls);
        update_option('cta_tracker_urls', $urls);
        
        wp_send_json_success(array('message' => '削除しました'));
    }
    
    public function ajax_get_urls() {
        check_ajax_referer('cta_tracker_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $urls = get_option('cta_tracker_urls', array());
        wp_send_json_success($urls);
    }
}
