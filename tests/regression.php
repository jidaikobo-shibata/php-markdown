<?php

// This executable regression script intentionally defines helpers and runs checks.
// phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

use Jidaikobo\MarkdownExtra;
use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;

require __DIR__ . '/../vendor/autoload.php';

function assertContains(string $needle, string $haystack, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, "FAIL: {$message}\nMissing: {$needle}\n");
        exit(1);
    }
}

function assertNotContains(string $needle, string $haystack, string $message): void
{
    if (strpos($haystack, $needle) !== false) {
        fwrite(STDERR, "FAIL: {$message}\nUnexpected: {$needle}\n");
        exit(1);
    }
}

function renderExample(string $path): string
{
    ob_start();
    include $path;
    $output = ob_get_clean();

    if ($output === false) {
        fwrite(STDERR, "FAIL: Example output buffering failed.\n");
        exit(1);
    }

    return $output;
}

function readRequiredFile(string $path): string
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        fwrite(STDERR, "FAIL: Unable to read {$path}.\n");
        exit(1);
    }

    return $contents;
}

MarkdownExtra::setTargetUrl('http://127.0.0.1:8000');
MarkdownExtra::setReplacePath(__DIR__ . '/../examples');

$markdown = <<<'MARKDOWN'
| Column | Value |
| --- | --- |
| Row: | Cell |
|: Accessible table
{#result-table .results}

![Sample](files/sample-image.svg)
*Figure caption with [a link](https://example.com/figure), **strong text**, and `code`*

![Complex image](<files/sample_(image).svg?first=1&second=2> "Image title"){#sample-image .image}
*Complex image caption*

```markdown
![Code sample](files/sample-image.svg)
*Code caption*
```

[Text file](/files/download.txt)

[Text file with query](/files/download.txt?download=1#start)

[SVG file](/files/sample-image.svg)

[Attributed link](https://example.com/){.external lang=ja}

[Outside file](/../composer.json)

[Encoded outside file](/%2e%2e/composer.json)

[Different host](http://127.0.0.1.example:8000/files/download.txt)

[Protocol-relative URL](//example.com/files/download.txt)
MARKDOWN;

$html = MarkdownExtra::defaultTransform($markdown);

assertContains('<table class="results" id="result-table">', $html, 'Table attributes should be preserved.');
assertContains('<caption>Accessible table</caption>', $html, 'Table captions should be preserved.');
assertContains('<th scope="col">Column</th>', $html, 'Column headers need scope="col".');
assertContains('<th scope="row">Row</th>', $html, 'Row headers need scope="row".');
assertContains('<figure>', $html, 'An image followed by emphasis should become a figure.');
assertContains(
    '<figcaption>Figure caption with <a href="https://example.com/figure">a link</a>, ' .
    '<strong>strong text</strong>, and <code>code</code></figcaption>',
    $html,
    'Figure captions should support inline Markdown.'
);
assertContains(
    '<img class="image" id="sample-image" src="files/sample_(image).svg?first=1&amp;second=2" ' .
    'alt="Complex image" title="Image title" />',
    $html,
    'Figures should delegate image URLs, titles, and attributes to the base parser.'
);
assertContains(
    "<pre><code class=\"language-markdown\">![Code sample](files/sample-image.svg)\n" .
    "*Code caption*\n</code></pre>",
    $html,
    'Figure syntax in a fenced code block must remain unchanged.'
);
assertContains('Text file (txt, 100 B)', $html, 'Text links should include type and size.');
assertContains(
    'Text file with query (txt, 100 B)',
    $html,
    'Query strings and fragments should not prevent local file resolution.'
);
assertContains(
    '<a href="http://127.0.0.1:8000/files/sample-image.svg">SVG file</a>',
    $html,
    'SVG links should not include type and size.'
);
assertNotContains('SVG file (svg,', $html, 'SVG links must be treated as image links.');
assertContains(
    '<a class="external" lang="ja" href="https://example.com/">Attributed link</a>',
    $html,
    'Inline link attributes should be preserved.'
);
assertNotContains('Outside file (json,', $html, 'Path traversal must not expose file metadata.');
assertNotContains('Encoded outside file (json,', $html, 'Encoded traversal must remain outside the root.');
assertNotContains('Different host (txt,', $html, 'Host-prefix URLs must not be treated as local.');
assertContains(
    '<a href="//example.com/files/download.txt">Protocol-relative URL</a>',
    $html,
    'Protocol-relative URLs must not be completed as root-relative URLs.'
);

$leadingCaption = <<<'MARKDOWN'
*Caption with [a link](https://example.com/table) and `code`*
| Column | Value |
| --- | --- |
| A | 10 |
MARKDOWN;

$leadingCaptionHtml = MarkdownExtra::defaultTransform($leadingCaption);
assertContains(
    '<caption>Caption with <a href="https://example.com/table">a link</a> and ' .
    '<code>code</code></caption>',
    $leadingCaptionHtml,
    'An emphasized paragraph directly before a table should become its caption.'
);
assertNotContains(
    '<p><em>Caption with',
    $leadingCaptionHtml,
    'A converted leading table caption must not remain as a paragraph.'
);

$separateEmphasis = str_replace("code`*\n|", "code`*\n\n|", $leadingCaption);
$separateEmphasisHtml = MarkdownExtra::defaultTransform($separateEmphasis);
assertContains(
    '<p><em>Caption with <a href="https://example.com/table">a link</a> and ' .
    '<code>code</code></em></p>',
    $separateEmphasisHtml,
    'A blank line should keep emphasized text separate from the table.'
);
assertNotContains(
    '<caption>',
    $separateEmphasisHtml,
    'Emphasized text separated by a blank line must not become a table caption.'
);

$notStandalone = <<<'MARKDOWN'
![Sample](files/sample-image.svg) trailing text
*This remains emphasis*
MARKDOWN;

$notStandaloneHtml = MarkdownExtra::defaultTransform($notStandalone);
assertNotContains(
    '<figure>',
    $notStandaloneHtml,
    'An image with trailing text must not become a figure.'
);

$indentedCode = <<<'MARKDOWN'
    ![Indented code](files/sample-image.svg)
    *Indented caption*
MARKDOWN;

$indentedCodeHtml = MarkdownExtra::defaultTransform($indentedCode);
assertNotContains('<figure>', $indentedCodeHtml, 'Indented code must not become a figure.');

$referenceFigure = <<<'MARKDOWN'
![Reference image][sample]
*Caption with a [reference link][details]*

[sample]: files/sample-image.svg "Reference title"
[details]: https://example.com/details
MARKDOWN;

$referenceFigureHtml = MarkdownExtra::defaultTransform($referenceFigure);
assertContains(
    '<img src="files/sample-image.svg" alt="Reference image" title="Reference title" />',
    $referenceFigureHtml,
    'Reference-style images should be supported in figures.'
);
assertContains(
    '<figcaption>Caption with a <a href="https://example.com/details">reference link</a></figcaption>',
    $referenceFigureHtml,
    'Reference-style links should be supported in figure captions.'
);

MarkdownExtra::setTargetUrl('http://127.0.0.1:8000/base');
$basePathHtml = MarkdownExtra::defaultTransform(
    "[Base path file](/files/download.txt)\n\n" .
    "[Outside base](http://127.0.0.1:8000/files/download.txt)"
);
assertContains(
    '<a href="http://127.0.0.1:8000/base/files/download.txt">' .
    'Base path file (txt, 100 B)</a>',
    $basePathHtml,
    'A target URL with a base path should map from that base.'
);
assertNotContains(
    'Outside base (txt,',
    $basePathHtml,
    'Same-origin URLs outside the configured base path must not expose metadata.'
);

$newOptions = MarkdownOptions::defaults()
    ->withBaseUrl('http://127.0.0.1:8000')
    ->withDocumentRoot(__DIR__ . '/../examples');
$newConverter = new MarkdownConverter($newOptions);
$newApiHtml = $newConverter->convert("[New API file](/files/download.txt)\n");
assertContains(
    '<a href="http://127.0.0.1:8000/files/download.txt">New API file (txt, 100 B)</a>',
    $newApiHtml,
    'The new instance API should apply its own URL and document-root options.'
);

$isolatedConverter = new MarkdownConverter();
$isolatedHtml = $isolatedConverter->convert("[Isolated](/files/download.txt)\n");
assertContains(
    '<a href="/files/download.txt">Isolated</a>',
    $isolatedHtml,
    'A new API instance must not inherit the compatibility facade static state.'
);
assertNotContains(
    'Isolated (txt,',
    $isolatedHtml,
    'File metadata must not leak between converter instances.'
);

$navigationHtml = $isolatedConverter->convert(
    "# Document title\n\n[TOC]\n\n## First section\n\n### Child section\n"
);
assertContains(
    '<ul class="table-of-contents">',
    $navigationHtml,
    'The TOC placeholder should render a table of contents.'
);
assertContains(
    '<a href="#content-first-section">First section</a>',
    $navigationHtml,
    'Table-of-contents links should target generated heading fragments.'
);
assertContains(
    '<h2>First section<a id="content-first-section" href="#content-first-section" ' .
    'class="heading-permalink" aria-hidden="true" tabindex="-1" title="Permalink">¶</a></h2>',
    $navigationHtml,
    'Headings should receive an accessible, non-tabbable permalink marker.'
);
assertNotContains('[TOC]', $navigationHtml, 'A replaced TOC placeholder must not remain in the output.');

$withoutTocHtml = $isolatedConverter->convert("## A heading without a placeholder\n");
assertNotContains(
    'class="table-of-contents"',
    $withoutTocHtml,
    'A document without a TOC placeholder must not receive an automatic TOC.'
);

$instanceFacade = new MarkdownExtra();
$instanceHtml = $instanceFacade->transform("**Instance facade**\n");
assertContains(
    '<strong>Instance facade</strong>',
    $instanceHtml,
    'The version 1 instance transform API should remain available.'
);

$containers = <<<'MARKDOWN'
::: note info "Reference information"
A [link](https://example.com/note) and **strong text**.
:::

::: note warn "Caution"
Check the current value.
:::

::: note alert "Important warning"
This is static document content.
:::

::: aside "Related information"
- An item
- Another item
:::

::: details "More details"
```text
:::
```
The fence-like text in code must not close the container.
:::

::: note
The default variant is info.
:::

::: details
The default summary is Details.
:::
MARKDOWN;

$containerHtml = MarkdownExtra::defaultTransform($containers);
assertContains(
    '<div class="note note-info" role="note" aria-labelledby="jidaikobo-note-1-label">',
    $containerHtml,
    'An info note should use the note role and an accessible name.'
);
assertContains(
    '<p id="jidaikobo-note-1-label" class="note-label">Reference information</p>',
    $containerHtml,
    'A note title should remain visible and label the note.'
);
assertContains(
    '<a href="https://example.com/note">link</a> and <strong>strong text</strong>',
    $containerHtml,
    'Markdown inside a note should be parsed normally.'
);
assertContains(
    '<div class="note note-warn" role="note"',
    $containerHtml,
    'Warn notes should use a fixed class.'
);
assertContains(
    '<div class="note note-alert" role="note"',
    $containerHtml,
    'Alert notes should use a fixed class.'
);
assertNotContains('role="alert"', $containerHtml, 'A static alert variant must not become an ARIA alert.');
assertContains(
    '<aside class="aside" aria-labelledby=',
    $containerHtml,
    'Aside syntax should render a native aside with an accessible name.'
);
assertContains('<ul>', $containerHtml, 'Block Markdown inside an aside should remain structured.');
assertContains('<details class="details">', $containerHtml, 'Details syntax should render a native details element.');
assertContains('<summary>More details</summary>', $containerHtml, 'Details should have a summary.');
assertContains(
    "<pre><code class=\"language-text\">:::\n</code></pre>",
    $containerHtml,
    'Container fences in code must remain code.'
);
assertContains(
    '<p>The fence-like text in code must not close the container.</p>',
    $containerHtml,
    'Content after fenced code should remain inside details.'
);
assertContains(
    '<div class="note note-info" role="note">',
    $containerHtml,
    'A note without arguments should use the info variant without inventing a label.'
);
assertContains(
    '<summary>Details</summary>',
    $containerHtml,
    'Details without a title should use a usable default summary.'
);

$nestedContainers = <<<'MARKDOWN'
:::: aside "Outer container"
::: note info "Inner note"
Nested content.
:::
::::
MARKDOWN;

$nestedContainerHtml = MarkdownExtra::defaultTransform($nestedContainers);
assertContains('<aside class="aside"', $nestedContainerHtml, 'A longer outer fence should support nesting.');
assertContains(
    '<div class="note note-info" role="note"',
    $nestedContainerHtml,
    'A shorter inner fence should remain nested in the outer container.'
);

$defaults = MarkdownOptions::defaults();
$configured = $defaults->withBaseUrl('https://example.com');
if ($defaults->getBaseUrl() !== '' || $configured->getBaseUrl() !== 'https://example.com') {
    fwrite(STDERR, "FAIL: MarkdownOptions must be immutable.\n");
    exit(1);
}

$_SERVER['SERVER_PORT'] = 8000;
$compatibilityExample = renderExample(__DIR__ . '/../examples/index.php');
$versionTwoExample = renderExample(__DIR__ . '/../examples/index-v2.php');
$commonMarkExample = renderExample(__DIR__ . '/../examples/index-commonmark.php');
$picoExample = renderExample(__DIR__ . '/../examples/index-pico.php');
$bootstrapExample = renderExample(__DIR__ . '/../examples/index-bootstrap.php');
$englishPicoCheatSheet = readRequiredFile(__DIR__ . '/../examples/cheatsheet-pico.html');
$japanesePicoCheatSheet = readRequiredFile(__DIR__ . '/../examples/cheatsheet-pico-ja.html');
$englishBootstrapCheatSheet = readRequiredFile(__DIR__ . '/../examples/cheatsheet-bootstrap.html');
$japaneseBootstrapCheatSheet = readRequiredFile(__DIR__ . '/../examples/cheatsheet-bootstrap-ja.html');
assertContains(
    'Jidaikobo MarkdownExtra 互換API表示確認',
    $compatibilityExample,
    'The compatibility API browser example should render.'
);
assertContains(
    'Jidaikobo Markdown バージョン2新API表示確認',
    $versionTwoExample,
    'The version 2 API browser example should render.'
);
assertContains('<figure>', $compatibilityExample, 'The compatibility example should render custom syntax.');
assertContains('<figure>', $versionTwoExample, 'The version 2 example should render custom syntax.');
assertContains(
    '<div class="note note-info" role="note"',
    $compatibilityExample,
    'The compatibility example should render note containers.'
);
assertContains(
    '<div class="note note-info" role="note"',
    $versionTwoExample,
    'The version 2 example should render note containers.'
);
assertContains(
    '<p><em>この強調文はcaptionではありません</em></p>',
    $compatibilityExample,
    'The compatibility example should show that a blank line prevents caption conversion.'
);
assertContains(
    '<p><em>この強調文はcaptionではありません</em></p>',
    $versionTwoExample,
    'The version 2 example should show that a blank line prevents caption conversion.'
);
assertContains(
    'League CommonMark標準Extensionのみの表示確認',
    $commonMarkExample,
    'The standard League CommonMark baseline example should render.'
);
assertContains(
    'Pico CSS Classless表示確認',
    $picoExample,
    'The Pico CSS browser example should render.'
);
assertContains(
    'assets/vendor/pico/pico.classless.min.css',
    $picoExample,
    'The Pico CSS browser example should use the locally stored stylesheet.'
);
assertContains('<figure>', $picoExample, 'The Pico CSS example should render custom syntax.');
assertContains(
    'Bootstrap 5表示確認',
    $bootstrapExample,
    'The Bootstrap browser example should render.'
);
assertContains(
    'assets/vendor/bootstrap/bootstrap.min.css',
    $bootstrapExample,
    'The Bootstrap browser example should use the locally stored stylesheet.'
);
assertContains(
    'class="table table-striped table-bordered align-middle"',
    $bootstrapExample,
    'The Bootstrap example should add table presentation classes through the AST.'
);
assertContains(
    '<figure class="figure">',
    $bootstrapExample,
    'The Bootstrap example should add a figure class through the AST.'
);
assertContains(
    'class="sample-image figure-img img-fluid rounded"',
    $bootstrapExample,
    'The Bootstrap example should preserve author classes while adding image classes.'
);
assertContains(
    '<figcaption class="figure-caption">',
    $bootstrapExample,
    'The Bootstrap example should add a figcaption class through the AST.'
);
assertContains(
    'class="note note-warn alert alert-warning" role="note"',
    $bootstrapExample,
    'The Bootstrap example should map warn notes to Bootstrap alert classes.'
);
assertContains(
    '<a class="alert-link" href="https://example.com/note">リンク</a>',
    $bootstrapExample,
    'The Bootstrap example should style links in info notes with the alert palette.'
);
assertContains(
    '<a class="alert-link" href="https://example.com/check">現在値の確認手順</a>',
    $bootstrapExample,
    'The Bootstrap example should style links in warn notes with the alert palette.'
);
assertContains(
    '<a class="alert-link" href="https://example.com/caution">操作上の注意</a>',
    $bootstrapExample,
    'The Bootstrap example should style links in alert notes with the alert palette.'
);
assertContains(
    '<details class="details border rounded p-3 my-4">',
    $bootstrapExample,
    'The Bootstrap example should style details through the AST.'
);
assertContains(
    '<ul class="table-of-contents">',
    $compatibilityExample,
    'The compatibility example should render the sample TOC placeholder.'
);
assertContains(
    'class="heading-permalink"',
    $versionTwoExample,
    'The version 2 example should render heading permalinks.'
);
assertContains(
    '<html lang="en">',
    $englishPicoCheatSheet,
    'The English Pico cheat sheet should declare its language.'
);
assertContains('Markdown Cheat Sheet', $englishPicoCheatSheet, 'The English Pico cheat sheet should render.');
assertContains(
    'Small text download (txt, 100 B)',
    $englishPicoCheatSheet,
    'The English Pico cheat sheet should contain rendered local file metadata.'
);
assertContains(
    'assets/vendor/pico/pico.classless.min.css',
    $japanesePicoCheatSheet,
    'The Japanese Pico cheat sheet should use the local Pico stylesheet.'
);
assertNotContains(
    'class="alert-link"',
    $japanesePicoCheatSheet,
    'Bootstrap presentation classes must not leak into the Pico cheat sheet.'
);
assertContains(
    '<html lang="ja">',
    $japaneseBootstrapCheatSheet,
    'The Japanese Bootstrap cheat sheet should declare its language.'
);
assertContains(
    'assets/vendor/bootstrap/bootstrap.min.css',
    $englishBootstrapCheatSheet,
    'The English Bootstrap cheat sheet should use the local Bootstrap stylesheet.'
);
assertContains(
    'Markdownチートシート',
    $japaneseBootstrapCheatSheet,
    'The Japanese Bootstrap cheat sheet should render.'
);
assertContains(
    'class="note note-alert alert alert-danger" role="note"',
    $japaneseBootstrapCheatSheet,
    'The Japanese Bootstrap cheat sheet should render and style Jidaikobo note syntax.'
);
assertNotContains('<?php', $englishPicoCheatSheet, 'Static cheat sheets must not contain PHP source.');
assertContains(
    '<td>月曜日:</td>',
    $commonMarkExample,
    'Row-header markers should remain readable without the Jidaikobo extension.'
);
assertContains(
    '<p><em>2026年9月時点の地域別人数</em></p>',
    $commonMarkExample,
    'A leading table caption should remain readable without the Jidaikobo extension.'
);
assertContains(
    '<td>: 属性指定を試した表のキャプション</td>',
    $commonMarkExample,
    'Legacy table-caption text should remain readable without the Jidaikobo extension.'
);
assertContains(
    '<a href="/files/download.txt">ローカルの小さな文書</a>',
    $commonMarkExample,
    'Root-relative links should remain ordinary links in the baseline.'
);
assertNotContains(
    '<caption>',
    $commonMarkExample,
    'The standard League extensions must not apply Jidaikobo table captions.'
);
assertNotContains(
    '<figure>',
    $commonMarkExample,
    'The standard League extensions must not apply Jidaikobo figures.'
);
assertNotContains(
    'ローカルの小さな文書 (txt,',
    $commonMarkExample,
    'The standard League extensions must not add local file metadata.'
);

fwrite(STDOUT, "All regression checks passed.\n");
