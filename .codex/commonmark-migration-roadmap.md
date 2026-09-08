# league/commonmark 移行ロードマップ

## 目的

現在の `michelf/php-markdown` ベースの実装をすぐに置き換えるのではなく、既存の
独自記法を安定させたうえで、構造的な拡張に適した `league/commonmark` ベースの
実装へ段階的に移行する。

移行版はComposerパッケージ `jidaikobo/php-markdown` のバージョン2として公開し、
Packagistのパッケージ名と既存の主要な利用方法は可能な限り維持する。

## 維持する機能

- 表セル末尾の「:」による `<th scope="row">`
- 通常の列見出しへの `scope="col"`
- 「:」から始まる表の行による `<caption>`
- Markdown画像直後のcaptionによるfigure/figcaption
- ローカルファイルへのリンクへの拡張子・容量追記
- 「/」から始まるURLの補完
- 現在READMEなどで案内している主要な変換API

figure/figcaptionでは、captionを単なる文字列ではなくASTの子要素として扱い、
リンク、強調、コードなどのインラインMarkdownを入れ子にできるようにする。

## 将来追加する構造

- 汎用の枠囲みは、原則としてclass付きの `div` として表現する。
- 本文から補足的に独立する内容には `aside` を使用する。
- `section` は単なる装飾目的では使わず、見出しまたはアクセシブルな名前を持つ
  文書区分に使用する。
- センタリングと右寄せはHTML要素の意味ではなく、許可されたclassとCSSで表現
  する。右寄せには物理方向の `right` だけでなく論理方向の `end` も検討する。
- 複数段落、見出し、リストなどを安全に内包できるコンテナ記法を設計する。
- Markdownから指定できる要素、class、属性は許可リストで制限する。

## 互換性方針

`Jidaikobo\MarkdownExtra` を外部向けwrapperまたはfacadeとして維持し、内部の
変換エンジンを直接利用者へ露出しない構成を目指す。

可能な限り維持するもの:

- Composerパッケージ名 `jidaikobo/php-markdown`
- 名前空間とクラス名 `Jidaikobo\MarkdownExtra`
- `MarkdownExtra::defaultTransform()`
- `new MarkdownExtra()->transform()`
- `setTargetUrl()` と `setReplacePath()`
- 既存の独自Markdown記法と、その意味および主要なHTML構造

互換性の対象外として、次の3点は明示的に断念する:

- 生成HTMLを一字一句同じにすること
- `Michelf\MarkdownExtra` の継承関係
- Michelf由来のpublicプロパティを直接操作する利用コード

HTMLの完全一致は保証しないが、アクセシビリティ上の意味、リンク先、見出し・表・
figureなどの文書構造が維持されることを重視する。変更点はバージョン2の移行文書に
明記する。

## v2.0.0の公開API方針

v2.0.0では、既存コード向けの互換wrapperと、staticな共有状態を使わない新しい
オブジェクトAPIを併存させる。両方のAPIは、同じ `league/commonmark` ベースの
内部Converterと独自Extensionを使用する。

```text
Jidaikobo\MarkdownExtra（互換wrapper）
                    │
                    ├── setTargetUrl()
                    ├── setReplacePath()
                    ├── defaultTransform()
                    └── transform()
                            │
                            ▼
Jidaikobo\Markdown\MarkdownConverter（新API）
                            │
                            ▼
league/commonmark + 独自Extension
```

### 互換wrapper

既存のクラス名とstatic setterを使う次の書き方を、v2.0.0でも許容する。

```php
use Jidaikobo\MarkdownExtra;

MarkdownExtra::setTargetUrl(
    'http://127.0.0.1:' . $serverPort
);
MarkdownExtra::setReplacePath(__DIR__);

$renderedHtml = MarkdownExtra::defaultTransform($markdown);
```

インスタンスを作成する既存の書き方も維持する。

```php
use Jidaikobo\MarkdownExtra;

$parser = new MarkdownExtra();
$renderedHtml = $parser->transform($markdown);
```

互換wrapperは、保持している従来設定から新しいOptionsを生成し、内部Converterへ
処理を委譲する。Michelfのパーサー実装をwrapper内で再現しない。

### 新しい推奨API

新規コードでは、変換器ごとに設定を保持するオブジェクトAPIを推奨する。

```php
use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;

$options = MarkdownOptions::defaults()
    ->withBaseUrl('http://127.0.0.1:' . $serverPort)
    ->withDocumentRoot(__DIR__);

$converter = new MarkdownConverter($options);
$renderedHtml = $converter->convert($markdown);
```

`MarkdownOptions` はimmutableな値オブジェクトとし、`with...()` は元のインスタンスを
変更せず、新しいOptionsを返す。これにより、複数サイト、テスト、常駐プロセスで
設定が混ざることを防ぐ。

新旧APIの名称は次のように対応させる。

