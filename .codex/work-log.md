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
