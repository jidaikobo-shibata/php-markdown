<?php

declare(strict_types=1);

use Jidaikobo\Examples\Support\Bootstrap5ExampleConverter;
use Jidaikobo\Markdown\MarkdownOptions;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Support/Bootstrap5ClassProcessor.php';
require __DIR__ . '/Support/Bootstrap5FigcaptionRenderer.php';
require __DIR__ . '/Support/Bootstrap5Extension.php';
require __DIR__ . '/Support/Bootstrap5ExampleConverter.php';

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

    $converter = Bootstrap5ExampleConverter::create($options);
    $renderedHtml = $converter->convert($markdown)->getContent();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap 5 - Jidaikobo Markdown 表示確認</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
</head>
<body class="bg-body-tertiary">
    <header class="container py-4">
        <h1 class="display-5">Bootstrap 5表示確認</h1>
        <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="サンプル変換方式と外観の切り替え">
            <a class="btn btn-outline-secondary btn-sm" href="index.php">互換API</a>
            <a class="btn btn-outline-secondary btn-sm" href="index-v2.php">バージョン2新API</a>
            <a class="btn btn-outline-secondary btn-sm" href="index-commonmark.php">League標準Extensionのみ</a>
            <a class="btn btn-outline-secondary btn-sm" href="index-pico.php">Pico CSS Classless</a>
            <strong class="btn btn-primary btn-sm" aria-current="page">Bootstrap 5</strong>
            <a class="btn btn-outline-secondary btn-sm" href="cheatsheet-bootstrap-ja.html">チートシート</a>
        </nav>
        <div class="alert alert-info mb-0">
            <p class="mb-1">
                バージョン2の独自Extensionにデモ専用のBootstrap class付与Extensionを加え、
                ローカル配置したBootstrap 5.3.8のCSSで表示しています。
            </p>
            <p class="mb-0">
                表には <code>table table-striped table-bordered align-middle</code>、figureには
                Bootstrap公式の基本classを付与しています。JavaScriptと外部CDNは使用していません。
            </p>
        </div>
    </header>
    <main class="container bg-body rounded shadow-sm p-3 p-md-5 mb-4">
        <?= $renderedHtml ?>
    </main>
    <footer class="container pb-4 text-body-secondary">
        <p class="mb-0">
            <small>
                Bootstrap 5.3.8はMIT Licenseで提供されています。
                ライセンスは
                <a href="assets/vendor/bootstrap/LICENSE">ローカルのライセンス文書</a>
                で確認できます。
            </small>
        </p>
    </footer>
</body>
</html>
