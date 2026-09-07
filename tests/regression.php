<?php

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
*Figure caption*

```markdown
![Code sample](files/sample-image.svg)
*Code caption*
```

[Text file](/files/download.txt)

[SVG file](/files/sample-image.svg)

[Attributed link](https://example.com/){.external lang=ja}
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
    "<pre><code class=\"markdown\">![Code sample](files/sample-image.svg)\n" .
    "*Code caption*\n</code></pre>",
    $html,
    'Figure syntax in a fenced code block must remain unchanged.'
);
assertContains('Text file (txt, 100 B)', $html, 'Text links should include type and size.');
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

fwrite(STDOUT, "All regression checks passed.\n");
