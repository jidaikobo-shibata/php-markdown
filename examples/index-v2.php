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
    <title>バージョン2新API - Jidaikobo Markdown 表示確認</title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: system-ui, sans-serif;
            line-height: 1.7;
        }

        body {
            max-width: 64rem;
            margin: 0 auto;
            padding: 1rem;
        }

        a {
            overflow-wrap: anywhere;
        }

        table {
            width: 100%;
            margin-block: 1.5rem;
            border-collapse: collapse;
        }

        caption {
            padding: 0.5rem;
            font-weight: 700;
            text-align: start;
        }

        th,
        td {
            padding: 0.4rem 0.7rem;
            border: 1px solid currentColor;
            text-align: start;
        }

        th[scope="row"] {
            color: #1a1a1a;
            background-color: #fff2c2;
            border-inline-start: 0.4rem solid #8a4b00;
            font-weight: 700;
        }

        @media (prefers-color-scheme: dark) {
            th[scope="row"] {
                color: #fff;
                background-color: #453800;
                border-inline-start-color: #ffcc66;
            }
        }

        figure {
            margin-inline: 0;
            padding: 1rem;
            border: 1px solid currentColor;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        pre {
            padding: 1rem;
            overflow-x: auto;
            border: 1px solid currentColor;
        }

        .note,
        aside,
        details {
            margin-block: 1.5rem;
            padding: 1rem;
            border: 1px solid currentColor;
            border-inline-start-width: 0.4rem;
        }

        .note-info {
            border-inline-start-color: #0969da;
        }

        .note-warn {
            border-inline-start-color: #9a6700;
        }

        .note-alert {
            border-inline-start-color: #cf222e;
        }

        .note-label,
        .aside-label {
            margin-block-start: 0;
            font-weight: 700;
        }

        @media (prefers-color-scheme: dark) {
            .note-info {
                border-inline-start-color: #58a6ff;
            }

            .note-warn {
                border-inline-start-color: #d29922;
            }

            .note-alert {
                border-inline-start-color: #ff7b72;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Jidaikobo Markdown バージョン2新API表示確認</h1>
        <nav aria-label="サンプル変換方式の切り替え">
            <a href="index.php">互換API</a>
            <span aria-hidden="true"> / </span>
            <strong aria-current="page">バージョン2新API</strong>
            <span aria-hidden="true"> / </span>
            <a href="index-commonmark.php">League標準Extensionのみ</a>
            <span aria-hidden="true"> / </span>
            <a href="index-pico.php">Pico CSS Classless</a>
            <span aria-hidden="true"> / </span>
            <a href="index-bootstrap.php">Bootstrap 5</a>
        </nav>
        <p>
            このページは <code>MarkdownOptions</code> と
            <code>MarkdownConverter</code> を使う、バージョン2の推奨APIを確認します。
        </p>
        <p>以下は <code>examples/sample.md</code> の現在の変換結果です。</p>
    </header>
    <main>
        <?= $renderedHtml ?>
    </main>
</body>
</html>
