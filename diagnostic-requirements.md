# 採用診断ブログパーツ 要件定義（ドラフト）

ブログ埋め込み向けの診断コンポーネント。質問・分岐ロジック・結果テンプレを分離し、非エンジニアがCMS上のテキスト編集だけで更新できることを目的とする。

---

## 1. 目的・概要
- ブログ記事内で回答に応じた採用施策ルートを提示する診断。
- 「質問定義」「分岐ロジック」「結果テンプレ」を独立管理し、差し替えや追加に耐える。
- 複数選択時は優先度ルールで結果を1つに確定。

## 2. 構成ブロックと編集範囲
1) 質問定義
- 編集: 質問文 / 選択肢 / 順序 / 分岐有無 / 単一・複数選択。
- 初期Q1〜Q5を保持（追加・削除可）。

2) 分岐ロジック
- 編集: 優先度順位（1〜6位）と判定条件。
- 初期優先度: 1.エンジニア(Q2) 2.ブランディング(Q5) 3.何もかもわからない(Q5) 4.少人数×幅広(Q5) 5.年間50人以上(Q1) 6.その他。

3) 結果テンプレ
- 編集: 見出し、本文、箇条書き、CTAラベル/URL、補足文、OG/Twitter用タイトル/説明。

4) 全体構造
- 質問追加・削除、結果追加が可能（ID管理で増減耐性）。

## 3. 画面/UI要件
- レイアウト: 1カラム縦積み、レスポンシブ（SP/PC）。
- 表示形式: 1問ずつ表示（プログレッシブ）。戻る/次へ、進捗バー（例: 3/5）。
- 選択UI: 単一=ラジオカード、複数=チェックカード。カード幅100%でタップ領域広め。
- ボタン文言: 「次へ」「結果を見る」「戻る」は編集可。
- バリデーション: 必須未回答時エラー表示（文言編集可）。
- 結果画面:
  - 基本: 結果タイトル、短い説明テキスト（任意）、CTAボタン、補足テキスト/リンク（任意）、再診断ボタン。
  - OG/Twitter用見出し・説明を結果ID単位で設定可。
  - 比較ブロック: 「即戦力RPO」と、ルートごとに設定する比較サービスを表形式で比較。編集者が以下を差し替え可:
    - 比較項目の行ラベル（例: 料金モデル / 得意領域 / スピード / 運用体制 など）
    - 列ヘッダー: 左=即戦力RPO、右=ルートごとに設定する比較サービス名（列名も編集可）
    - 各セル文言（短文/箇条書き想定）
    - 画像パス（ロゴやサービスイメージ）を列ごとに設定可能。altテキストも編集可。
    - 表示/非表示の切り替え（表ごと、行ごと）
- アニメーション: 軽いフェード/スライド（ON/OFF可）。
- アクセシビリティ: キーボード操作可、aria-label付与、コントラストAA以上、フォーカス可視。
- デザインテーマ: 基調色 #f54b02（プライマリ）/#ff6b03（セカンダリ、アクセント）、白・黒を併用。ゴシック体（サンセリフ）を標準フォントとする。

## 4. ロジック要件
- 判定: 複数選択時、優先度順で最初にマッチしたルートを採用。
- データ構造: IDベース管理（表示ラベルと分離）。
- 分岐条件: 質問ごとに `branchKey` / `operator` / `value` / `target` を定義し優先度判定で使用。
- 結果テンプレ: 最低限 `title`, `bullets[]`, `ctaLabel`, `ctaUrl`, `note`, `ogTitle`, `ogDescription`。
- Mermaid: ブログに埋め込める文字列として保持し、更新容易に。

## 5. 編集・運用要件
- フォーマット: JSONまたはYAML（`questions`, `priorityRules`, `results`, `flowchart` を分離）。
- 編集者: CMSからテキスト編集のみで更新（コード変更不要）。
- 履歴: 更新者/更新日時/メモなどのメタ情報を任意フィールドで保持。
- 選択肢増減: 選択肢IDを用い、表示ラベル変更や増減に耐える。

## 6. 計測・イベント要件
- GA/GTM等で以下を計測可能に（data-attributesでフック想定）。
  - 質問表示・回答（questionId, optionId）。
  - 結果確定（routeId）。
  - CTAクリック（routeId, url, utm など）。
