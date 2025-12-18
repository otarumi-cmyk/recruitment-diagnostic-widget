/**
 * CTAクリック率計測 - フロントエンドトラッキングスクリプト
 */

(function() {
    'use strict';
    
    // 設定
    const CONFIG = {
        apiUrl: window.ctaTrackerConfig?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: window.ctaTrackerConfig?.nonce || '',
        autoTrack: true, // ページ読み込み時に自動で表示を記録
        debug: false
    };
    
    // セッションIDの取得または生成
    function getSessionId() {
        let sessionId = localStorage.getItem('cta_tracker_session_id');
        if (!sessionId) {
            sessionId = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            localStorage.setItem('cta_tracker_session_id', sessionId);
        }
        return sessionId;
    }
    
    // デバイス判定
    function detectDevice() {
        const ua = navigator.userAgent.toLowerCase();
        if (ua.indexOf('mobile') !== -1 || ua.indexOf('android') !== -1 || ua.indexOf('iphone') !== -1 || ua.indexOf('ipad') !== -1) {
            if (ua.indexOf('tablet') !== -1 || ua.indexOf('ipad') !== -1) {
                return 'tablet';
            }
            return 'mobile';
        }
        return 'desktop';
    }
    
    // ユーティリティ関数
    function log(message, data = null) {
        if (CONFIG.debug) {
            console.log('[CTA Tracker]', message, data || '');
        }
    }
    
    function getCurrentUrl() {
        return window.location.href;
    }
    
    function findCTALinks() {
        const ctaLinks = [];
        
        // 1. data-cta-url属性を持つリンクを探す
        const explicitLinks = document.querySelectorAll('a[data-cta-url]');
        explicitLinks.forEach(link => {
            ctaLinks.push({
                element: link,
                url: link.getAttribute('data-cta-url') || link.href
            });
        });
        
        // 2. 登録済みCTA URLと一致するリンクを探す（data-cta-urlがない場合）
        if (CONFIG.registeredUrls && CONFIG.registeredUrls.length > 0) {
            const allLinks = document.querySelectorAll('a[href]');
            allLinks.forEach(link => {
                // 既に追加されている場合はスキップ
                if (link.hasAttribute('data-cta-url')) {
                    return;
                }
                
                const href = link.href;
                // 登録済みURLと一致するかチェック
                for (const registeredUrl of CONFIG.registeredUrls) {
                    if (href === registeredUrl || href.startsWith(registeredUrl + '#')) {
                        ctaLinks.push({
                            element: link,
                            url: registeredUrl
                        });
                        break;
                    }
                }
            });
        }
        
        return ctaLinks;
    }
    
    function sendRequest(action, data) {
        const formData = new FormData();
        formData.append('action', 'cta_tracker_log');
        formData.append('event_type', action);
        
        if (CONFIG.nonce) {
            formData.append('nonce', CONFIG.nonce);
        }
        
        for (const key in data) {
            if (data.hasOwnProperty(key)) {
                formData.append(key, data[key]);
            }
        }
        
        // 非同期で送信（エラーは無視）
        fetch(CONFIG.apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(response => {
            if (CONFIG.debug) {
                return response.json().then(data => {
                    log('API Response:', data);
                });
            }
        }).catch(error => {
            if (CONFIG.debug) {
                log('API Error:', error);
            }
        });
    }
    
    function trackImpression(articleUrl, ctaUrl) {
        log('Tracking impression:', { articleUrl, ctaUrl });
        sendRequest('impression', {
            article_url: articleUrl,
            cta_url: ctaUrl,
            session_id: getSessionId(),
            device: detectDevice()
        });
    }
    
    function trackClick(articleUrl, ctaUrl) {
        log('Tracking click:', { articleUrl, ctaUrl });
        sendRequest('click', {
            article_url: articleUrl,
            cta_url: ctaUrl,
            session_id: getSessionId(),
            device: detectDevice()
        });
    }
    
    function initTracking() {
        const articleUrl = getCurrentUrl();
        const ctaLinks = findCTALinks();
        
        if (ctaLinks.length === 0) {
            log('No CTA links found');
            return;
        }
        
        log('Found CTA links:', ctaLinks.length);
        
        // 各CTAリンクに対してイベントリスナーを設定
        ctaLinks.forEach(linkData => {
            const link = linkData.element;
            const ctaUrl = linkData.url;
            
            // 表示を記録（一度だけ）
            if (CONFIG.autoTrack) {
                trackImpression(articleUrl, ctaUrl);
            }
            
            // クリックを記録
            link.addEventListener('click', function(e) {
                trackClick(articleUrl, ctaUrl);
                
                // デフォルトの動作を続行（リンク遷移）
            });
        });
    }
    
    // ページ読み込み時に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTracking);
    } else {
        initTracking();
    }
    
    // グローバルAPIを公開（手動でトラッキングしたい場合）
    window.CTATracker = {
        trackImpression: trackImpression,
        trackClick: trackClick,
        config: CONFIG
    };
    
})();
