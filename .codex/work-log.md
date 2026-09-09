# Work Log

## 2026-03-07
- `.codex/` を初期作成。
- 作業ログの起点としてこのファイルを追加。
- `composer.json` の `require` 重複を解消して整合性を回復。
- `composer update --lock` を実行し、lock を最新化（結果として差分なし）。
- `composer validate --no-check-publish` で `./composer.json is valid` を確認。
- `composer.json` の修正後、Dockerなしでブラウザ確認を試行。
- Codex実行環境側で `php -S` を起動しても、ユーザー環境の `127.0.0.1` とは分離されるためアクセス不可だった。
- ユーザー端末で `php -S 127.0.0.1:8080 -t public` を実行して `public/index.php` の表示確認に成功。
- 不要なCodex側 `php -S` プロセスは停止済み。
- 未完了: 静的解析（`composer phpstan`）とコードスタイル確認（`composer codestyle`）は未実施。
- 次にやるとよいこと: Dockerなし運用手順として、ローカル起動手順と停止手順を README に追記する。
- プロジェクト実装を俯瞰し、改善候補を優先度付きで整理。
- 優先度高: `TableTrait` の caption 挿入ロジック不整合（`<table>` 文字列置換依存）を修正対象に設定。
- 優先度高: `SetfilesizeTrait` の URL→ローカルパス解決を厳密化（`parse_url` / `realpath` / 配下チェック）して安全性向上を提案。
- 優先度中: `FigcaptionTrait` 正規表現の堅牢化、a11y連携（`aria-describedby` など）の検討、型宣言と PSR-12 寄せ、最小回帰テスト追加を提案。
- 未完了: 上記提案の実装は未着手。
- 次にやるとよいこと: まず `TableTrait` と `SetfilesizeTrait` の2点を先行実装し、サンプル入力で回帰確認する。

## 2026-08-29

- `jidaikobo/log` の廃止準備として、未使用の開発依存から削除。
- ローカルのComposerロックと `vendor/` から `jidaikobo/log` と、それだけが
  要求していた `psr/log` を削除。
- Composer定義は正常。PHPStan level 5もエラーなし。
- `composer audit` でPHP_CodeSnifferのhigh勧告1件を確認。本作業では依存更新は
  未実施。
- 未完了: PHP_CodeSnifferのセキュリティ勧告への対応。
- 次にやるとよいこと: 勧告対応後に依存整理版をリリースする。

### Security dependency update

- PHP_CodeSnifferを3.13.6へ更新し、`composer.json` に修正版の下限を明記。
- `composer audit` は勧告0件、PHPStan level 5は正常。
- PHPCS全体実行では既存のコードスタイル違反を検出。
- 未完了: 既存のコードスタイル違反。
- 次にやるとよいこと: コードスタイル修正は挙動変更と分離して実施する。

## 2026-09-07

- `examples/index.php`、`examples/sample.md`、容量表示用テキスト、表示用SVGを
  追加し、外部依存なしで現在の `Jidaikobo\MarkdownExtra` の出力をブラウザ確認
  できるようにした。
- ライブラリ本体を変更せず、表、caption、figure、ローカルファイル容量、ルート
  相対URL補完と、URL・属性・コードフェンスの境界例を一画面にまとめた。
- PHP 8.3.6で構文エラーがないこと、`sample.md` を読み込めること、PHP組み込み
  Webサーバーでページ・Markdown・サンプルファイルがHTTP 200になることを確認。
- 期待どおり: 列・行の `scope`、通常のcaption、figure、txtの形式・容量、ルート
  相対URL補完、Markdown画像への容量非追記、複雑なURLとtitleの出力。
- 現状の問題: ブロック属性を直後に置くとcaption付き表全体が未変換、コード
  フェンス内のfigure記法も事前変換、リンク属性が欠落、SVGへの通常リンクには
  形式・容量が追記される。
- 未完了: 上記のライブラリ本体の問題修正と回帰テスト追加。
- 次にやるとよいこと: サンプルで再現した境界例をテストへ移し、まず表属性と
  figureのコードフェンス誤変換を、既存利用との互換性を確認しながら修正する。

### サンプルで確認した問題の修正

- 属性行を持つ表も解析し、`id`・`class`をtableへ付けたうえでcaptionを先頭に
  挿入するよう修正。caption挿入は属性のない `<table>` だけに依存しなくなった。
