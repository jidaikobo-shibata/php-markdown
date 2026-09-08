<?php

declare(strict_types=1);

use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;

require __DIR__ . '/../vendor/autoload.php';

$samplePath = __DIR__ . '/sample.md';
$markdown = file_get_contents($samplePath);

if ($markdown === false) {
    http_response_code(500);
    $renderedHtml = '<p role="alert">sample.md を読み込めませんでした。</p>';
} else {
    $serverPort = filter_var(
        $_SERVER['SERVER_PORT'] ?? 8000,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 65535]]
    );

    if ($serverPort === false) {
        $serverPort = 8000;
    }

    $options = MarkdownOptions::defaults()
        ->withBaseUrl('http://127.0.0.1:' . $serverPort)
        ->withDocumentRoot(__DIR__);

    $converter = new MarkdownConverter($options);
    $renderedHtml = $converter->convert($markdown);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pico CSS Classless - Jidaikobo Markdown 表示確認</title>
    <link rel="stylesheet" href="assets/vendor/pico/pico.classless.min.css">
</head>
<body>
    <header>
        <h1>Pico CSS Classless表示確認</h1>
        <nav aria-label="サンプル変換方式と外観の切り替え">
            <a href="index.php">互換API</a>
            <span aria-hidden="true"> / </span>
            <a href="index-v2.php">バージョン2新API</a>
            <span aria-hidden="true"> / </span>
            <a href="index-commonmark.php">League標準Extensionのみ</a>
            <span aria-hidden="true"> / </span>
            <strong aria-current="page">Pico CSS Classless</strong>
            <span aria-hidden="true"> / </span>
            <a href="index-bootstrap.php">Bootstrap 5</a>
        </nav>
        <p>
            バージョン2新APIの変換結果に、ローカル配置した
            Pico CSS 2.1.1のClassless版だけを適用しています。
            独自の表・figure用CSSは追加していません。
        </p>
        <p>以下は <code>examples/sample.md</code> の現在の変換結果です。</p>
    </header>
    <main>
        <?= $renderedHtml ?>
    </main>
    <footer>
        <p>
            <small>
                Pico CSS 2.1.1はMIT Licenseで提供されています。
                ライセンスは
                <a href="assets/vendor/pico/LICENSE.md">ローカルのライセンス文書</a>
                で確認できます。
            </small>
        </p>
    </footer>
</body>
</html>
