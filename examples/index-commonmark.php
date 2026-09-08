<?php

declare(strict_types=1);

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

require __DIR__ . '/../vendor/autoload.php';

$samplePath = __DIR__ . '/sample.md';
$markdown = file_get_contents($samplePath);

if ($markdown === false) {
    http_response_code(500);
    $renderedHtml = '<p role="alert">sample.md を読み込めませんでした。</p>';
} else {
    $environment = new Environment([
        'html_input' => 'allow',
        'allow_unsafe_links' => false,
        'max_nesting_level' => 100,
        'max_delimiters_per_line' => 1000,
        'attributes' => [
            'allow' => ['id', 'class', 'lang', 'title', 'rel'],
        ],
    ]);

    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new TableExtension());
    $environment->addExtension(new AttributesExtension());

    $converter = new MarkdownConverter($environment);
    $renderedHtml = $converter->convert($markdown)->getContent();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>League標準Extensionのみ - Markdown表示確認</title>
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
    </style>
</head>
<body>
    <header>
        <h1>League CommonMark標準Extensionのみの表示確認</h1>
        <nav aria-label="サンプル変換方式の切り替え">
            <a href="index.php">互換API</a>
            <span aria-hidden="true"> / </span>
            <a href="index-v2.php">バージョン2新API</a>
            <span aria-hidden="true"> / </span>
            <strong aria-current="page">League標準Extensionのみ</strong>
        </nav>
        <p>
            このページはLeague CommonMark公式の
            <code>CommonMarkCoreExtension</code>、<code>TableExtension</code>、
            <code>AttributesExtension</code>だけを使います。
            Jidaikoboの独自Extensionは適用しません。
        </p>
        <p>
            独自記法の記号と内容が欠落せず、通常のMarkdownとして
            どのように読めるかを確認するためのベースラインです。
        </p>
        <p>以下は <code>examples/sample.md</code> の変換結果です。</p>
    </header>
    <main>
        <?= $renderedHtml ?>
    </main>
</body>
</html>
