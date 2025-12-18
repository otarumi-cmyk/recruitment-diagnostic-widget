# CTAクリック率計測プラグイン

記事内CTAのクリック率を計測するWordPressプラグインです。記事の表示回数とCTAのクリック数を記録し、CTR（Click Through Rate）を計算します。

## 機能

- 記事の表示回数の自動記録
- CTAリンクのクリック数の記録
- CTR（クリック率）の自動計算
- 記事別・CTA別・デバイス別の統計表示
- CSVエクスポート機能
- 重複記録の防止（5分以内の同一セッションは重複として扱わない）

## インストール

1. `cta-click-tracker-plugin` フォルダを WordPress の `wp-content/plugins/` ディレクトリにアップロード
2. WordPress管理画面の「プラグイン」メニューから「CTAクリック率計測」を有効化

## 使い方

### 1. CTA URLを登録（推奨）

WordPress管理画面の「CTA計測」→「CTA URL管理」から、トラッキング対象となるCTA URLを事前に登録できます。

- **CTA名**: CTAを識別するための名前（例: 無料登録ページ）
- **CTA URL**: トラッキング対象となるURL
- **説明**: CTAの説明や用途（任意）

登録したCTA URLは、記事内のリンクで自動的に検出されます。

### 2. 記事にCTAリンクを追加

#### 方法1: data-cta-url属性を使用（推奨）

記事内のCTAリンクに `data-cta-url` 属性を追加してください：

```html
<a href="https://example.com/landing-page" data-cta-url="https://example.com/landing-page">
    今すぐ登録する
</a>
```

#### 方法2: 自動検出

CTA URL管理で登録済みのURLと一致するリンクは、自動的に検出されます。ただし、明示的に `data-cta-url` 属性を指定することを推奨します。

### 3. トラッキングの動作

- ページ読み込み時に、`data-cta-url` 属性を持つリンク、または登録済みCTA URLと一致するリンクが自動的に検出されます
- 各CTAリンクの表示が自動的に記録されます（重複防止あり）
- ユーザーがCTAリンクをクリックすると、クリックが記録されます

### 4. CTA URLの管理

WordPress管理画面の「CTA計測」→「CTA URL管理」から：

- 新しいCTA URLを追加
- 既存のCTA URLを編集・削除
- 登録済みCTA URLの一覧を確認

### 5. 統計の確認

WordPress管理画面の「CTA計測」→「ダッシュボード」から、以下の統計を確認できます：

- **サマリー**: 総表示数、総クリック数、全体CTR
- **記事別統計**: 各記事URLとCTA URLの組み合わせごとの統計
- **CTA別統計**: CTA URLごとの集計
- **デバイス別統計**: デスクトップ・モバイル・タブレット別の統計

### 6. CSVエクスポート

ダッシュボード画面の「CSVエクスポート」ボタンから、統計データをCSV形式でダウンロードできます。

## 手動トラッキング

JavaScriptから手動でトラッキングすることも可能です：

```javascript
// 表示を記録
CTATracker.trackImpression(articleUrl, ctaUrl);

// クリックを記録
CTATracker.trackClick(articleUrl, ctaUrl);
```

## データベース

プラグインは以下のテーブルを作成します：

- `wp_cta_tracker_logs`: 表示とクリックのログを記録

## 注意事項

- 同じセッション（ブラウザ）で、同じ記事・同じCTA・同じイベントタイプ（表示/クリック）が5分以内に複数回発生した場合、重複として扱われ、1回のみ記録されます
- セッションIDは `localStorage` に保存されます

## カスタマイズ

### デバッグモードの有効化

ブラウザのコンソールで以下のコードを実行すると、デバッグログが表示されます：

```javascript
CTATracker.config.debug = true;
```

## ライセンス

GPL v2 or later