- figure変換をMarkdownのブロック処理へ移し、フェンス・インデントコードが保護
  された後に実行することで、コード例内のfigure記法の誤変換を防いだ。
- 独自リンク処理でもMarkdown Extraのリンク属性をHTMLへ引き継ぐよう修正。
- SVG、WebP、AVIFなど現行の画像拡張子を容量追記の対象外に追加。
- `tests/regression.php` とComposerの `test` スクリプトを追加し、上記4点に加えて
  列・行見出しのscope、通常のfigure、txtの容量表示を回帰確認できるようにした。
- 確認結果: Composer定義正常、回帰テスト成功、PHPStan level 5エラーなし、
  `git diff --check`正常、起動中の確認ページはHTTP 200。
- 未完了: URLからローカルパスへの変換の厳密化など、以前から記録されている
  セキュリティ強化は今回の4件とは分離して未着手。
- 次にやるとよいこと: ローカルパス解決の境界テストを先に追加し、`parse_url`と
  `realpath`による公開ディレクトリ配下チェックを実装する。

### 公開・リリース予定

- このパッケージはPackagistの
  `https://packagist.org/packages/jidaikobo/php-markdown` で公開している。
- 追加の修正をいくつか行った後、新しいバージョンタグを作成する予定。
- タグ作成前に、バージョン番号、差分、回帰テスト、静的解析、Composer定義、
  Packagistへの反映設定を確認する。
- ヘッダー行の1列目と本文行の1列目を `scope="row"` にする表をサンプルへ追加。
  行見出しは色だけに依存せず、背景色と太い左罫線で判別できるCSSを適用した。
- `league/commonmark` への段階的な移行方針を
  `.codex/commonmark-migration-roadmap.md` に記録した。
- `jidaikobo/php-markdown` のバージョン2として移行し、wrapperで主要APIと独自記法
  の互換維持を目指す。HTMLの完全一致、Michelfの継承関係、Michelf由来のpublic
  プロパティを直接操作する利用コードは互換性の対象外とする。
- 未完了: 現行1.xの安定化、互換fixtureの整備、league/commonmark版の試作。
- 次にやるとよいこと: caption内のインラインMarkdownを含むfigureの仕様と回帰
  テストを整備する。

### v1.0.9リリース準備

- figureの画像部分をMichelf本体のspan処理へ委譲し、画像のtitle、属性、括弧を
  含むURLに対応した。
- figcaptionの内容もspan処理へ通し、リンク、強調、インラインコードを入れ子に
  できるようにした。フェンス・インデントコードと、画像以外の文字を伴う行は
  figureへ変換しない回帰テストを追加した。
- ファイル容量取得時のURL判定をscheme・host・port・基底パスの比較へ変更し、
  `realpath`で公開ディレクトリ配下の通常ファイルだけを許可した。平文・URL
  エンコードされたtraversal、別host、protocol-relative URLのテストを追加した。
- READMEを現在の表、figure、リンク、サンプル、テスト手順に合わせて更新し、
  `CHANGELOG.md` にv1.0.9の変更内容を記録した。
- Composerのコードスタイルスクリプトを実在するPHPCSへ修正し、PSR-12設定と
  PHP 7.4以降のPHPCompatibility検査を整備した。
- `composer archive` の内容を確認し、混入していたローカルの `vendor/`、ログ、
  旧 `public/` などを配布アーカイブから除外する設定を追加した。
- コミット済みHEADから64KBの配布アーカイブを再生成し、隔離した `/tmp` へ展開。
  `composer install --no-dev` で依存が `michelf/php-markdown` だけになることと、
  table caption、行見出し、figure、figcaption内リンクの変換成功を確認した。
- 確認結果: 回帰テスト、PSR-12、PHPCompatibility、PHPStan level 5、Composer
  strict validate、Composer audit、`git diff --check`はすべて正常。
- 利用者によるブラウザでの最終目視で、レンダリング結果と複雑なfigure記法が
  問題ないことを確認した。
- v1.0.9タグ作成と、`main`およびタグのpushについて利用者の承認を得た。
- 未完了: v1.0.9タグ作成、push、Packagistへの反映確認。
- 次にやるとよいこと: 起動中のサンプルでfigureとリンク表示を目視し、問題が
  なければコミット後にv1.0.9タグを作成する。

