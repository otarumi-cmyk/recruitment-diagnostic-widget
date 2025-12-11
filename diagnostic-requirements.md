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
  - id: Q0
    title: 新卒採用と中途採用のどちらを対象にしますか？
    type: single
    options:
      - { id: recruit_newgrad, label: "新卒採用", value: "newgrad" }
      - { id: recruit_midcareer, label: "中途採用", value: "midcareer" }
    branches:
      - { key: recruit_type, operator: "==", value: "newgrad", target: newgrad }
      - { key: recruit_type, operator: "==", value: "midcareer", target: midcareer }

  - id: Q0_5
    title: シード枠での採用を検討していますか？
    type: single
    condition: { key: recruit_type, operator: "==", value: "newgrad" }  # 新卒を選んだ場合のみ表示
    options:
      - { id: seed_yes, label: "はい", value: "seed" }
      - { id: seed_no, label: "いいえ", value: "not_seed" }
    branches:
      - { key: seed_type, operator: "==", value: "seed", target: seed }
      - { key: seed_type, operator: "==", value: "not_seed", target: not_seed }

  - id: Q1
    title: 年間の採用人数はどれくらいですか？
    type: single
    options:
      - { id: headcount_1_5,   label: "1~5名", value: 5 }
      - { id: headcount_6_10,  label: "6~10名", value: 10 }
      - { id: headcount_11_20, label: "11~20名", value: 20 }
      - { id: headcount_21_30, label: "21~30名", value: 30 }
      - { id: headcount_31_50, label: "31~50名", value: 50 }
      - { id: headcount_51_100, label: "51~100名", value: 100 }
      - { id: headcount_101,   label: "101名以上", value: 101 }
    branches:
      - { key: headcount, operator: ">=", value: 50, target: high_volume }
      - { key: headcount, operator: "<",  value: 50, target: not_high_volume }

  - id: Q2
    title: 採用したい職種を教えてください。
    type: single
    options:
      - { id: role_it_engineer, label: "ITエンジニア・PM", value: "engineer" }
      - { id: role_designer,    label: "デザイナー・ディレクター", value: "designer" }
      - { id: role_marketing,   label: "マーケティング・企画・PdM", value: "marketing" }
      - { id: role_sales,       label: "営業", value: "sales" }
      - { id: role_cs,          label: "カスタマーサクセス", value: "cs" }
      - { id: role_backoffice,  label: "経理・管理・バックオフィス", value: "backoffice" }
      - { id: role_executive,   label: "経営・CxO", value: "executive" }
      - { id: role_office,       label: "事務職", value: "office" }
      - { id: role_retail,       label: "販売・サービス", value: "retail" }
      - { id: role_consultant,  label: "士業・コンサルタント", value: "consultant" }
      - { id: role_tech_eng,     label: "機械・電気・電子・半導体", value: "tech_eng" }
      - { id: role_construction, label: "建築設計・土木・プラント", value: "construction" }
      - { id: role_technical,   label: "技術工・警備・設備", value: "technical" }
      - { id: role_other,       label: "その他", value: "other" }
    branches:
      - { key: role, operator: "==", value: "engineer", target: engineer }
      - { key: role, operator: "!=", value: "engineer", target: non_engineer }

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
    title: 従業員数はどれくらいですか？
    type: single
    options:
      - { id: empcount_1_29,    label: "1~29名", value: 29 }
      - { id: empcount_30_99,   label: "30~99名", value: 99 }
      - { id: empcount_100_299, label: "100~299名", value: 299 }
      - { id: empcount_300_999, label: "300~999名", value: 999 }
      - { id: empcount_1000_4999, label: "1000~4999名", value: 4999 }
      - { id: empcount_5000_plus, label: "5000名以上", value: 5000 }
    branches: []

  - id: Q5
    title: 抱えている採用課題を教えてください。（複数選択可）
    type: multi
    options:
      - { id: issue_branding,    label: "SNS・動画・記事・パンフレット・採用ページなどブランディングしたい" }
      - { id: issue_multi_small, label: "幅広い職種を少人数ずつ採用したい" }
      - { id: issue_unknown,     label: "採用戦略や進め方がわからない" }
      - { id: issue_resource,    label: "人事リソース不足" }
      - { id: issue_pool,        label: "母集団形成したい" }
      - { id: issue_pdca,        label: "採用のPDCAを回せない" }
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
  - { id: seed,        target: R7 }  # シード枠（優先度最低）
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
| Q0: 新卒採用と中途採用のどちらを対象にしますか？             |
| [○ 新卒採用] [○ 中途採用]                                    |
| Progress: [█------] 1/6                                      |
|           ← 戻る                    次へ →                    |
+--------------------------------------------------------------+
| Q0.5: シード枠での採用を検討していますか？（新卒選択時のみ） |
| [○ はい] [○ いいえ]                                          |
| Progress: [██-----] 2/6                                      |
|           ← 戻る                    次へ →                    |
+--------------------------------------------------------------+
| Q1: 年間の採用人数はどれくらいですか？                       |
| [○ 1~5名] [○ 6~10名] [○ 11~20名] [○ 21~30名]                 |
| [○ 31~50名] [○ 51~100名] [○ 101名以上]                      |
| Progress: [███----] 3/6                                      |
|           ← 戻る                    次へ →                    |
+--------------------------------------------------------------+
| Q2: 採用したい職種を教えてください。                         |
| [○ ITエンジニア・PM] [○ デザイナー・ディレクター]            |
| [○ マーケティング・企画・PdM] [○ 営業] [○ CS]               |
| [○ 経理・管理・バックオフィス] [○ 経営・CxO]                 |
| [○ 事務職] [○ 販売・サービス] [○ 士業・コンサルタント]      |
| [○ 機械・電気・電子・半導体] [○ 建築設計・土木・プラント]    |
| [○ 技術工・警備・設備] [○ その他]                           |
| Progress: [████---] 4/6                                      |
|           ← 戻る                    次へ →                    |
+--------------------------------------------------------------+
| ...（Q3/Q4/Q5 同様のカード表示）                             |
+--------------------------------------------------------------+
| あなたにおすすめの採用代行はこちら                           |
| 比較表（3サービス比較）                                      |
|            | サービスA (img) | サービスB (img) | サービスC (img) |
| 料金モデル  | ...            | ...            | ...              |
| 得意領域    | ...            | ...            | ...              |
| スピード    | ...            | ...            | ...              |
| 運用体制    | ...            | ...            | ...              |
| ...        | ...            | ...            | ...              |
+--------------------------------------------------------------+
| 即戦力RPO（まとめ提案ブロック）                              |
| 【全部入りでこのプラン】                                     |
| - 料金例: 月額XX万円〜（成果連動を含め柔軟に設定可）          |
| - 含まれるもの: 母集団形成 / クリエイティブ / 選考調整 /      |
|   PDCA / レポート / 伴走ミーティング                          |
| - 強み: 早期立ち上げ、複数職種同時対応、現場巻き込み設計       |
| - オプション: 説明会・イベント、リファラル設計、ATS連携       |
| CTA: 主CTA（例: 今すぐ相談する）、必要ならサブCTAを併設        |
+--------------------------------------------------------------+
```

## 9. 今後決めたいこと（要フィードバック）
- フォーマット最終決定（JSON/YAML/frontmatter）。
- 埋め込み手段（iframe / script / Web Component）。
- トラッキングの実装先とイベント命名。
- デザインテーマ初期値（色/余白/角丸/影）。
- CTAリンク既定値とUTM付与方針。
