[![MIT](https://custom-icon-badges.herokuapp.com/badge/license-MIT-8BB80A.svg?logo=law&logoColor=white)](https://github.com/jidaikobo-shibata/php-markdown?tab=MIT-1-ov-file)

# jidaikobo/php-markdown

Accessible Markdown extensions built on
[League CommonMark](https://commonmark.thephpleague.com/).

Version 2 adds accessible table headers and captions, figures with structured
captions, local file metadata, and root-relative URL completion. It uses a
per-converter configuration API while retaining the primary version 1 API as
a compatibility facade.

> Version 2 is currently available as the `2.0.0-beta.1` prerelease and is
> developed on the `main` branch. Test it before using it in production.
> See the [archived version 1 documentation](docs/README-v1.md) when maintaining
> an existing version 1 installation.

## Installation

Install the version 2 beta explicitly with Composer:

```bash
composer require jidaikobo/php-markdown:2.0.0-beta.1
```

A stable version 2 release is not available yet. Composer does not select this
beta for applications constrained to stable releases unless it is requested
explicitly.

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
are shared process state.

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

Heading permalinks are enabled for heading levels 1 through 6. Add `[TOC]` on
its own line to generate a table of contents at that position. The table of
contents includes heading levels 2 through 4 and is limited to 100 entries per
document. A document without the placeholder does not receive a table of
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
<div class="note note-info" role="note" aria-labelledby="jidaikobo-note-1-label">
  <p id="jidaikobo-note-1-label" class="note-label">Reference information</p>
  <p>This note can contain <strong>normal Markdown</strong>, lists, links, and code blocks.</p>
</div>
```

The quoted visible title is optional. When present, it labels the note with
`aria-labelledby`. Use an `aside` fence for content tangentially related to
the surrounding content:

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
for backward compatibility:

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
suffix. Markdown images are not processed as download links.

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

Raw HTML remains enabled for compatibility. Applications rendering untrusted
Markdown should apply an HTML sanitization policy appropriate to their output
context.

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
- The current version 2 release is `2.0.0-beta.1`, not a stable release.
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