### v1.0.9リリース完了

- 最終リリースコミット `27b93ac` にannotated tag `v1.0.9` を作成した。
- `main` と `v1.0.9` をGitHubの `origin` へpushし、リモートのブランチとタグが
  `27b93ac` を指すことを確認した。
- Packagistのライブメタデータで、`v1.0.9` が同じコミットを参照して公開された
  ことを確認した。
- v1.0.9についての未完了事項はなし。
- 次にやるとよいこと: 1.xは必要な保守に留め、CommonMark移行ロードマップに
  沿ってバージョン2の互換fixture整備から開始する。

### v2.0.0公開API方針

- `.codex/commonmark-migration-roadmap.md` に、v2.0.0の互換wrapperと新APIを
  併存させる方針を追記した。
- `Jidaikobo\MarkdownExtra`、`setTargetUrl()`、`setReplacePath()`、
  `defaultTransform()`、インスタンスの `transform()` は互換APIとして維持する。
- 新規コードには、immutableな `MarkdownOptions` とインスタンス単位の
  `MarkdownConverter::convert()` を推奨する。
- 新旧APIは同じ `league/commonmark` ベースの内部Converterを使用し、static設定と
  新APIインスタンスの状態が混ざらないことを回帰テストで保証する。
- 未完了: 実クラスの作成、v1互換fixture、API名とv2最低PHPバージョンの確定。
- 次にやるとよいこと: v1.0.9の公開APIと代表的な変換結果をfixtureとして固定し、
  `MarkdownOptions` と `MarkdownConverter` の最小インターフェースを試作する。

### v1保守ブランチとv2開発ブランチ

- `v1.0.9` (`27b93ac`) を起点に保守用の `1.x` ブランチを作成した。
- v2開発は既定の `main` で継続することとし、一時的な `2.x` ブランチは
  `main` と同じコミットであることを確認してローカルから削除した。
- `composer.json` のv2向け依存変更は、未コミットのまま `main` に引き継いでいる。
- 理由: `^1.0` 利用者向けの保守系列と、次期メジャー版の開発先を明確に
  分けつつ、将来の標準ブランチを `main` にするため。
- 未完了: `1.x` と `main` のリモート反映、v2実装と検証。
- 次にやるとよいこと: `main` で互換fixture、新API、互換wrapperを実装し、
  v2の完成確認後に必要なブランチをpushする。

### v2.0.0 League CommonMark版の初期実装

- 本番依存を `league/commonmark:^2.10` へ切り替え、Michelfは移行比較用の
  開発依存に移した。
- immutableな `MarkdownOptions` と、設定がインスタンス間で混ざらない
  `MarkdownConverter` を実装した。
- `Jidaikobo\MarkdownExtra` は旧static setter、`defaultTransform()`、インスタンスの
  `transform()` を保つ互換facadeに置き換えた。
- 表の `scope`、caption、figure/figcaption、URL補完、安全なローカルファイル
  容量表示を、League CommonMarkのASTノード操作と独自rendererで実装した。
- 旧正規表現実装のtraitを削除し、READMEとCHANGELOGをv2開発状況に合わせた。
- 確認結果: 回帰テスト、Composer strict validate、PSR-12、PHP 7.4互換検査、
  PHPStan level 5が成功。サンプルはPHP組み込みWebサーバーでHTTP 200と主要な
  caption、scope、figure、容量表示を確認した。
- 未完了: v1とv2の差分分類、専用移行ガイド、追加境界テスト、開発依存の
  Michelf削除判断、リモートブランチ反映。
- 次にやるとよいこと: v1.0.9の出力と比較し、差分を互換性方針に照らして
  回帰テストまたは移行ガイドに固定する。

### 互換APIとv2新APIのブラウザーサンプル

- `examples/index.php` は `Jidaikobo\MarkdownExtra` とstatic setterを使う互換APIの
  確認ページとして維持した。
- `examples/index-v2.php` を追加し、immutableな `MarkdownOptions` と
  `MarkdownConverter` を使うv2推奨APIを確認できるようにした。
- 両ページは同じ `sample.md` を変換し、現在のAPIと相互リンクを明示する。
- 回帰テストで両方のエントリーポイントを実際にincludeし、ページと独自記法の
  出力を確認するようにした。PHPCompatibilityの対象も `examples/` 全体に広げた。
