<?php

declare(strict_types=1);

use Jidaikobo\Examples\Support\Bootstrap5ExampleConverter;
use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;

$projectRoot = dirname(__DIR__);
$checkOnly = in_array('--check', $argv, true);

require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/examples/Support/Bootstrap5ClassProcessor.php';
require $projectRoot . '/examples/Support/Bootstrap5FigcaptionRenderer.php';
require $projectRoot . '/examples/Support/Bootstrap5Extension.php';
require $projectRoot . '/examples/Support/Bootstrap5ExampleConverter.php';

$documents = [
    'en' => [
        'source' => $projectRoot . '/docs/cheatsheet.md',
        'title' => 'Markdown Cheat Sheet',
        'pico_output' => $projectRoot . '/examples/cheatsheet-pico.html',
        'bootstrap_output' => $projectRoot . '/examples/cheatsheet-bootstrap.html',
    ],
    'ja' => [
        'source' => $projectRoot . '/docs/cheatsheet-ja.md',
        'title' => 'Markdownチートシート',
        'pico_output' => $projectRoot . '/examples/cheatsheet-pico-ja.html',
        'bootstrap_output' => $projectRoot . '/examples/cheatsheet-bootstrap-ja.html',
    ],
];

$options = MarkdownOptions::defaults()
    ->withBaseUrl('http://127.0.0.1:8000')
    ->withDocumentRoot($projectRoot . '/examples');

foreach ($documents as $language => $document) {
    $markdown = file_get_contents($document['source']);
    if ($markdown === false) {
        fwrite(STDERR, 'Unable to read ' . $document['source'] . "\n");
        exit(1);
    }

    $picoConverter = new MarkdownConverter($options);
    $picoContent = $picoConverter->convert($markdown);
    $picoHtml = renderPage($language, $document['title'], 'pico', $picoContent);
    writePage($document['pico_output'], $picoHtml, $checkOnly);

    $bootstrapConverter = Bootstrap5ExampleConverter::create($options);
    $bootstrapContent = $bootstrapConverter->convert($markdown)->getContent();
    $bootstrapHtml = renderPage($language, $document['title'], 'bootstrap', $bootstrapContent);
    writePage($document['bootstrap_output'], $bootstrapHtml, $checkOnly);
}

fwrite(STDOUT, $checkOnly
    ? "The four static cheat-sheet pages are up to date.\n"
    : "Generated four static cheat-sheet pages.\n");

function renderPage(string $language, string $title, string $profile, string $content): string
{
    $escapedLanguage = htmlspecialchars($language, ENT_QUOTES, 'UTF-8');
    $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $isBootstrap = $profile === 'bootstrap';
    $stylesheet = $isBootstrap
        ? 'assets/vendor/bootstrap/bootstrap.min.css'
        : 'assets/vendor/pico/pico.classless.min.css';
    $bodyClass = $isBootstrap ? ' class="bg-body-tertiary"' : '';
    $headerClass = $isBootstrap ? ' class="container py-4"' : '';
    $mainClass = $isBootstrap ? ' class="container bg-body rounded shadow-sm p-3 p-md-5 mb-4"' : '';
    $profileName = $isBootstrap ? 'Bootstrap 5' : 'Pico CSS';
    $otherProfile = $isBootstrap ? 'pico' : 'bootstrap';
    $otherProfileName = $isBootstrap ? 'Pico CSS' : 'Bootstrap 5';
    $languageSuffix = $language === 'ja' ? '-ja' : '';
    $otherLanguageSuffix = $language === 'ja' ? '' : '-ja';
    $otherLanguageName = $language === 'ja' ? 'English' : '日本語';
    $otherLanguageCode = $language === 'ja' ? 'en' : 'ja';
    $navClass = $isBootstrap ? ' class="d-flex flex-wrap gap-2"' : '';
    $linkClass = $isBootstrap ? ' class="btn btn-outline-secondary btn-sm"' : '';
    $currentClass = $isBootstrap ? ' class="btn btn-primary btn-sm"' : '';

    return '<!DOCTYPE html>' . "\n"
        . '<html lang="' . $escapedLanguage . '">' . "\n"
        . '<head>' . "\n"
        . '    <meta charset="UTF-8">' . "\n"
        . '    <meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
        . '    <title>' . $escapedTitle . ' — ' . $profileName . '</title>' . "\n"
        . '    <link rel="stylesheet" href="' . $stylesheet . '">' . "\n"
        . '    <link rel="stylesheet" href="assets/cheatsheet.css">' . "\n"
        . '</head>' . "\n"
        . '<body' . $bodyClass . '>' . "\n"
        . '    <header' . $headerClass . '>' . "\n"
        . '        <nav' . $navClass . ' aria-label="Cheat sheet style and language">' . "\n"
        . '            <strong' . $currentClass . ' aria-current="page">' . $profileName . '</strong>' . "\n"
        . '            <a' . $linkClass . ' href="cheatsheet-' . $otherProfile . $languageSuffix . '.html">'
        . $otherProfileName . '</a>' . "\n"
        . '            <a' . $linkClass . ' href="cheatsheet-' . $profile . $otherLanguageSuffix
        . '.html" hreflang="' . $otherLanguageCode . '">' . $otherLanguageName . '</a>' . "\n"
        . '        </nav>' . "\n"
        . '    </header>' . "\n"
        . '    <main' . $mainClass . '>' . "\n"
        . $content
        . '    </main>' . "\n"
        . '</body>' . "\n"
        . '</html>' . "\n";
}

function writePage(string $path, string $contents, bool $checkOnly): void
{
    if ($checkOnly) {
        if (file_get_contents($path) !== $contents) {
            fwrite(STDERR, $path . " is not up to date. Run composer build-cheatsheets.\n");
            exit(1);
        }

        return;
    }

    if (file_put_contents($path, $contents) === false) {
        fwrite(STDERR, 'Unable to write ' . $path . "\n");
        exit(1);
    }
}