| 互換API | 新API |
|---|---|
| `setTargetUrl()` | `withBaseUrl()` |
| `setReplacePath()` | `withDocumentRoot()` |
| `defaultTransform()` / `transform()` | `convert()` |
| staticな共有設定 | Converterごとのimmutableな設定 |

新APIでは、意味が分かりにくい `replacePath` という名称を引き継がず、公開URLに
対応するローカルの公開ディレクトリであることを示す `documentRoot` を使用する。

### API互換テスト

v2.0.0では、少なくとも次を自動テストで保証する。

- static setterを使う既存の呼び出しがそのまま動作する。
- `MarkdownExtra::defaultTransform()` がHTML文字列を返す。
- `new MarkdownExtra()->transform()` が動作する。
- 新旧APIが同じ意味構造を出力する。
- table、caption、figure、ファイル容量、URL補完が両方のAPIで動作する。
- 複数の新APIインスタンス間で設定が混ざらない。
- static互換設定が、新APIで生成済みのConverterへ影響しない。

新旧APIで生成HTMLを一字一句一致させることは要件としない。要素、属性、親子関係、
アクセシビリティ上の意味を比較する。

## 内部の想定アーキテクチャ

```text
Jidaikobo\MarkdownExtra（互換wrapper）
        │
        ├── 互換用の公開メソッドと設定
        │
        └── MarkdownConverter（新APIと共通）
                    │
                    └── league/commonmark版（バージョン2の既定）
```

`league/commonmark` 側では、独自のExtensionとしてパーサー、ASTノード、イベント
リスナー、レンダラーをまとめる。文字列置換によるHTML後処理は可能な限り避ける。

独自実装は一つの大きなProcessorに集約せず、利用者から見て独立した機能単位の
Extensionに分ける。当面は次の3単位とする。

- `AccessibleTableExtension`: 列・行見出しと新旧table caption記法
- `FigureExtension`: figure/figcaption記法
- `LinkEnhancementExtension`: ルート相対URL補完とローカルファイル情報

公式に存在する機能は再実装せず、League CommonMark付属のExtensionを必要に応じて
登録する。外部向けAPIは引き続き `MarkdownConverter` と `MarkdownOptions` にまとめる。

Michelf版は、開発中の出力比較および互換fixture作成には使用するが、v2.0.0の
本番変換経路には残さない方針を基本とする。移行調査で必要性が判明した場合のみ、
期間を限定した併存を再検討する。

## 段階的な移行

### 第1段階: 現行1.xの安定化

1. figure/figcaptionを現行エンジン上で改善する。
2. URLからローカルパスへの解決を `parse_url` と `realpath` で厳密化する。
3. 既存記法、境界例、生成される主要なHTML構造の回帰テストを増やす。
4. 現行の修正版を1.xの新しいタグとして公開する。

### 第2段階: 互換仕様の固定

1. wrapperとして維持する公開APIを一覧化する。
2. 現行Markdown入力と期待する意味構造をfixtureとして保存する。
3. HTML完全一致ではなく、要素、属性、親子関係を検証するテストを用意する。
4. 利用側でMichelfの継承・publicプロパティに依存している箇所を調査する。

### 第3段階: league/commonmark版の試作

1. `league/commonmark` のバージョン2系を開発依存として試験導入する。
2. CommonMark本体、Table、Attributesなど必要最小限のExtensionを構成する。
3. 行見出し、table caption、figure/figcaptionをAST操作として実装する。
4. Linkノードを使ってファイル容量表示とURL補完を実装する。
5. HTML入力、危険なURL、最大入れ子数、属性の許可リストを安全側に設定する。

### 第4段階: 比較と移行

1. 同じfixtureをMichelf版とleague/commonmark版へ入力する。
2. 出力差分を「意図した変更」「許容可能」「要修正」に分類する。
3. wrapperを通した既存の主要APIと独自記法の互換性を確認する。
4. バージョン2への移行手順と、互換性を断念する3項目をREADMEまたは専用文書へ
   明記する。

### 第5段階: バージョン2の公開

1. Composer依存を本番用の `league/commonmark` バージョン2系へ切り替える。
2. 回帰テスト、静的解析、コードスタイル、依存関係監査を実行する。
3. サンプルをブラウザで確認し、アクセシビリティとセキュリティを確認する。
4. `jidaikobo/php-markdown` 2.0.0のタグを作成する。
5. Packagistへの反映とインストール確認を行う。

## 未決定事項

- 枠囲み、aside、sectionに使用するMarkdown記法
- 配置用classの名称と、物理方向・論理方向の扱い
- 1.xと2.xのConverterを同一リリース内で併存させる期間
- バージョン2で保証するPHPの最低バージョン
- `league/commonmark` の依存バージョン制約
- HTML差分を利用者へ示す移行ガイドの形式

## 次に行うこと

現行1.xのfigure/figcaptionについて、caption内のリンク、強調、コード、複雑なURL、
画像属性を含む入力をサンプルと回帰テストへ追加し、現行エンジンでどこまで安全に
改善するかを決める。