- 未完了: 利用者による両ページの目視比較、コミット。
- 次にやるとよいこと: 両URLのHTTP 200と主要構造を検査し、ブラウザーで
  表示を比較する。

### v2.0.0文書とv1文書アーカイブ

- `README.md` をv2の現行文書として再構成し、推奨のインスタンスAPI、互換
  facade、独自記法、セキュリティ、両ブラウザーサンプル、バージョン保守方針を
  英語で記載した。
- `UPGRADING.md` を追加し、v1のまま動くv2 API、推奨APIへの段階的移行、
  Michelf継承・publicプロパティ・HTML完全一致を保証しない点を分けて説明した。
- `docs/README-v1.md` に `v1.0.9` タグのREADME本文を保存し、更新対象では
  ない凍結スナップショットであることと、`1.x`・v2 README・移行ガイドへの
  リンクを冒頭に明記した。
- 理由: v1の利用方法を消さずに参照可能にしつつ、v1とv2のREADMEを二重に
  メンテナンスし続ける負担を避けるため。
- 未完了: 文書内容の利用者確認、コミット。
- 次にやるとよいこと: v2の実装と文書の名称・セキュリティ説明を突き合わせ、
  リリース時にREADMEの「upcoming」表記を外す。

### v2.0.0リリース準備

- 利用者の確認により、当面の最低PHP要件は7.4を維持することにした。
- `league/commonmark 2.10.0` 自身のComposer要件が `^7.4 || ^8.0` であることを
  確認した。ただしPHP 7.4実機ではなく、PHPCompatibilityによる静的検査である。
- `CHANGELOG.md` のv2.0.0を2026-09-07付けに確定し、READMEの未公開表記を
  リリース用に更新した。
- 未完了: 最終検証、コミット、`v2.0.0` タグ作成、`1.x`・`main`・タグのpush。
- 次にやるとよいこと: テストと配布アーカイブを検証し、成功時のみタグとpushを
  実行する。

### v2.0.0最終検証

- PHP構文、回帰テスト、Composer strict validate、PSR-12、PHPCompatibility 7.4以降、
  PHPStan level 5、Composer audit、`git diff --check` がすべて成功した。
- 互換APIとv2新APIの両exampleがHTTP 200となり、両者の `<main>` 出力の
  SHA-256が一致することを確認した。
- コミット `152a570` から37KBのzipを作成し、空の旧ディレクトリを除いた
  28エントリの配布内容を確認した。
- 配布zipを `/tmp` に隔離展開し、`composer install --no-dev` 後の直接本番依存が
  `league/commonmark 2.10.0` だけであること、Michelfがインストールされないこと、
  同梱回帰テストの成功を確認した。
- 未完了: `v2.0.0` タグ作成と、`1.x`・`main`・タグのpush。
- 次にやるとよいこと: この検証記録をコミットし、そのコミットへannotated tagを
  作成してリモート反映を確認する。

### v2.0.0リリース（後に取り下げ）

- この節は2026-09-07の公開時点の記録。安定版公開は尚早と判断し、2026-09-08に
  `v2.0.0` を取り下げ、`v2.0.0-beta.1` へ訂正することにした。

- 最終検証コミット `762bee0` にannotated tag `v2.0.0` を作成した。
- `1.x`、`main`、`v2.0.0` をGitHubの `origin` へpushした。
- リモートで `1.x` が `27b93ac` (`v1.0.9`)、`main` と `v2.0.0` のpeeled tagが
  `762bee0` を指すことを確認した。
- Packagistの公開メタデータで `v2.0.0` が `762bee0` として反映済みであることを
  確認した。
- v2.0.0リリースの未完了事項はなし。
- 次にやるとよいこと: 実利用で見つかった差分は、v1保守とv2改良をそれぞれ
  `1.x` と `main` に分けて対応する。

### v2.0.0-beta.1への訂正

- 利用者の判断により、v2は安定版ではなくbetaとして検証を継続する。
- READMEと移行ガイドの導入コマンドを `2.0.0-beta.1` の明示指定に変更し、
  stableの `^2.0` への移行は正式版公開後と明記した。
- `CHANGELOG.md` の見出しを `2.0.0-beta.1 - 2026-09-08` へ訂正した。
- 未完了: 再検証、訂正コミット、公開済み `v2.0.0` タグのローカル・リモート
  削除、`v2.0.0-beta.1` の作成とpush、Packagist反映確認。
