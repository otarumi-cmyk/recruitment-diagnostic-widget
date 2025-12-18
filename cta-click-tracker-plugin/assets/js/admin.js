/**
 * 管理画面JavaScript
 */

jQuery(document).ready(function($) {
    // 期間選択のカスタム日付範囲表示/非表示
    $('#period-select').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom-date-range').show();
        } else {
            $('#custom-date-range').hide();
        }
    });
    
    // 数値のカウントアップアニメーション
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            element.textContent = current.toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                element.textContent = end.toLocaleString();
            }
        };
        window.requestAnimationFrame(step);
    }
    
    // サマリー統計の数値をアニメーション
    $('.stat-value').each(function() {
        const $this = $(this);
        const text = $this.text().replace(/[^0-9.]/g, '');
        const value = parseFloat(text);
        if (!isNaN(value) && value > 0) {
            $this.attr('data-value', Math.floor(value));
            $this.text('0');
            setTimeout(() => {
                animateValue(this, 0, Math.floor(value), 1000);
            }, 100);
        }
    });
    
    // テーブルのソート機能
    $('.sortable').on('click', function() {
        const $th = $(this);
        const $table = $th.closest('table');
        const column = $th.data('sort');
        const isNumeric = $th.hasClass('numeric');
        const isAsc = $th.hasClass('sort-asc');
        
        // すべてのソートインジケーターをリセット
        $table.find('.sort-indicator').text('');
        $table.find('.sortable').removeClass('sort-asc sort-desc');
        
        // ソート方向を設定
        if (isAsc) {
            $th.addClass('sort-desc');
            $th.find('.sort-indicator').text(' ↓');
        } else {
            $th.addClass('sort-asc');
            $th.find('.sort-indicator').text(' ↑');
        }
        
        // テーブルをソート
        const $tbody = $table.find('tbody');
        const $rows = $tbody.find('tr').toArray();
        
        $rows.sort(function(a, b) {
            let aVal, bVal;
            
            if (column === 'article_url') {
                aVal = $(a).data('article-url') || '';
                bVal = $(b).data('article-url') || '';
            } else if (column === 'cta_url') {
                aVal = $(a).data('cta-url') || '';
                bVal = $(b).data('cta-url') || '';
            } else {
                aVal = parseFloat($(a).data(column)) || 0;
                bVal = parseFloat($(b).data(column)) || 0;
            }
            
            if (isNumeric) {
                return isAsc ? aVal - bVal : bVal - aVal;
            } else {
                if (isAsc) {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            }
        });
        
        $tbody.empty().append($rows);
    });
    
    // 記事別統計の検索機能
    $('#article-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        const $table = $('#article-stats-table');
        const $rows = $table.find('tbody tr');
        
        $rows.each(function() {
            const $row = $(this);
            const articleUrl = ($row.data('article-url') || '').toLowerCase();
            const ctaUrl = ($row.data('cta-url') || '').toLowerCase();
            
            if (articleUrl.indexOf(searchTerm) !== -1 || ctaUrl.indexOf(searchTerm) !== -1) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    });
    
    // CTA別統計の検索機能
    $('#cta-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        const $table = $('#cta-stats-table');
        const $rows = $table.find('tbody tr');
        
        $rows.each(function() {
            const $row = $(this);
            const ctaUrl = ($row.data('cta-url') || '').toLowerCase();
            
            if (ctaUrl.indexOf(searchTerm) !== -1) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    });
    
    // 「もっと見る」ボタン
    $('#show-all-articles').on('click', function() {
        $('#all-articles-container').slideDown();
        $(this).hide();
    });
    
    $('#show-all-ctas').on('click', function() {
        $('#all-ctas-container').slideDown();
        $(this).hide();
    });
    
    // トレンドグラフの描画
    if (typeof dailyStatsData !== 'undefined' && dailyStatsData.length > 0) {
        drawChart('impressions-chart', dailyStatsData, 'impressions', '表示数');
        drawChart('clicks-chart', dailyStatsData, 'clicks', 'クリック数');
    }
    
    function drawChart(canvasId, data, field, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        const padding = 40;
        const chartWidth = width - padding * 2;
        const chartHeight = height - padding * 2;
        
        // データの準備
        const values = data.map(d => parseInt(d[field]) || 0);
        const dates = data.map(d => d.date);
        const maxValue = Math.max(...values, 1);
        
        // 背景をクリア
        ctx.clearRect(0, 0, width, height);
        
        // グリッド線を描画
        ctx.strokeStyle = '#e0e0e0';
        ctx.lineWidth = 1;
        for (let i = 0; i <= 5; i++) {
            const y = padding + (chartHeight / 5) * i;
            ctx.beginPath();
            ctx.moveTo(padding, y);
            ctx.lineTo(width - padding, y);
            ctx.stroke();
        }
        
        // Y軸ラベル
        ctx.fillStyle = '#666';
        ctx.font = '12px sans-serif';
        ctx.textAlign = 'right';
        for (let i = 0; i <= 5; i++) {
            const value = Math.floor((maxValue / 5) * (5 - i));
            const y = padding + (chartHeight / 5) * i;
            ctx.fillText(formatValue(value), padding - 10, y + 4);
        }
        
        // グラフ線を描画
        if (values.length > 0) {
            ctx.strokeStyle = '#2271b1';
            ctx.lineWidth = 2;
            ctx.beginPath();
            
            values.forEach((value, index) => {
                const x = padding + (chartWidth / (values.length - 1)) * index;
                const y = padding + chartHeight - (value / maxValue) * chartHeight;
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.stroke();
            
            // データポイントを描画
            ctx.fillStyle = '#2271b1';
            values.forEach((value, index) => {
                const x = padding + (chartWidth / (values.length - 1)) * index;
                const y = padding + chartHeight - (value / maxValue) * chartHeight;
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, Math.PI * 2);
                ctx.fill();
            });
        }
        
        // X軸ラベル（日付）
        ctx.fillStyle = '#666';
        ctx.font = '10px sans-serif';
        ctx.textAlign = 'center';
        dates.forEach((date, index) => {
            if (index % Math.ceil(dates.length / 6) === 0 || index === dates.length - 1) {
                const x = padding + (chartWidth / (values.length - 1)) * index;
                const dateObj = new Date(date);
                const label = (dateObj.getMonth() + 1) + '/' + dateObj.getDate();
                ctx.fillText(label, x, height - 10);
            }
        });
    }
    
    function formatValue(value) {
        if (value >= 1000000) {
            return (value / 1000000).toFixed(1) + 'M';
        } else if (value >= 1000) {
            return (value / 1000).toFixed(1) + 'K';
        }
        return value.toString();
    }
    
    // ホバーエフェクト
    $('.cta-tracker-stat-box').hover(
        function() {
            $(this).css('transform', 'translateY(-2px)');
            $(this).css('box-shadow', '0 4px 8px rgba(0,0,0,0.1)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
            $(this).css('box-shadow', '0 1px 1px rgba(0,0,0,.04)');
        }
    );
    
    $('.device-stat-card').hover(
        function() {
            $(this).css('transform', 'translateY(-2px)');
            $(this).css('box-shadow', '0 4px 8px rgba(0,0,0,0.1)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
            $(this).css('box-shadow', '0 1px 1px rgba(0,0,0,.04)');
        }
    );
});
