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

$instanceFacade = new MarkdownExtra();
$instanceHtml = $instanceFacade->transform("**Instance facade**\n");
assertContains(
    '<strong>Instance facade</strong>',
    $instanceHtml,
    'The version 1 instance transform API should remain available.'
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