- 次にやるとよいこと: 旧タグ削除前に文書・テスト・依存を再検証し、成功時のみ
  タグの訂正を行う。

### v2.0.0-beta.1訂正完了

- 回帰テスト、Composer strict validate・監査、PSR-12、PHPCompatibility 7.4以降、
  PHPStan level 5、`git diff --check` が成功した。
- 訂正コミット `2cbc2c9` へannotated tag `v2.0.0-beta.1` を作成し、`main` と
  新タグをGitHubへpushした。
- 新タグがリモートで `2cbc2c9` を指すことを確認した後、旧 `v2.0.0` タグを
  ローカルとGitHubの両方から削除した。コミットとブランチは削除していない。
- Packagistの公開メタデータで、`v2.0.0` が消え、`v2.0.0-beta.1` が `2cbc2c9`
  として掲載されたことを確認した。
- beta.1への訂正に関する未完了事項はなし。
- 次にやるとよいこと: beta期間はAPIと既存記法の実利用を検証し、変更は
  CHANGELOGと移行ガイドに記録する。

### League CommonMark標準Extensionのみのベースライン

- `examples/index-commonmark.php` を追加し、同じ `sample.md` をLeague公式の
  `CommonMarkCoreExtension`、`TableExtension`、`AttributesExtension` だけで変換するようにした。
- Jidaikoboの独自Extensionを外したとき、行見出しの末尾 `:`、表captionの先頭 `:`、
  画像直後の強調caption、ルート相対リンクが欠落せず、通常のMarkdownとして
  読めることを確認できるページとした。
- 既存の互換API・v2新APIページと相互リンクし、READMEと移行ガイドにも
  3つ目の比較ページとして記載した。
- 回帰テストでベースラインページをincludeし、独自変換が行われないことと、
  独自記法の内容が残ることを検査するようにした。
- 理由: 「プレーンテキストの状態でも読みやすい」という方針を、独自Extensionによる
  最終HTMLだけでなく、独自処理を外した出力からも確認できるようにするため。
- 未完了: PHP組み込みWebサーバーでのHTTP確認と利用者の目視確認、コミット。
- 次にやるとよいこと: 3ページを比較し、独自記法が変換前・ベースライン変換後の
  どちらでも理解可能かを確認する。

### 前置table caption記法と機能単位Extension

- 強調だけの段落が空行なしでtableの直前にある場合、その子ASTノードを
  `TableCaption` へ移す新しい記法を追加した。リンクやコードなどの入れ子も維持する。
- 段落の終了行とtableの開始行が連続している場合だけをcaptionとし、空行があれば
  通常の強調段落として残す。正規表現の先読みではなく、Tableの直前の兄弟ノードと
  ASTの行番号で判定する。
- v1の `|: caption` 記法は下位互換のため維持し、新規文書には前置記法を
  推奨する。サンプルでは新旧の両方を確認できる。
- 一つの `JidaikoboExtension` / `MarkdownProcessor` に集約していた処理を、
  `AccessibleTableExtension`、`FigureExtension`、`LinkEnhancementExtension` に分割した。
- 各機能ディレクトリ内にExtension、Processor、必要なNodeとRendererを配置し、
  `MarkdownConverter` が機能単位でEnvironmentへ登録するLeague CommonMarkの構成に寄せた。
- README、CHANGELOG、移行ガイド、移行ロードマップを新記法と機能分割に合わせて
  更新した。
- 確認結果: 回帰テスト、Composer strict validate、PSR-12、PHPCompatibility 7.4以降、
  PHPStan level 5、`git diff --check` が成功した。3ページはすべてHTTP 200で、互換APIと
  v2 APIの `<main>` 出力が一致した。ベースラインでは前置captionが強調段落と表になる。
- 未完了: 利用者の目視確認。実装と自動検査はコミット可能な状態。
- 次にやるとよいこと: 新captionのインライン構造、空行による非変換、旧記法の維持を
  自動テストとブラウザーで確認する。

### Pico CSS Classless比較ページ

- `examples/index-pico.php` を追加し、v2推奨APIで同じ `sample.md` を変換した結果に
  Pico CSS 2.1.1のClassless版だけを適用する比較ページを用意した。
