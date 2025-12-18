<?php
/**
 * フロントエンドクラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTA_Click_Tracker_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script(
            'cta-click-tracker',
            CTA_CLICK_TRACKER_URL . 'assets/js/tracker.js',
            array(),
            CTA_CLICK_TRACKER_VERSION,
            true
        );
        
        // 登録済みCTA URLを取得
        $registered_urls = get_option('cta_tracker_urls', array());
        $cta_urls = array();
        foreach ($registered_urls as $url) {
            $cta_urls[] = $url['url'];
        }
        
        wp_localize_script('cta-click-tracker', 'ctaTrackerConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cta_tracker_frontend'),
            'registeredUrls' => $cta_urls,
        ));
    }
}
