<?php
/**
 * ダッシュボードビュー
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cta-tracker-dashboard">
    <!-- ヘッダー -->
    <div class="cta-tracker-header">
        <h1><span class="dashicons dashicons-chart-line"></span> CTAクリック率計測ダッシュボード</h1>
    </div>
    
    <!-- 期間選択 -->
    <div class="cta-tracker-period-selector">
        <div class="period-selector-header">
            <span class="dashicons dashicons-calendar-alt"></span>
            <span class="period-label">期間選択</span>
        </div>
        <form method="get" action="" class="period-form">
            <input type="hidden" name="page" value="cta-click-tracker">
            <div class="period-controls">
                <select name="period" id="period-select" class="period-select">
                    <option value="today" <?php selected($period, 'today'); ?>>今日</option>
                    <option value="week" <?php selected($period, 'week'); ?>>過去7日間</option>
                    <option value="month" <?php selected($period, 'month'); ?>>過去30日間</option>
                    <option value="custom" <?php selected($period, 'custom'); ?>>カスタム</option>
                </select>
                <div id="custom-date-range" class="custom-date-range" style="display: <?php echo $period === 'custom' ? 'inline-flex' : 'none'; ?>;">
                    <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>" class="date-input">
                    <span class="date-separator">～</span>
                    <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>" class="date-input">
                </div>
                <button type="submit" class="button button-primary period-submit">
                    <span class="dashicons dashicons-yes-alt"></span> 適用
                </button>
            </div>
        </form>
        <div class="period-actions">
            <a href="<?php echo admin_url('admin-ajax.php?action=cta_tracker_export_csv&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . '&nonce=' . wp_create_nonce('cta_tracker_admin')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-download"></span> CSVエクスポート
            </a>
            <a href="<?php echo admin_url('admin.php?page=cta-click-tracker&period=' . esc_attr($period) . '&start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date)); ?>" class="button button-secondary">
                <span class="dashicons dashicons-update"></span> リフレッシュ
            </a>
        </div>
    </div>
    
    <!-- サマリー -->
    <div class="cta-tracker-summary">
        <div class="summary-header">
            <h2><span class="dashicons dashicons-chart-bar"></span> サマリー統計</h2>
            <span class="summary-period">（<?php echo esc_html($period_label); ?>）</span>
        </div>
        <div class="cta-tracker-stats-grid">
            <div class="cta-tracker-stat-box stat-impressions">
                <div class="stat-icon">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="stat-label">総表示数</div>
                <div class="stat-value"><?php echo number_format($summary->total_impressions ?? 0); ?></div>
                <div class="stat-trend">
                    <?php if (isset($trends['impressions'])): 
                        $trend = $trends['impressions'];
                    ?>
                        <span class="trend-indicator trend-<?php echo esc_attr($trend['direction']); ?>">
                            <?php echo $trend['direction'] === 'up' ? '↑' : ($trend['direction'] === 'down' ? '↓' : '→'); ?>
                        </span>
                        <span class="trend-text">
                            <?php echo $trend['direction'] === 'up' ? '+' : ($trend['direction'] === 'down' ? '-' : ''); ?>
                            <?php echo esc_html($trend['value']); ?>%
                        </span>
                    <?php else: ?>
                        <span class="trend-text">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="cta-tracker-stat-box stat-clicks">
                <div class="stat-icon">
                    <span class="dashicons dashicons-mouse"></span>
                </div>
                <div class="stat-label">総クリック数</div>
                <div class="stat-value"><?php echo number_format($summary->total_clicks ?? 0); ?></div>
                <div class="stat-trend">
                    <?php if (isset($trends['clicks'])): 
                        $trend = $trends['clicks'];
                    ?>
                        <span class="trend-indicator trend-<?php echo esc_attr($trend['direction']); ?>">
                            <?php echo $trend['direction'] === 'up' ? '↑' : ($trend['direction'] === 'down' ? '↓' : '→'); ?>
                        </span>
                        <span class="trend-text">
                            <?php echo $trend['direction'] === 'up' ? '+' : ($trend['direction'] === 'down' ? '-' : ''); ?>
                            <?php echo esc_html($trend['value']); ?>%
                        </span>
                    <?php else: ?>
                        <span class="trend-text">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="cta-tracker-stat-box stat-ctr">
                <div class="stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="stat-label">全体CTR</div>
                <div class="stat-value"><?php echo number_format($summary->overall_ctr ?? 0, 2); ?>%</div>
                <div class="stat-trend">
                    <?php if (isset($trends['ctr'])): 
                        $trend = $trends['ctr'];
                    ?>
                        <span class="trend-indicator trend-<?php echo esc_attr($trend['direction']); ?>">
                            <?php echo $trend['direction'] === 'up' ? '↑' : ($trend['direction'] === 'down' ? '↓' : '→'); ?>
                        </span>
                        <span class="trend-text">
                            <?php echo $trend['direction'] === 'up' ? '+' : ($trend['direction'] === 'down' ? '-' : ''); ?>
                            <?php echo esc_html($trend['value']); ?>%
                        </span>
                    <?php else: ?>
                        <span class="trend-text">-</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 記事別統計 -->
    <div class="cta-tracker-section">
        <div class="section-header">
            <h2><span class="dashicons dashicons-admin-post"></span> 記事別統計</h2>
            <div class="section-actions">
                <input type="text" id="article-search" placeholder="記事URLで検索..." class="search-input">
            </div>
        </div>
        <div class="table-container">
            <table class="wp-list-table widefat fixed striped cta-tracker-table" id="article-stats-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="article_url">記事URL <span class="sort-indicator"></span></th>
                        <th class="sortable" data-sort="cta_url">CTA URL <span class="sort-indicator"></span></th>
                        <th class="sortable numeric" data-sort="impressions">表示数 <span class="sort-indicator"></span></th>
                        <th class="sortable numeric" data-sort="clicks">クリック数 <span class="sort-indicator"></span></th>
                        <th class="sortable numeric" data-sort="ctr">CTR <span class="sort-indicator"></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $has_more_articles = false;
                    $article_stats_limited = array();
                    if (empty($article_stats)): ?>
                        <tr>
                            <td colspan="5" class="no-data">データがありません</td>
                        </tr>
                    <?php else: 
                        $article_stats_limited = array_slice($article_stats, 0, 10);
                        $has_more_articles = count($article_stats) > 10;
                    ?>
                        <?php foreach ($article_stats_limited as $index => $stat): 
                            $comment = '';
                            if ($stat->ctr >= 4.0) {
                                $comment = 'クリック率が高い記事';
                            } elseif ($stat->ctr >= 2.0) {
                                $comment = '安定したパフォーマンス';
                            } else {
                                $comment = '改善の余地あり';
                            }
                        ?>
                            <tr data-article-url="<?php echo esc_attr($stat->article_url); ?>" data-cta-url="<?php echo esc_attr($stat->cta_url); ?>" data-impressions="<?php echo esc_attr($stat->impressions); ?>" data-clicks="<?php echo esc_attr($stat->clicks); ?>" data-ctr="<?php echo esc_attr($stat->ctr); ?>">
                                <td class="url-cell">
                                    <span class="dashicons dashicons-admin-post"></span>
                                    <a href="<?php echo esc_url($stat->article_url); ?>" target="_blank" class="url-link" title="<?php echo esc_attr($stat->article_url); ?>">
                                        <?php echo esc_html(mb_substr($stat->article_url, 0, 60)); ?><?php echo mb_strlen($stat->article_url) > 60 ? '...' : ''; ?>
                                    </a>
                                    <?php if ($comment): ?>
                                        <div class="row-comment">└─ <?php echo esc_html($comment); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="url-cell">
                                    <a href="<?php echo esc_url($stat->cta_url); ?>" target="_blank" class="url-link" title="<?php echo esc_attr($stat->cta_url); ?>">
                                        <?php echo esc_html(mb_substr($stat->cta_url, 0, 60)); ?><?php echo mb_strlen($stat->cta_url) > 60 ? '...' : ''; ?>
                                    </a>
                                </td>
                                <td class="numeric-cell"><?php echo number_format($stat->impressions); ?></td>
                                <td class="numeric-cell"><?php echo number_format($stat->clicks); ?></td>
                                <td class="numeric-cell ctr-cell">
                                    <strong class="ctr-value"><?php echo number_format($stat->ctr, 2); ?>%</strong>
                                    <?php if ($stat->ctr >= 4.0): ?>
                                        <span class="ctr-badge high">高</span>
                                    <?php elseif ($stat->ctr >= 2.0): ?>
                                        <span class="ctr-badge medium">中</span>
                                    <?php else: ?>
                                        <span class="ctr-badge low">低</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($has_more_articles): ?>
            <div class="table-footer">
                <button type="button" class="button button-secondary" id="show-all-articles">
                    <span class="dashicons dashicons-arrow-down-alt2"></span> もっと見る（全<?php echo count($article_stats); ?>件）
                </button>
            </div>
            <div id="all-articles-container" style="display: none;">
                <table class="wp-list-table widefat fixed striped cta-tracker-table">
                    <thead>
                        <tr>
                            <th>記事URL</th>
                            <th>CTA URL</th>
                            <th>表示数</th>
                            <th>クリック数</th>
                            <th>CTR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($article_stats, 10) as $stat): ?>
                            <tr>
                                <td class="url-cell">
                                    <span class="dashicons dashicons-admin-post"></span>
                                    <a href="<?php echo esc_url($stat->article_url); ?>" target="_blank" class="url-link">
                                        <?php echo esc_html(mb_substr($stat->article_url, 0, 60)); ?><?php echo mb_strlen($stat->article_url) > 60 ? '...' : ''; ?>
                                    </a>
                                </td>
                                <td class="url-cell">
                                    <a href="<?php echo esc_url($stat->cta_url); ?>" target="_blank" class="url-link">
                                        <?php echo esc_html(mb_substr($stat->cta_url, 0, 60)); ?><?php echo mb_strlen($stat->cta_url) > 60 ? '...' : ''; ?>
                                    </a>
                                </td>
                                <td class="numeric-cell"><?php echo number_format($stat->impressions); ?></td>
                                <td class="numeric-cell"><?php echo number_format($stat->clicks); ?></td>
                                <td class="numeric-cell ctr-cell">
                                    <strong class="ctr-value"><?php echo number_format($stat->ctr, 2); ?>%</strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- CTA別統計 -->
    <div class="cta-tracker-section">
        <div class="section-header">
            <h2><span class="dashicons dashicons-megaphone"></span> CTA別統計</h2>
            <div class="section-actions">
                <input type="text" id="cta-search" placeholder="CTA URLで検索..." class="search-input">
            </div>
        </div>
        <div class="table-container">
            <table class="wp-list-table widefat fixed striped cta-tracker-table" id="cta-stats-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="cta_url">CTA URL <span class="sort-indicator"></span></th>
                        <th class="sortable numeric" data-sort="impressions">表示数 <span class="sort-indicator"></span></th>
                        <th class="sortable numeric" data-sort="clicks">クリック数 <span class="sort-indicator"></span></th>
                        <th class="sortable numeric" data-sort="ctr">CTR <span class="sort-indicator"></span></th>
                        <th>記事数</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $has_more_ctas = false;
                    $cta_stats_limited = array();
                    $cta_article_counts = array();
                    
                    if (empty($cta_stats)): ?>
                        <tr>
                            <td colspan="5" class="no-data">データがありません</td>
                        </tr>
                    <?php else: 
                        $cta_stats_limited = array_slice($cta_stats, 0, 10);
                        $has_more_ctas = count($cta_stats) > 10;
                        
                        // CTA別の記事数を計算
                        if (!empty($article_stats)) {
                            foreach ($article_stats as $article_stat) {
                                if (!isset($cta_article_counts[$article_stat->cta_url])) {
                                    $cta_article_counts[$article_stat->cta_url] = 0;
                                }
                                $cta_article_counts[$article_stat->cta_url]++;
                            }
                        }
                    ?>
                        <?php foreach ($cta_stats_limited as $stat): 
                            $comment = '';
                            if ($stat->ctr >= 4.0) {
                                $comment = '最も効果的なCTA';
                            } elseif ($stat->ctr >= 2.0) {
                                $comment = '安定したパフォーマンス';
                            } else {
                                $comment = '改善の余地あり';
                            }
                            $article_count = $cta_article_counts[$stat->cta_url] ?? 0;
                        ?>
                            <tr data-cta-url="<?php echo esc_attr($stat->cta_url); ?>" data-impressions="<?php echo esc_attr($stat->impressions); ?>" data-clicks="<?php echo esc_attr($stat->clicks); ?>" data-ctr="<?php echo esc_attr($stat->ctr); ?>">
                                <td class="url-cell">
                                    <span class="dashicons dashicons-megaphone"></span>
                                    <a href="<?php echo esc_url($stat->cta_url); ?>" target="_blank" class="url-link" title="<?php echo esc_attr($stat->cta_url); ?>">
                                        <?php echo esc_html(mb_substr($stat->cta_url, 0, 80)); ?><?php echo mb_strlen($stat->cta_url) > 80 ? '...' : ''; ?>
                                    </a>
                                    <?php if ($comment): ?>
                                        <div class="row-comment">└─ <?php echo esc_html($comment); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="numeric-cell"><?php echo number_format($stat->impressions); ?></td>
                                <td class="numeric-cell"><?php echo number_format($stat->clicks); ?></td>
                                <td class="numeric-cell ctr-cell">
                                    <strong class="ctr-value"><?php echo number_format($stat->ctr, 2); ?>%</strong>
                                    <?php if ($stat->ctr >= 4.0): ?>
                                        <span class="ctr-badge high">高</span>
                                    <?php elseif ($stat->ctr >= 2.0): ?>
                                        <span class="ctr-badge medium">中</span>
                                    <?php else: ?>
                                        <span class="ctr-badge low">低</span>
                                    <?php endif; ?>
                                </td>
                                <td class="numeric-cell"><?php echo number_format($article_count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($has_more_ctas): ?>
            <div class="table-footer">
                <button type="button" class="button button-secondary" id="show-all-ctas">
                    <span class="dashicons dashicons-arrow-down-alt2"></span> もっと見る（全<?php echo count($cta_stats); ?>件）
                </button>
            </div>
            <div id="all-ctas-container" style="display: none;">
                <table class="wp-list-table widefat fixed striped cta-tracker-table">
                    <thead>
                        <tr>
                            <th>CTA URL</th>
                            <th>表示数</th>
                            <th>クリック数</th>
                            <th>CTR</th>
                            <th>記事数</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($cta_stats, 10) as $stat): 
                            $article_count = $cta_article_counts[$stat->cta_url] ?? 0;
                        ?>
                            <tr>
                                <td class="url-cell">
                                    <span class="dashicons dashicons-megaphone"></span>
                                    <a href="<?php echo esc_url($stat->cta_url); ?>" target="_blank" class="url-link">
                                        <?php echo esc_html(mb_substr($stat->cta_url, 0, 80)); ?><?php echo mb_strlen($stat->cta_url) > 80 ? '...' : ''; ?>
                                    </a>
                                </td>
                                <td class="numeric-cell"><?php echo number_format($stat->impressions); ?></td>
                                <td class="numeric-cell"><?php echo number_format($stat->clicks); ?></td>
                                <td class="numeric-cell ctr-cell">
                                    <strong class="ctr-value"><?php echo number_format($stat->ctr, 2); ?>%</strong>
                                </td>
                                <td class="numeric-cell"><?php echo number_format($article_count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- デバイス別統計 -->
    <div class="cta-tracker-section">
        <div class="section-header">
            <h2><span class="dashicons dashicons-smartphone"></span> デバイス別統計</h2>
        </div>
        <div class="device-stats-grid">
            <?php if (empty($device_stats)): ?>
                <div class="no-data">データがありません</div>
            <?php else: ?>
                <?php 
                $device_icons = array(
                    'desktop' => 'dashicons-desktop',
                    'mobile' => 'dashicons-smartphone',
                    'tablet' => 'dashicons-tablet'
                );
                $total_impressions = array_sum(array_map(function($s) { return intval($s->impressions); }, $device_stats));
                foreach ($device_stats as $stat): 
                    $percentage = $total_impressions > 0 ? round((intval($stat->impressions) / $total_impressions) * 100, 1) : 0;
                    $icon = $device_icons[$stat->device] ?? 'dashicons-admin-generic';
                ?>
                    <div class="device-stat-card">
                        <div class="device-icon">
                            <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                        </div>
                        <div class="device-name"><?php echo esc_html(ucfirst($stat->device)); ?></div>
                        <div class="device-stats">
                            <div class="device-stat-row">
                                <span class="stat-label-small">表示数:</span>
                                <span class="stat-value-small"><?php echo number_format($stat->impressions); ?></span>
                            </div>
                            <div class="device-stat-row">
                                <span class="stat-label-small">クリック数:</span>
                                <span class="stat-value-small"><?php echo number_format($stat->clicks); ?></span>
                            </div>
                            <div class="device-stat-row">
                                <span class="stat-label-small">CTR:</span>
                                <span class="stat-value-small ctr-value"><?php echo number_format($stat->ctr, 2); ?>%</span>
                            </div>
                        </div>
                        <div class="device-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                            </div>
                            <div class="progress-label"><?php echo esc_html($percentage); ?>%</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- トレンドグラフ -->
    <?php if (!empty($daily_stats)): ?>
    <div class="cta-tracker-section">
        <div class="section-header">
            <h2><span class="dashicons dashicons-chart-area"></span> トレンドグラフ（<?php echo esc_html($period_label); ?>）</h2>
        </div>
        <div class="trend-charts">
            <div class="trend-chart-container">
                <h3>表示数</h3>
                <canvas id="impressions-chart" width="800" height="200"></canvas>
            </div>
            <div class="trend-chart-container">
                <h3>クリック数</h3>
                <canvas id="clicks-chart" width="800" height="200"></canvas>
            </div>
        </div>
        <script>
            var dailyStatsData = <?php echo json_encode($daily_stats); ?>;
        </script>
    </div>
    <?php endif; ?>
</div>
