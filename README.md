[![MIT](https://custom-icon-badges.herokuapp.com/badge/license-MIT-8BB80A.svg?logo=law&logoColor=white)](https://github.com/jidaikobo-shibata/php-markdown?tab=MIT-1-ov-file)

# jidaikobo/php-markdown

Accessible Markdown extensions built on
[League CommonMark](https://commonmark.thephpleague.com/).

Version 2 adds accessible table headers and captions, figures with structured
captions, local file metadata, and root-relative URL completion. It uses a
per-converter configuration API while retaining the primary version 1 API as
a compatibility facade.

> Version 2.0.1 is the current stable release and is developed on the `main`
> branch. See the [archived version 1 documentation](docs/README-v1.md) when
> maintaining an existing version 1 installation.

## Installation

Install the stable version 2 series with Composer:

```bash
composer require jidaikobo/php-markdown:^2.0
```

Existing applications which must remain on version 1 should use:

```bash
composer require jidaikobo/php-markdown:^1.0
```

See [UPGRADING.md](UPGRADING.md) before changing the major-version constraint.

## Recommended version 2 API

Create immutable options and pass them to a converter instance:

```php
<?php

declare(strict_types=1);

use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;

require __DIR__ . '/vendor/autoload.php';

$options = MarkdownOptions::defaults()
    ->withBaseUrl('https://example.com')
    ->withDocumentRoot('/var/www/example.com/public');

$converter = new MarkdownConverter($options);
$html = $converter->convert($markdown);
```

`MarkdownOptions` is immutable: each `with...()` method returns a new options
object. Converter instances therefore do not share configuration. This is
particularly useful for tests, long-running PHP processes, and applications
which render content for multiple sites.

Both options are optional. Without a base URL, root-relative links are not
completed. File metadata is added only when both a base URL and document root
allow the URL to be resolved safely.

The new API validates these values when the corresponding `with...()` method
is called:

- `withBaseUrl()` accepts an empty string, or an absolute HTTP(S) URL with a
  host and without user information, a query, or a fragment. Trailing slashes
  are removed. Whitespace, control characters, and backslashes are rejected.
- `withDocumentRoot()` accepts an empty string, or an existing absolute
  directory other than a filesystem root. It is canonicalized with
  `realpath()`, so a symlink or `..` path resolving to the filesystem root is
  also rejected.

Invalid values throw `InvalidArgumentException`. Passing an empty string
clears that option. The version 1 compatibility facade intentionally keeps its
previous permissive setters and does not introduce these new exceptions.

### HTML input and League CommonMark configuration

The recommended API escapes raw HTML by default. Choose a policy explicitly
when an application has different requirements:

```php
$safeOptions = MarkdownOptions::defaults();

$stripHtmlOptions = MarkdownOptions::defaults()
    ->withHtmlInput(MarkdownOptions::HTML_INPUT_STRIP);

$trustedHtmlOptions = MarkdownOptions::defaults()
    ->withHtmlInput(MarkdownOptions::HTML_INPUT_ALLOW);
```

Use `HTML_INPUT_ALLOW` only for trusted Markdown, or sanitize the converted
HTML with a policy appropriate to the application before displaying it.

Additional League CommonMark extensions and their configuration can be added
without replacing the Jidaikobo environment:

```php
use League\CommonMark\Extension\Footnote\FootnoteExtension;

$options = MarkdownOptions::defaults()
    ->withLeagueExtension(new FootnoteExtension())
    ->withLeagueConfiguration([
        'table_of_contents' => [
            'max_heading_level' => 5,
        ],
    ]);
```

Repeated calls append extensions and recursively merge configuration maps;
list values such as attribute allowlists are replaced rather than appended.
The dedicated `withHtmlInput()` value takes precedence over an `html_input`
key in generic League configuration.

## Version 1 compatibility API

The main version 1 entry point remains available in version 2:

```php
<?php

use Jidaikobo\MarkdownExtra;

require __DIR__ . '/vendor/autoload.php';

MarkdownExtra::setTargetUrl('https://example.com');
MarkdownExtra::setReplacePath('/var/www/example.com/public');

$html = MarkdownExtra::defaultTransform($markdown);
```

The instance form also remains available:

```php
$parser = new MarkdownExtra();
$html = $parser->transform($markdown);
```

The compatibility facade and the recommended API use the same League
CommonMark converter and Jidaikobo extension internally. New applications
should prefer `MarkdownConverter`, because the static compatibility settings
are shared process state. The facade also retains version 1's raw-HTML
`allow` behavior; use the recommended API for a safe default.

Compatibility has deliberate limits. Version 2 does not preserve:

- inheritance from `Michelf\MarkdownExtra`;
- Michelf-specific public parser properties; or
- byte-for-byte identical HTML, including whitespace and attribute order.

The package instead preserves the documented custom syntax, destinations,
document structure, and accessibility semantics. See the
[migration guide](UPGRADING.md) for details.

## Cheat sheets

- [English Markdown cheat sheet](docs/cheatsheet.md)
- [日本語Markdownチートシート](docs/cheatsheet-ja.md)

Each document places its Markdown source immediately before the rendered
example. Four generated static HTML pages provide Pico CSS and Bootstrap 5
presentations in both languages; viewing them does not execute PHP.

## Enabled League extensions

Heading permalinks are enabled for heading levels 1 through 6. Each permalink
is a keyboard-focusable link whose accessible name is the corresponding
heading text; the fragment ID is applied to the heading itself. Add `[TOC]`
on its own line to generate a table of contents at that position. The table
of contents includes heading levels 2 through 4 and is limited to 100 entries
per document. A document without the placeholder does not receive a table of
contents automatically.

## Custom Markdown syntax

### Notes, asides, and disclosure blocks

Use a fenced note for ancillary information. The optional note variant is
`info` (the default), `warn`, or `alert`. All three render with `role="note"`;
the variant changes a fixed CSS class only. In particular, the static `alert`
variant does not create an ARIA live-region alert.

```markdown
::: note info "Reference information"
This note can contain **normal Markdown**, lists, links, and code blocks.
:::
```

```html
<div class="note note-info" role="note" aria-label="Reference information">
  <p class="note-label">Reference information</p>
  <p>This note can contain <strong>normal Markdown</strong>, lists, links, and code blocks.</p>
</div>
```

The quoted visible title is optional. When present, the same text is displayed
and used as the container's `aria-label`. This avoids generated ID collisions
when independently converted fragments are combined. Use an `aside` fence for
content tangentially related to the surrounding content:

```markdown
::: aside "Related information"
This becomes a native `aside` element.
:::
```

Use `details` for content which readers can expand and collapse. Its optional
title becomes the native `summary`; the default summary is `Details`.

```markdown
::: details "More details"
This becomes the body of a native `details` element.
:::
```

The opening and closing fences may contain three or more colons. Use a longer
outer fence when nesting these containers.

### Column and row headers

Normal table header cells receive `scope="col"`. Add a trailing colon to a
cell to turn it into a row header with `scope="row"`:

```markdown
| Name: | Age | City          |
|-------|-----|---------------|
| Alice:| 30  | New York      |
| Bob:  | 25  | San Francisco |
```

The colon is a syntax marker and is not included in the rendered cell text.

### Table captions and attributes

The preferred syntax is an emphasized paragraph immediately before the table,
with no blank line between them:

```markdown
*Results for the current period*
| Name  | Value |
|-------|-------|
| Alice | 10    |
```

Without the Jidaikobo extension, this remains readable as an emphasized
paragraph followed by a table. A blank line keeps the emphasized paragraph
separate and prevents caption conversion.

The version 1 syntax, where a table row begins with a colon, remains supported
for backward compatibility. To avoid consuming ordinary data, it is recognized
only on the final body row and only when every cell after the first is empty:

```markdown
| Name  | Value |
|-------|-------|
| Alice | 10    |
|: Results for the current period
```

League CommonMark attributes can follow the table without preventing caption
generation:

```markdown
*Results*
| Name  | Value |
|-------|-------|
| Alice | 10    |
{#results .summary}
```

This produces a `table` with the requested ID and class and a `caption` as its
first child.

### Figures and figure captions

An image on its own line followed immediately by an emphasized caption is
converted into `figure` and `figcaption`:

```markdown
![Example](<files/example.svg?variant=(blue)> "Image description"){#example .image}
*A caption with a [link](https://example.com/details), **strong text**, and `code`*
```

The caption is represented as AST children, so links, emphasis, strong text,
and inline code remain structurally nested. Figure-like text inside fenced or
indented code blocks is not converted.

### File type and size

When a link resolves to a readable file below the configured document root,
its extension and human-readable size are appended to the link text:

```markdown
[Download the report](/files/report.pdf)
```

```html
<a href="https://example.com/files/report.pdf">Download the report (pdf, 1.2 MB)</a>
```

Query strings and fragments do not interfere with local file resolution.
Common image types, including SVG, WebP, and AVIF, do not receive a metadata
suffix. Extensionless files also remain ordinary links because no reliable
file type can be displayed. Markdown images are not processed as download
links.

### Root-relative links

When a base URL is configured, link destinations beginning with one `/` are
completed using that URL. Protocol-relative destinations beginning with `//`
remain unchanged.

## Security behavior

- Unsafe link schemes are rejected by League CommonMark.
- Markdown attributes are limited to `id`, `class`, `lang`, `title`, and `rel`.
- Markdown nesting and delimiter counts have explicit limits.
- Local files are resolved with canonical paths and must remain below the
  configured document root.
- URL scheme, host, and port must match the configured base URL before local
  file metadata is read.
- The new API rejects ambiguous base URLs and document roots which resolve to
  the filesystem root.

The recommended API escapes raw HTML by default. The version 1 compatibility
facade continues to allow raw HTML to avoid silently changing existing output.
Applications using the facade with untrusted Markdown must sanitize the
rendered HTML, or migrate that conversion to the recommended API and select
the `escape` or `strip` policy.

## Reusable cheat-sheet fragments

The package includes CSS-free English and Japanese HTML fragments generated
from the same Markdown sources as the preview pages. Each fragment begins with
the visible contents label and ends with the final help section; it does not
contain `html`, `body`, `main`, a page title, or CSS.

Use the PSR-4 API to place the Japanese help inside a landmark chosen by the
host application:

```php
use Jidaikobo\Markdown\CheatSheet;

echo '<main>';
echo CheatSheet::getHtml(CheatSheet::LANGUAGE_JAPANESE);
echo '</main>';
```

English is available as `CheatSheet::LANGUAGE_ENGLISH`. Arbitrary language
paths are not accepted. The returned HTML is a trusted, generated package
resource; it does not convert application-supplied Markdown.

Sample image and download URLs use the relative `files/` directory by default.
A CMS may provide an absolute HTTP(S) URL or a single-slash root-relative URL
for assets:

```php
$html = CheatSheet::getHtml(
    CheatSheet::LANGUAGE_JAPANESE,
    '/assets/php-markdown',
    'cms-markdown-help'
);
```

This changes sample URLs to `/assets/php-markdown/files/...`. Copy only the
required files from `resources/cheatsheet/files/` to that public location;
serving the Composer `vendor` directory directly is not recommended. The third
argument namespaces heading IDs and their TOC and permalink destinations,
avoiding collisions with an existing CMS page. It must begin with an ASCII
letter and contain only letters, digits, `_`, or `-`.

The generated HTML fragments and their small sample assets are also
available together in `resources/cheatsheet/` for applications which do not
call the PHP API. If the fragment language differs from the surrounding page,
the host application should put it in an element with the appropriate `lang`
attribute.

Pico CSS and Bootstrap remain repository preview profiles. They help evaluate
the output after cloning the repository, but they are not dependencies or
presentation requirements of the reusable fragments.

## Browser examples

Install dependencies and start PHP's built-in web server from the repository
root:

```bash
php -S 127.0.0.1:8000 -t examples
```

Then compare the sample entry points:

- <http://127.0.0.1:8000/> uses the version 1 compatibility facade.
- <http://127.0.0.1:8000/index-v2.php> uses the recommended version 2 API.
- <http://127.0.0.1:8000/index-commonmark.php> uses only League CommonMark's
  Core, Table, Attributes, Heading Permalink, and Table of Contents extensions
  as a graceful-degradation baseline.
- <http://127.0.0.1:8000/index-pico.php> uses the recommended version 2 API
  and applies the locally stored Pico CSS 2.1.1 Classless stylesheet without
  custom table or figure styles.
- <http://127.0.0.1:8000/index-bootstrap.php> applies locally stored Bootstrap
  5.3.8 CSS and a demo-only AST extension which adds Bootstrap classes to
  tables and figures. It does not define a public Bootstrap integration API.
- <http://127.0.0.1:8000/cheatsheet-pico.html> renders the English Pico CSS
  cheat sheet.
- <http://127.0.0.1:8000/cheatsheet-pico-ja.html> renders the Japanese Pico CSS
  cheat sheet.
- <http://127.0.0.1:8000/cheatsheet-bootstrap.html> renders the English
  Bootstrap cheat sheet.
- <http://127.0.0.1:8000/cheatsheet-bootstrap-ja.html> renders the Japanese
  Bootstrap cheat sheet.

The first five pages render the same `examples/sample.md`. The cheat-sheet
files are static HTML generated from the corresponding Markdown files in `docs/`.

Regenerate the static cheat sheets after editing their Markdown sources:

```bash
composer build-cheatsheets
```

## Development

Run regression checks:

```bash
composer test
```

Run static analysis and coding-standard checks:

```bash
composer phpstan
composer codestyle
composer compatibility
```

## Requirements

- PHP 7.4 or later
- `ext-mbstring`
- `league/commonmark` 2.10 or later within the supported 2.x series

## Version support

- Version 2 is developed on `main`.
- The current stable version 2 release is `2.0.1`.
- Version 1 maintenance is isolated on the `1.x` branch.
- Applications using `^1.0` do not update automatically to version 2.

The [version 1 README snapshot](docs/README-v1.md) is retained for reference
and is not maintained alongside version 2 documentation. The immutable
[`v1.0.9` tag](https://github.com/jidaikobo-shibata/php-markdown/tree/v1.0.9)
contains the complete released version 1 source and documentation.

## License

This project is licensed under the [MIT License](LICENSE).

## Links

- [jidaikobo/php-markdown on Packagist](https://packagist.org/packages/jidaikobo/php-markdown)
- [jidaikobo-shibata/php-markdown on GitHub](https://github.com/jidaikobo-shibata/php-markdown)

## Acknowledgements

Version 2 is built on [League CommonMark](https://commonmark.thephpleague.com/).
Versions 1.x were built on Michel Fortin's
[`michelf/php-markdown`](https://github.com/michelf/php-markdown).