- 以前のサンプル要件どおり外部CDNなしで確認できるよう、公式配布CSSとMIT
  Licenseを `examples/assets/vendor/pico/` にローカル配置し、バージョン、取得元、
  SHA-256を記録した。独自の表・figure用CSSは加えず、Pico本来の表示を確認する。
- 既存3ページからPico比較ページへ移動できるリンクを追加し、READMEと回帰テストも
  4ページ構成に更新した。
- PHPCompatibilityがローカル配置したminified CSSを誤って検査しないよう、対象拡張子を
  `php` に限定した。
- 確認結果: 4ページのPHP構文、回帰テスト、PSR-12、PHP 7.4互換検査、PHPStan
  level 5、Composer strict validate・audit、`git diff --check`が成功した。Picoページと
  ローカルCSSはいずれも組み込みWebサーバーでHTTP 200となり、CSSのSHA-256も一致した。
- 利用者がPicoの表を含む外観を目視し、比較候補として問題ないことを確認した。
- 未完了: Pico用の最小追加CSSを正式に用意するかどうかの判断。
- 次にやるとよいこと: Pico本来の表示を確認し、必要ならアクセシビリティ上必要な
  最小追加CSSだけを別途検討する。

### Bootstrap 5比較ページ

- `examples/index-bootstrap.php` を追加し、同じ `sample.md` にBootstrap 5.3.8を
  適用してPico Classless版と比較できるようにした。
- デモ専用の `examples/Support/Bootstrap5Extension.php` などを用意し、HTML文字列の
  置換ではなく解析後のASTに、table・figure・画像・figcaption用のBootstrap classを
  付与する構成にした。公開APIとしてのBootstrap対応はまだ定義していない。
- 表には基本classに加えてstriped・bordered・vertical alignmentを、figureには
  Bootstrap公式のfigure・responsive image・caption classを付与した。Markdown側の
  既存classが失われないことも回帰テストへ追加した。
- 外部CDNなしで表示できるよう公式CSSとMIT Licenseをローカル配置し、取得元、
  バージョン、SHA-256を記録した。Bootstrap JavaScriptは使用しない。
- PSR-12設定を `examples/index.php` だけでなく `examples/` 内のPHP全体へ広げ、
  vendor CSSを対象外にするため検査拡張子を `php` に限定した。
- 確認結果: PHP構文、回帰テスト、PSR-12、PHP 7.4互換検査、PHPStan level 5、
  Composer strict validate・audit、`git diff --check`が成功した。Bootstrapページと
  ローカルCSSはいずれもHTTP 200で、CSSのSHA-256と主要なclass・構造も一致した。
- 利用者がPico版とBootstrap版の外観を目視し、両方の方向性に魅力があることを
  確認した。
- 未完了: 公開APIとしてレンダリングプロファイルを提供するかどうかの判断。
- 次にやるとよいこと: Pico版とBootstrap版を見比べ、公開APIとしてレンダリング
  プロファイルを持つ価値があるか判断する。

### note・aside・detailsコンテナー

- `:::`フェンス内を通常のCommonMark ASTとして解析する、機能単位の
  `ContainerExtension`を追加した。
- `note`は`info`・`warn`・`alert`の固定表示種別に対応し、いずれも静的文書の
  `div[role="note"]`として出力する。タイトル指定時は見えるラベルと
  `aria-labelledby`を生成し、`alert`種別をARIA live regionにはしない。
- `aside`はネイティブの`aside`、`details`は`summary`を持つネイティブの
  `details`として出力し、内部の段落・リスト・リンク・コードブロックを維持する。
- サンプル、README、CHANGELOG、回帰テストを新記法に合わせて更新し、通常版には
  ラベルと太い開始罫線を併用するCSS、Bootstrap版には既存alert classとの対応を追加した。
- 理由: 視覚的な枠だけでなく、補足・傍系情報・開閉情報という意味をHTMLと
  支援技術へ伝えつつ、League CommonMarkのAST構造を壊さないため。
- 回帰テスト、PHPStan level 5、PSR-12、PHP 7.4互換検査、Composer strict validate・
  audit、`git diff --check`が成功した。5つのサンプルページはすべてHTTP 200で、
  新しいnote・aside・details構造と標準Extensionでの記法残存を確認した。
- 未完了: 利用者によるブラウザー目視と、スクリーンリーダーでのラベル読み上げ確認。
- 次にやるとよいこと: detailsのキーボード操作と各表示プロファイルの外観を確認し、
  noteラベルが環境によって不自然に重複して読まれないか実機で確認する。