- CTAに任意のクエリパラメータ付与を許容。

## 7. 非機能要件
- パフォーマンス: 初期バンドル軽量、CLS防止の高さ確保。
- 互換性: モダンブラウザ + iOS/Android主要ブラウザ。
- セキュリティ: スタイル衝突回避（名前空間/プレフィックス）、XSS対策（外部入力なし想定、テンプレはサニタイズ運用）。
- アクセシビリティ: 前述のARIA/コントラスト/フォーカス対応。

## 8. 初期データモデル例（YAML）
```yaml
questions:
  - id: Q1
    title: 年間採用人数
    type: single
    options:
      - { id: headcount_5,   label: "5名程度" }
      - { id: headcount_10,  label: "10名程度" }
      - { id: headcount_20,  label: "20名程度" }
      - { id: headcount_30,  label: "30名程度" }
      - { id: headcount_50,  label: "50名程度" }
      - { id: headcount_100, label: "100名以上" }
    branches:
      - { key: headcount, operator: ">=", value: 50, target: high_volume }
      - { key: headcount, operator: "<",  value: 50, target: not_high_volume }

  - id: Q2
    title: 採用したい職種
    type: single
    options:
      - { id: role_engineer,        label: "エンジニア" }
      - { id: role_sales,           label: "営業" }
      - { id: role_cs,              label: "CS" }
      - { id: role_marketing,       label: "マーケ" }
      - { id: role_backoffice,      label: "バックオフィス" }
      - { id: role_designer,        label: "デザイナー" }
      - { id: role_pm,              label: "PM" }
      - { id: role_engineer_detail, label: "エンジニア（フロント・バック・インフラ etc…）" }
    branches:
      - { key: role, operator: "==", value: engineer, target: engineer }
      - { key: role, operator: "!=", value: engineer, target: non_engineer }

  - id: Q3
    title: 採用したい雇用形態
    type: single
    options:
      - { id: emp_parttime, label: "アルバイト" }
      - { id: emp_intern,   label: "インターン" }
      - { id: emp_contract, label: "業務委託" }
      - { id: emp_fulltime, label: "正社員" }
    branches: []

  - id: Q4
    title: 企業規模
    type: single
    options:
      - { id: size_smb,     label: "中小企業" }
      - { id: size_venture, label: "ベンチャー" }
      - { id: size_enterprise, label: "大企業" }
    branches: []

  - id: Q5
    title: 採用課題（複数選択可）
    type: multi
    options:
      - { id: issue_branding,    label: "ブランディングしたい" }
      - { id: issue_multi_small, label: "幅広い職種を少人数ずつ採用したい" }
      - { id: issue_unknown,     label: "何もかもわからない" }
      - { id: issue_resource,    label: "人事リソース不足" }
      - { id: issue_pool,        label: "母集団形成したい" }
      - { id: issue_pdca,        label: "PDCA回せない" }
      - { id: issue_requirements,label: "要件定義がわからない" }
      - { id: issue_cost,        label: "採用コストを抑えたい" }
      - { id: issue_knowledge,   label: "採用ナレッジがない" }
    branches:
      - { key: issue, operator: "includes", value: issue_branding,    target: branding }
      - { key: issue, operator: "includes", value: issue_multi_small, target: multi_small }
      - { key: issue, operator: "includes", value: issue_unknown,     target: unknown }

priorityRules:
  - { id: engineer,    target: R1 }
  - { id: branding,    target: R2 }
  - { id: unknown,     target: R3 }
  - { id: multi_small, target: R4 }
  - { id: high_volume, target: R5 }
  - { id: other,       target: R6 }

results:
  # 各結果ごとに comparisonTable を個別定義し、比較サービス名・行項目・セル内容を差し替え可能
  - id: R1
    title: エンジニアルート
    bullets:
      - 技術者採用専用の母集団形成
      - エンジニア特化媒体の運用
      - 求人票の技術的要件整理
      - 現場巻き込みの選考設計
    ctaLabel: 無料相談する
    ctaUrl: https://example.com/engineer
    note: 追記枠
    ogTitle: エンジニア採用に特化したRPO
    ogDescription: エンジニア採用向けの最適プラン
    comparisonTable:
      enabled: true
      columns:
        - id: col_primary
          label: 即戦力RPO
          image:
            src: /path/to/primary.png
            alt: 即戦力RPOロゴ
        - id: col_other
          label: 他サービス（例: エンジニア特化ATS）
          image:
            src: /path/to/other.png
            alt: 他サービスロゴ
      # 列はルートごとに別サービス名・別ロゴへ差し替え可能
      rows:
        - id: row_pricing
          label: 料金モデル
          cells:
            col_primary: 固定＋成果 / 月額など
            col_other: 従量/成果/掲載課金 など
        - id: row_strength
          label: 得意領域
          cells:
            col_primary: エンジニア採用に強い
            col_other: 総合型
      hideableRows: true  # 行単位で表示/非表示をCMS側で制御可能
  # R2〜R6 同様に定義

flowchart: |
  flowchart TD
    Q1[Q1：年間採用人数<br>分岐：50人以上 or 以下] --> Q2
    Q2[Q2：採用したい職種<br>分岐：エンジニア or それ以外] --> Q3
    Q3[Q3：雇用形態（分岐なし）] --> Q4
    Q4[Q4：企業規模（分岐なし）] --> Q5
    Q5[Q5：採用課題（複数選択）<br>分岐：ブランディング / 少人数×幅広 / 何もかもわからない] --> PRIORITY
    PRIORITY{優先度ルールに基づき結果を1つに決定}
    PRIORITY -->|1. エンジニア| R1
    PRIORITY -->|2. ブランディング| R2
    PRIORITY -->|3. 何もかもわからない| R3
    PRIORITY -->|4. 少人数×幅広| R4
    PRIORITY -->|5. 年間50人以上| R5
    PRIORITY -->|6. その他| R6
    R1[結果：エンジニアルート]
    R2[結果：ブランディングルート]
    R3[結果：ゼロ知識ルート]
    R4[結果：少人数マルチ職種ルート]
    R5[結果：ハイボリューム採用ルート]
    R6[結果：一般ルート]
```

