<?php
/**
 * CTA URL管理ビュー
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cta-tracker-urls">
    <h1><span class="dashicons dashicons-admin-links"></span> CTA URL管理</h1>
    
    <?php if (isset($message)): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <div class="cta-urls-container">
        <!-- 新規追加/編集フォーム -->
        <div class="cta-url-form-section">
            <h2><?php echo $edit_url ? 'CTA URLを編集' : '新しいCTA URLを追加'; ?></h2>
            <form method="post" action="" class="cta-url-form">
                <?php wp_nonce_field('cta_tracker_save_url'); ?>
                <input type="hidden" name="action" value="save_cta_url">
                <?php if ($edit_url): ?>
                    <input type="hidden" name="url_id" value="<?php echo esc_attr($edit_url['id']); ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cta_name">CTA名 <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="cta_name" name="cta_name" class="regular-text" 
                                   value="<?php echo esc_attr($edit_url['name'] ?? ''); ?>" 
                                   placeholder="例: 無料登録ページ">
                            <p class="description">CTAを識別するための名前を入力してください</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cta_url">CTA URL <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="url" id="cta_url" name="cta_url" class="regular-text" 
                                   value="<?php echo esc_attr($edit_url['url'] ?? ''); ?>" 
                                   placeholder="https://example.com/landing-page" required>
                            <p class="description">トラッキング対象となるCTAのURLを入力してください</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description">説明</label>
                        </th>
                        <td>
                            <textarea id="description" name="description" class="large-text" rows="3" 
                                      placeholder="このCTAの説明や用途を入力してください"><?php echo esc_textarea($edit_url['description'] ?? ''); ?></textarea>
                            <p class="description">このCTAの説明や用途を入力してください（任意）</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-yes-alt"></span> 
                        <?php echo $edit_url ? '更新' : '追加'; ?>
                    </button>
                    <?php if ($edit_url): ?>
                        <a href="<?php echo admin_url('admin.php?page=cta-click-tracker-urls'); ?>" class="button">
                            キャンセル
                        </a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <!-- CTA URL一覧 -->
        <div class="cta-urls-list-section">
            <h2>登録済みCTA URL一覧</h2>
            
            <?php if (empty($urls)): ?>
                <div class="notice notice-info">
                    <p>まだCTA URLが登録されていません。上記のフォームから追加してください。</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 20%;">CTA名</th>
                            <th style="width: 40%;">CTA URL</th>
                            <th style="width: 25%;">説明</th>
                            <th style="width: 10%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($urls as $url): ?>
                            <tr>
                                <td><?php echo esc_html($url['id']); ?></td>
                                <td>
                                    <strong><?php echo esc_html($url['name'] ?: '(名前なし)'); ?></strong>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($url['url']); ?>" target="_blank" class="url-link">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php echo esc_html(mb_substr($url['url'], 0, 60)); ?>
                                        <?php echo mb_strlen($url['url']) > 60 ? '...' : ''; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo esc_html(mb_substr($url['description'] ?? '', 0, 50)); ?>
                                    <?php echo mb_strlen($url['description'] ?? '') > 50 ? '...' : ''; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=cta-click-tracker-urls&edit=' . $url['id']); ?>" 
                                       class="button button-small">
                                        <span class="dashicons dashicons-edit"></span> 編集
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-click-tracker-urls&action=delete&url_id=' . $url['id']), 'cta_tracker_delete_url'); ?>" 
                                       class="button button-small button-link-delete" 
                                       onclick="return confirm('このCTA URLを削除してもよろしいですか？');">
                                        <span class="dashicons dashicons-trash"></span> 削除
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 使用方法 -->
        <div class="cta-urls-usage-section">
            <h2>使用方法</h2>
            <div class="usage-content">
                <h3>1. CTA URLを登録</h3>
                <p>上記のフォームからCTA URLを登録してください。登録したCTA URLは記事内のリンクで使用できます。</p>
                
                <h3>2. 記事内での使用</h3>
                <p>記事内のCTAリンクに <code>data-cta-url</code> 属性を追加してください：</p>
                <pre><code>&lt;a href="https://example.com/landing-page" data-cta-url="https://example.com/landing-page"&gt;
    今すぐ登録する
&lt;/a&gt;</code></pre>
                
                <h3>3. 自動検出（オプション）</h3>
                <p>登録済みのCTA URLは、記事内のリンクで自動的に検出されます。ただし、明示的に <code>data-cta-url</code> 属性を指定することを推奨します。</p>
            </div>
        </div>
    </div>
</div>