### 英日Markdownチートシートと文書内ナビゲーション

- `docs/cheatsheet.md`と`docs/cheatsheet-ja.md`を追加し、基本Markdown、League標準
  Extension、Jidaikobo独自拡張について、記法のコード例と実際に変換される例を併記した。
- League公式の`HeadingPermalinkExtension`を全見出しで有効にし、
  `TableOfContentsExtension`は`[TOC]`プレースホルダーを書いた位置だけに目次を生成する
  構成にした。目次は見出しレベル2〜4、最大100項目に制限した。
- 理由: 既存文書へ目次を強制挿入せず、必要な文書だけが明示的に文書内ナビゲーションを
  持てるようにし、過大な目次生成も制限するため。
- PHPを実行せず閲覧できるPico CSS版とBootstrap版を英日それぞれ生成した。
  Markdown原稿を正本とし、`scripts/build-cheatsheets.php`とComposerスクリプトで
  4つの静的HTMLを再生成・更新漏れ検査できる構成にした。
- League標準Extensionのみの比較ページにもHeading PermalinkとTable of Contentsを追加し、
  Jidaikobo独自拡張を使わない場合との比較条件を維持した。
- PHP構文、回帰テスト、PHPStan level 5、PSR-12、PHP 7.4互換検査、Composer strict
  validate、`git diff --check`が成功した。既存5ページと4つの静的チートシート、画像は
  HTTP 200で、日本語slug、目次、note・details、ファイル容量の出力を確認した。
- 未完了: 利用者による英日チートシートの内容とブラウザー表示の目視確認。
- 次にやるとよいこと: 項目の過不足、用語、見出しパーマリンクの外観を確認し、必要なら
  正式リリース前に文言と目次範囲を調整する。

### チートシートの文言と表示責任の整理

- 英日原稿から、運用側で扱う生HTMLとサニタイズの説明を削除した。
- 独自拡張の見出しを、提供元が明確な `jidaikobo/php-markdown` 表記へ変更した。
- 共通チートシートCSSからnote・aside・detailsの枠、色、余白を削除した。
  noteはclassと意味構造だけをパッケージが提供し、外観は利用側のCSSに委ねることを
  英日原稿へ明記した。detailsはネイティブの開閉動作を使い、外観はブラウザーや
  読み込んだCSSに依存することも記載した。
- 理由: パッケージ本体の意味的なHTML出力と、アプリケーションごとの表示設計を
  混同しないため。Bootstrap版は表示比較用なのでBootstrap classの付与を維持する。
- 未完了: 再生成したPico CSS版とBootstrap版の利用者による目視確認。
- 次にやるとよいこと: Picoによるネイティブdetailsの装飾と、装飾されないnoteの
  バランスがチートシートとして分かりやすいか確認する。

### Bootstrapチートシートのコード例とnote余白

- Bootstrap版の `pre` に限定して、Bootstrapの変数を使った背景色、padding、角丸、
  monospaceフォントを指定し、Markdown記法の範囲を視覚的に判別できるようにした。
- note内の最後の子要素の下マージンをなくし、Bootstrapのpaddingと段落marginが
  重なって下側だけ広く見える状態を調整した。
- 理由: Pico版と同様に記法とレンダリング結果を見分けやすくしつつ、note自体の
  paddingや意味構造は変えないため。
- 未完了: 更新後のBootstrap版について利用者による目視確認。
- 次にやるとよいこと: コード例のコントラストと、note上下の余白の釣り合いを確認する。

### Bootstrap note内リンクのコントラスト対応

- Bootstrap表示用Extensionで、note内に入れ子になったリンクへBootstrap標準の
  `alert-link` classを付与するようにした。パッケージ本体のHTML出力には影響しない。
- info・warn・alertのサンプルすべてにリンクを置き、3種の背景色で表示とclass付与を
  検査できるようにした。
- 理由: 通常のBootstrapリンク色はinfo背景上でWCAG AAの通常文字に必要な4.5:1を
  下回るため。alert固有の文字色をリンクにも使い、下線と太字を維持する。
- 未完了: 更新後の3種類のnoteについて利用者による目視確認。
- 次にやるとよいこと: キーボードフォーカス時を含め、リンクの識別性をブラウザーで
  確認する。

