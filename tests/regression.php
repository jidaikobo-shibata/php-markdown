<?php

// This executable regression script intentionally defines helpers and runs checks.
// phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

use Jidaikobo\MarkdownExtra;
use Jidaikobo\Markdown\CheatSheet;
use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;

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

function assertInvalidArgument(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (InvalidArgumentException $exception) {
        return;
    }

    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function assertFragmentLinksResolve(string $html, string $message): void
{
    preg_match_all('/\bid="([^"]+)"/u', $html, $idMatches);
    preg_match_all('/\bhref="#([^"]+)"/u', $html, $linkMatches);

    $ids = $idMatches[1];
    if (count($ids) !== count(array_unique($ids))) {
        fwrite(STDERR, "FAIL: {$message} Duplicate IDs were found.\n");
        exit(1);
    }

    $knownIds = array_fill_keys($ids, true);
    foreach ($linkMatches[1] as $target) {
        if (!isset($knownIds[$target])) {
            fwrite(STDERR, "FAIL: {$message} Missing fragment target: {$target}\n");
            exit(1);
        }
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

$legacyCaptionBoundaries = <<<'MARKDOWN'
| Key | Value |
| --- | --- |
| :literal | kept |
| :second | kept2 |
MARKDOWN;

$legacyCaptionBoundaryHtml = MarkdownExtra::defaultTransform($legacyCaptionBoundaries);
assertNotContains(
    '<caption>',
    $legacyCaptionBoundaryHtml,
    'A normal multi-cell data row beginning with a colon must not become a caption.'
);
assertContains(
    '<td>:literal</td>',
    $legacyCaptionBoundaryHtml,
    'A colon in a non-final data row must remain unchanged.'
);
assertContains(
    '<td>:second</td>',
    $legacyCaptionBoundaryHtml,
    'A final row with another non-empty cell must remain data.'
);

$preferredCaption = <<<'MARKDOWN'
*Preferred caption*
| Key | Value |
| --- | --- |
| :literal | |
MARKDOWN;

$preferredCaptionHtml = MarkdownExtra::defaultTransform($preferredCaption);
assertContains('<caption>Preferred caption</caption>', $preferredCaptionHtml, 'The preferred caption should win.');
assertContains(
    '<td>:literal</td>',
    $preferredCaptionHtml,
    'A legacy-looking row must not be mutated after a caption already exists.'
);

$rowHeaderBoundaries = <<<'MARKDOWN'
| Label | Value |
| --- | --- |
| Row: `code` | ordinary |
| **Actual:** | header |
MARKDOWN;

$rowHeaderBoundaryHtml = MarkdownExtra::defaultTransform($rowHeaderBoundaries);
assertContains(
    '<td>Row: <code>code</code></td>',
    $rowHeaderBoundaryHtml,
    'A colon before a later inline-code node must not mark a row heading.'
);
assertContains(
    '<th scope="row"><strong>Actual</strong></th>',
    $rowHeaderBoundaryHtml,
    'A colon in the genuinely final inline content should mark a row heading.'
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
    '<h2 id="content-first-section">First section<a aria-label="First section" ' .
    'href="#content-first-section" class="heading-permalink" title="Permalink">¶</a></h2>',
    $navigationHtml,
    'Heading permalinks should be keyboard focusable and have a meaningful accessible name.'
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
    '<div class="note note-info" role="note" aria-label="Reference information">',
    $containerHtml,
    'An info note should use the note role and an accessible name.'
);
assertContains(
    '<p class="note-label">Reference information</p>',
    $containerHtml,
    'A note title should remain visible without requiring a generated ID.'
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
    '<aside class="aside" aria-label="Related information">',
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

$combinedNotes = $newConverter->convert("::: note info \"Repeated\"\nOne\n:::\n")
    . $newConverter->convert("::: note info \"Repeated\"\nTwo\n:::\n");
assertNotContains(
    'id="jidaikobo-note-',
    $combinedNotes,
    'Separately converted note fragments must not create colliding generated IDs.'
);
if (substr_count($combinedNotes, 'aria-label="Repeated"') !== 2) {
    fwrite(STDERR, "FAIL: Each combined note should retain its accessible name.\n");
    exit(1);
}

$extensionlessOptions = MarkdownOptions::defaults()
    ->withBaseUrl('https://example.com')
    ->withDocumentRoot(dirname(__DIR__));
$extensionlessHtml = (new MarkdownConverter($extensionlessOptions))->convert('[License](/LICENSE)');
assertContains(
    '<a href="https://example.com/LICENSE">License</a>',
    $extensionlessHtml,
    'An extensionless local file should remain an ordinary link.'
);
assertNotContains('License (,', $extensionlessHtml, 'Empty file types must not be displayed.');

$rawHtml = '<script>alert(document.domain)</script>';
assertContains(
    '&lt;script&gt;alert(document.domain)&lt;/script&gt;',
    (new MarkdownConverter())->convert($rawHtml),
    'The new API should escape raw HTML by default.'
);
assertContains(
    $rawHtml,
    MarkdownExtra::defaultTransform($rawHtml),
    'The compatibility API should continue to allow raw HTML.'
);
assertNotContains(
    '<script>',
    (new MarkdownConverter(
        MarkdownOptions::defaults()->withHtmlInput(MarkdownOptions::HTML_INPUT_STRIP)
    ))->convert($rawHtml),
    'The new API should allow applications to strip raw HTML.'
);
assertContains(
    $rawHtml,
    (new MarkdownConverter(
        MarkdownOptions::defaults()->withHtmlInput(MarkdownOptions::HTML_INPUT_ALLOW)
    ))->convert($rawHtml),
    'The new API should allow trusted applications to opt in to raw HTML.'
);

try {
    MarkdownOptions::defaults()->withHtmlInput('unsupported');
    fwrite(STDERR, "FAIL: Unsupported raw HTML policies must be rejected.\n");
    exit(1);
} catch (InvalidArgumentException $exception) {
    assertContains(
        'Unsupported html_input value',
        $exception->getMessage(),
        'The option error should be clear.'
    );
}

$customizedOptions = MarkdownOptions::defaults()
    ->withLeagueExtension(new StrikethroughExtension())
    ->withLeagueConfiguration([
        'heading_permalink' => ['min_heading_level' => 3],
        'attributes' => ['allow' => ['id']],
    ]);
$customizedHtml = (new MarkdownConverter($customizedOptions))->convert(
    "## Level two\n\n~~removed~~\n\n[Link](https://example.com){#kept .removed lang=ja}\n"
);
assertContains('<del>removed</del>', $customizedHtml, 'Applications should be able to add League extensions.');
assertContains(
    '<a id="kept" href="https://example.com">Link</a>',
    $customizedHtml,
    'Allowed attributes should remain.'
);
assertNotContains('class="removed"', $customizedHtml, 'Configuration lists must replace rather than append.');
assertNotContains('lang="ja"', $customizedHtml, 'Narrowed attribute allowlists must not retain default entries.');
assertNotContains(
    'heading-permalink',
    $customizedHtml,
    'Applications should be able to customize League extension configuration.'
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
$configured = $defaults
    ->withBaseUrl('https://example.com/base///')
    ->withDocumentRoot(__DIR__ . '/../examples/');
if (
    $defaults->getBaseUrl() !== ''
    || $configured->getBaseUrl() !== 'https://example.com/base'
    || $configured->getDocumentRoot() !== realpath(__DIR__ . '/../examples')
) {
    fwrite(STDERR, "FAIL: MarkdownOptions must be immutable.\n");
    exit(1);
}

$invalidBaseUrls = [
    'example.com',
    '//example.com',
    'ftp://example.com',
    'https://user@example.com',
    'https://example.com?tenant=1',
    'https://example.com#section',
    'https://example.com\\path',
    " https://example.com",
];
foreach ($invalidBaseUrls as $invalidBaseUrl) {
    assertInvalidArgument(
        static function () use ($invalidBaseUrl): void {
            MarkdownOptions::defaults()->withBaseUrl($invalidBaseUrl);
        },
        'The new API must reject ambiguous or non-HTTP base URLs.'
    );
}

$invalidDocumentRoots = [
    'relative/public',
    DIRECTORY_SEPARATOR,
    dirname(__DIR__) . '/definitely-missing-document-root',
    DIRECTORY_SEPARATOR . 'tmp/..',
];
foreach ($invalidDocumentRoots as $invalidDocumentRoot) {
    assertInvalidArgument(
        static function () use ($invalidDocumentRoot): void {
            MarkdownOptions::defaults()->withDocumentRoot($invalidDocumentRoot);
        },
        'The new API must reject unsafe or ambiguous document roots.'
    );
}

MarkdownExtra::setTargetUrl('relative-base?tenant=1');
MarkdownExtra::setReplacePath('relative-root');
assertContains(
    '<p>Compatibility settings</p>',
    MarkdownExtra::defaultTransform('Compatibility settings'),
    'Legacy configuration values must not start throwing new exceptions.'
);

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
$englishCheatSheetFragment = CheatSheet::getHtml(CheatSheet::LANGUAGE_ENGLISH);
$japaneseCheatSheetFragment = CheatSheet::getHtml(CheatSheet::LANGUAGE_JAPANESE);
if (
    readRequiredFile(__DIR__ . '/../resources/cheatsheet/files/download.txt')
        !== readRequiredFile(__DIR__ . '/../examples/files/download.txt')
    || readRequiredFile(__DIR__ . '/../resources/cheatsheet/files/sample-image.svg')
        !== readRequiredFile(__DIR__ . '/../examples/files/sample-image.svg')
) {
    fwrite(STDERR, "FAIL: Reusable and preview cheat-sheet sample assets must remain synchronized.\n");
    exit(1);
}
assertFragmentLinksResolve($englishCheatSheetFragment, 'The English cheat-sheet fragment must be self-consistent.');
assertFragmentLinksResolve($japaneseCheatSheetFragment, 'The Japanese cheat-sheet fragment must be self-consistent.');
assertContains(
    '<p><strong>Contents</strong></p>',
    $englishCheatSheetFragment,
    'The reusable English fragment should begin with its contents label.'
);
if (strpos($japaneseCheatSheetFragment, '<p><strong>目次</strong></p>') !== 0) {
    fwrite(STDERR, "FAIL: The reusable Japanese fragment must begin at its contents label.\n");
    exit(1);
}
assertNotContains('<main', $japaneseCheatSheetFragment, 'Reusable fragments must not choose a page landmark.');
assertNotContains('<style', $japaneseCheatSheetFragment, 'Reusable fragments must not contain presentation CSS.');
assertNotContains('<h1', $japaneseCheatSheetFragment, 'Reusable fragments must omit the preview-page title.');
assertContains(
    'href="files/download.txt">小さなテキストファイル (txt, 100 B)</a>',
    $japaneseCheatSheetFragment,
    'Reusable fragments should use portable relative sample-asset URLs by default.'
);

$prefixedCheatSheetFragment = CheatSheet::getHtml(
    CheatSheet::LANGUAGE_JAPANESE,
    '/cms-assets/php-markdown',
    'cms-markdown-help'
);
assertContains(
    'src="/cms-assets/php-markdown/files/sample-image.svg"',
    $prefixedCheatSheetFragment,
    'A CMS should be able to provide a root-relative sample-asset base URL.'
);
assertContains(
    'href="/cms-assets/php-markdown/files/download.txt"',
    $prefixedCheatSheetFragment,
    'The sample-asset base URL should apply to downloadable examples.'
);
assertContains(
    'id="cms-markdown-help-markdownの基本記法"',
    $prefixedCheatSheetFragment,
    'A CMS should be able to namespace fragment heading IDs.'
);
assertContains(
    'href="#cms-markdown-help-markdownの基本記法"',
    $prefixedCheatSheetFragment,
    'TOC and permalink destinations should follow the configured heading ID prefix.'
);
assertFragmentLinksResolve(
    $prefixedCheatSheetFragment,
    'The namespaced cheat-sheet fragment must remain self-consistent.'
);
assertInvalidArgument(
    static function (): void {
        CheatSheet::getHtml('../ja');
    },
    'Cheat-sheet languages must be selected from an allowlist.'
);
assertInvalidArgument(
    static function (): void {
        CheatSheet::getHtml(CheatSheet::LANGUAGE_ENGLISH, 'javascript:alert(1)');
    },
    'Cheat-sheet asset bases must reject unsafe URL schemes.'
);
assertInvalidArgument(
    static function (): void {
        CheatSheet::getHtml(CheatSheet::LANGUAGE_ENGLISH, '', 'unsafe prefix');
    },
    'Cheat-sheet heading prefixes must not allow attribute injection.'
);
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