### 付録: ASCIIワイヤー（推奨プランなし・結果ヘッダー最小構成）
```
+--------------------------------------------------------------+
| LOGO (img)                                                   |
+--------------------------------------------------------------+
| Q1: 年間採用人数はどれくらいですか？                         |
| [○ 5名程度] [○ 10名程度] [○ 20名程度] [○ 30名程度]          |
| [○ 50名程度] [○ 100名以上]                                  |
| Progress: [███-----] 1/5                                     |
|           ← 戻る                    次へ →                   |
+--------------------------------------------------------------+
| Q2: 採用したい職種を教えてください。                        |
| [○ エンジニア] [○ 営業] [○ CS] [○ マーケ] [○ バックオフィス] |
| [○ デザイナー] [○ PM] [○ エンジニア（詳細）]                |
| Progress: [████----] 2/5                                     |
|           ← 戻る                    次へ →                   |
+--------------------------------------------------------------+
| ...（Q3/Q4/Q5 同様のカード表示）                            |
+--------------------------------------------------------------+
| あなたにおすすめの採用代行はこちら                           |
| 比較表（ルートごとに差し替え）                              |
|            | 即戦力RPO (img) | 他サービス (img)             |
| 料金モデル  | 固定＋成果       | 従量/成果/掲載課金など       |
| 得意領域    | エンジニア特化   | 総合型                      |
| スピード    | 〇〇〇           | 〇〇                        |
| 運用体制    | 伴走＋代行       | 〇〇                        |
| ...        | ...             | ...                         |
+--------------------------------------------------------------+
| CTAボタン                                                    |
| - 主CTA / （必要ならサブCTAも可）                            |
+--------------------------------------------------------------+
```

## 9. 今後決めたいこと（要フィードバック）
- フォーマット最終決定（JSON/YAML/frontmatter）。
- 埋め込み手段（iframe / script / Web Component）。
- トラッキングの実装先とイベント命名。
- デザインテーマ初期値（色/余白/角丸/影）。
- CTAリンク既定値とUTM付与方針。