### v2.0.0-beta.2リリース準備

- READMEと移行ガイドの導入バージョンを `2.0.0-beta.2` へ更新した。
- CHANGELOGのnote・aside・details、見出しパーマリンク、目次、静的チートシート、
  Bootstrap表示対応を2026-09-08付けの `2.0.0-beta.2` として確定した。
- 理由: beta.1以降の新しい記法と文書・表示サンプルを、安定版前に評価できる
  プレリリースとして明確に区切るため。
- PHP構文、回帰テスト、静的HTML同期、PSR-12、PHP 7.4互換検査、PHPStan level 5、
  Composer strict validate・audit、`git diff --check`が成功した。
- 未完了: リリースコミット、annotated tag作成、mainとタグのpush、Packagist反映確認。
- 次にやること: 検証済みの変更をコミットし、配布アーカイブ確認後にタグ付けする。

### v2.0.0-beta.2リリース完了

- リリースコミット `8b39aab` にannotated tag `v2.0.0-beta.2` を作成し、`main`と
  タグをGitHubの `origin` へpushした。
- Composer配布アーカイブを隔離展開し、`--no-dev`で直接本番依存が
  `league/commonmark 2.10.1`だけになることと、互換APIによるnote変換を確認した。
- リモートのtagが `8b39aab` を指すこと、およびPackagistの `v2.0.0-beta.2` が
  同じsource commitで公開されたことを確認した。
- beta.2リリースに関する未完了事項はなし。
- 次にやるとよいこと: 実利用側でbeta.2を評価し、安定版までに必要な記法や
  アクセシビリティ上の調整を整理する。

### beta.2後の監査指摘対応

- 推奨APIの生HTML既定値を`escape`へ変更し、`withHtmlInput()`で`escape`・`strip`・
  `allow`を変換器ごとに選べるようにした。互換APIはv1互換のため`allow`を明示する。
- `withLeagueExtension()`と`withLeagueConfiguration()`を追加し、共通のEnvironment
  FactoryからJidaikobo拡張と利用側のLeague拡張・設定を組み立てるよう整理した。
  設定内の連想配列は再帰マージし、allowlist等のリストは丸ごと置換する。
- 旧caption行は本文の最終行かつ第2セル以降が空の場合だけ認識し、先行captionが
  ある場合はコロンを変更しないようにした。行見出しの末尾判定も、AST上で実際に
  最後となるインライン内容だけを辿るようにした。
- 見出しパーマリンクを通常のキーボードフォーカス対象とし、見出し本文を
  `aria-label`に設定した。フラグメントIDは見出し要素自体へ付ける。
- note・asideは生成IDと`aria-labelledby`を使わず、見えるタイトルと同文の
  `aria-label`で命名し、別々に変換したHTML断片を結合してもIDが衝突しないようにした。
- 拡張子のないローカルファイルは形式・容量を追記せず、通常リンクのままとした。
- v2で未使用だった`michelf/php-markdown`を開発依存とロック・vendorから削除した。
- README、移行ガイド、CHANGELOG、英日チートシートを更新し、4つの静的HTMLを再生成した。
- 理由: 信頼できないMarkdownに対する安全な既定値を提供し、利用側の拡張余地を残しつつ、
  誤変換、アクセシビリティ上の操作不能、生成ID衝突をvendor改変なしで解消するため。
- 回帰テスト、静的HTML同期、PSR-12、PHP 7.4互換検査、PHPStan level 5、Composer
  strict validate・audit、`git diff --check`が成功した。5つの動的サンプルと4つの
  静的チートシートはPHP組み込みサーバーでHTTP 200を確認し、サーバーは終了した。
- 推奨APIの`baseUrl`は空文字またはuserinfo・query・fragmentを持たない絶対HTTP(S)
  URLに限定した。`documentRoot`は空文字または既存の絶対ディレクトリに限定し、
  `realpath()`後にファイルシステムルートとなる値を拒否する。互換APIは専用の内部生成
  経路を使い、従来の設定値で新たな例外を投げない。
- 未完了: 利用者によるブラウザーとキーボードでのパーマリンク確認。
- 次にやるとよいこと: 実案件の環境変数から渡されるbase URLとdocument rootが新しい
  条件を満たすことを確認し、必要なら移行時のエラーハンドリング例を追加する。
