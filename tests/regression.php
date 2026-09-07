<?php

// This executable regression script intentionally defines helpers and runs checks.
// phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

use Jidaikobo\MarkdownExtra;

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

assertContains(
    '<table id="result-table" class="results">' . "\n" .
    '<caption>Accessible table</caption>',
    $html,
    'Table attributes and caption should be preserved.'
);
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
    '<img src="files/sample_(image).svg?first=1&amp;second=2" alt="Complex image" ' .
    'title="Image title" id="sample-image" class="image" />',
    $html,
    'Figures should delegate image URLs, titles, and attributes to the base parser.'
);
assertContains(
    "<pre><code class=\"markdown\">![Code sample](files/sample-image.svg)\n" .
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
    '<a href="https://example.com/" class="external" lang="ja">Attributed link</a>',
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

fwrite(STDOUT, "All regression checks passed.\n");
