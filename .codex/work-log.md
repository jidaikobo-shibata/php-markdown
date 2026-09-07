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
- 確認結果: 回帰テスト、PSR-12、PHPCompatibility、PHPStan level 5、Composer
  strict validate、Composer audit、`git diff --check`はすべて正常。
- 未完了: 利用者によるブラウザでの最終目視、v1.0.9タグ作成、push、Packagist
  への反映確認。
- 次にやるとよいこと: 起動中のサンプルでfigureとリンク表示を目視し、問題が
  なければコミット後にv1.0.9タグを作成する。
