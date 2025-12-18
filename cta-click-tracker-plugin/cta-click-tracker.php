<?php
/**
 * Plugin Name: CTAクリック率計測
 * Plugin URI: https://example.com
 * Description: 記事内CTAのクリック率を計測するツール。記事の表示回数とCTAのクリック数を記録し、CTRを計算します。
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: cta-click-tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CTA_CLICK_TRACKER_VERSION', '1.0.0');
define('CTA_CLICK_TRACKER_PATH', plugin_dir_path(__FILE__));
define('CTA_CLICK_TRACKER_URL', plugin_dir_url(__FILE__));

require_once CTA_CLICK_TRACKER_PATH . 'includes/class-database.php';
require_once CTA_CLICK_TRACKER_PATH . 'includes/class-admin.php';
require_once CTA_CLICK_TRACKER_PATH . 'includes/class-frontend.php';

class CTA_Click_Tracker {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'check_db_update'));
        
        if (is_admin()) {
            new CTA_Click_Tracker_Admin();
        }
        
        new CTA_Click_Tracker_Frontend();
        
        add_action('wp_ajax_cta_tracker_log', array($this, 'ajax_log_event'));
        add_action('wp_ajax_nopriv_cta_tracker_log', array($this, 'ajax_log_event'));
        add_action('wp_ajax_cta_tracker_reset_logs', array($this, 'ajax_reset_logs'));
    }
    
    public function check_db_update() {
        $db_version = get_option('cta_tracker_db_version', '0');
        
        if (version_compare($db_version, '1.0.0', '<')) {
            CTA_Click_Tracker_Database::create_tables();
            update_option('cta_tracker_db_version', '1.0.0');
        }
    }
    
    public function activate() {
        CTA_Click_Tracker_Database::create_tables();
        update_option('cta_tracker_db_version', '1.0.0');
    }
    
    public function deactivate() {
        // クリーンアップ処理（必要に応じて）
    }
    
    public function init() {
        // 初期化処理
        load_plugin_textdomain('cta-click-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function ajax_log_event() {
        $article_url = isset($_POST['article_url']) ? esc_url_raw($_POST['article_url']) : '';
        $cta_url = isset($_POST['cta_url']) ? esc_url_raw($_POST['cta_url']) : '';
        $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
        $device = isset($_POST['device']) ? sanitize_text_field($_POST['device']) : 'desktop';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        if (empty($article_url) || empty($cta_url)) {
            wp_send_json_error('article_url と cta_url は必須です');
            return;
        }
        
        if (!in_array($event_type, array('impression', 'click'))) {
            wp_send_json_error('無効なevent_typeです');
            return;
        }
        
        $result = CTA_Click_Tracker_Database::insert_log(array(
            'article_url' => $article_url,
            'cta_url' => $cta_url,
            'event_type' => $event_type,
            'device' => in_array($device, array('desktop', 'mobile', 'tablet')) ? $device : 'desktop',
            'session_id' => $session_id,
        ));
        
        if ($result) {
            wp_send_json_success(array('logged' => true));
        } else {
            wp_send_json_error('データベースエラー');
        }
    }
    
    public function ajax_reset_logs() {
        check_ajax_referer('cta_tracker_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $result = CTA_Click_Tracker_Database::reset_logs();
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('リセットに失敗しました');
        }
    }
}

CTA_Click_Tracker::get_instance();
