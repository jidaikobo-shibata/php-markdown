# Upgrading from version 1 to version 2

Version 2 replaces the Markdown parsing engine while retaining the package
name, documented custom syntax, and primary version 1 entry point.

This guide describes the changes an application should review before changing
its Composer constraint from `^1.0` to `^2.0`.

## Package installation

Version 1 constraints do not install version 2 automatically:

```json
{
    "require": {
        "jidaikobo/php-markdown": "^1.0"
    }
}
```

After reviewing and testing this guide against the application, install the
stable version 2 series explicitly:

```bash
composer require jidaikobo/php-markdown:^2.0
```

Changing the major-version constraint remains an explicit application decision;
test representative content and integrations before updating production.

## What remains compatible

The following version 1 code remains supported:

```php
use Jidaikobo\MarkdownExtra;

MarkdownExtra::setTargetUrl('https://example.com');
MarkdownExtra::setReplacePath('/var/www/example.com/public');

$html = MarkdownExtra::defaultTransform($markdown);
```

The instance form remains supported as well:

```php
$parser = new MarkdownExtra();
$html = $parser->transform($markdown);
```

Version 2 continues to support the documented Jidaikobo syntax and behavior:

- trailing `:` table cells become row headers with `scope="row"`;
- normal column headers receive `scope="col"`;
- an emphasized paragraph immediately before a table becomes its caption;
- table rows beginning with `:` become captions;
- an image followed by an emphasized caption becomes a figure;
- local download links can display file type and size; and
- link destinations beginning with `/` can be completed from a base URL.

The compatibility facade now delegates to the same League CommonMark engine
as the new API. It does not contain or emulate the old Michelf parser.

The compatibility facade retains raw HTML with the `allow` policy. The
recommended API uses the safer `escape` policy by default. This intentional
difference matters when the same Markdown is compared through both APIs.

The leading emphasized-caption syntax is new in version 2. The table-row
caption syntax from version 1 remains supported, so existing Markdown does not
need to be rewritten immediately.

## Recommended migration

Existing facade calls may be migrated independently. There is no requirement
to rewrite every call before installing version 2.

### Before: shared static configuration

```php
use Jidaikobo\MarkdownExtra;

MarkdownExtra::setTargetUrl($baseUrl);
MarkdownExtra::setReplacePath($documentRoot);

$html = MarkdownExtra::defaultTransform($markdown);
```

### After: converter-specific immutable configuration

```php
use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;

$options = MarkdownOptions::defaults()
    ->withBaseUrl($baseUrl)
    ->withDocumentRoot($documentRoot);

$converter = new MarkdownConverter($options);
$html = $converter->convert($markdown);
```

The method names map as follows:

| Version 1 compatibility API | Recommended version 2 API |
|---|---|
| `setTargetUrl()` | `withBaseUrl()` |
| `setReplacePath()` | `withDocumentRoot()` |
| `defaultTransform()` | `convert()` |
| `transform()` | `convert()` |
| shared static settings | immutable settings per converter |

The new API is preferable when an application renders multiple sites, runs
in a long-lived PHP process, or needs isolated test configuration.

Unlike the compatibility setters, the new API validates URL and filesystem
options immediately. A base URL must be an absolute HTTP(S) URL without user
information, a query, or a fragment. A document root must be an existing
absolute directory and must not resolve to the filesystem root. Invalid input
throws `InvalidArgumentException`; an empty string clears the option. Review
configuration assembled from environment variables before migrating a call.

It also supports per-converter HTML policy and additional League extensions:

```php
use League\CommonMark\Extension\Footnote\FootnoteExtension;

$options = MarkdownOptions::defaults()
    ->withHtmlInput(MarkdownOptions::HTML_INPUT_STRIP)
    ->withLeagueExtension(new FootnoteExtension())
    ->withLeagueConfiguration([
        'table_of_contents' => ['max_heading_level' => 5],
    ]);
```

Available HTML policies are `HTML_INPUT_ESCAPE` (the new API default),
`HTML_INPUT_STRIP`, and `HTML_INPUT_ALLOW`.

## Intentional compatibility breaks

### Michelf inheritance is removed

`Jidaikobo\MarkdownExtra` no longer extends `Michelf\MarkdownExtra`.
Applications must not rely on an `instanceof Michelf\MarkdownExtra` check or
pass the facade where that concrete parent class is required.

Prefer depending on the Jidaikobo converter directly in new code.

### Michelf public parser properties are removed

Code which modifies Michelf-specific properties such as parser gamuts,
configuration fields, or callbacks must be redesigned. Those properties are
implementation details of the engine replaced in version 2.

Do not copy those mutations to the compatibility facade. Open an issue if an
existing customization represents a generally useful extension point.

### HTML is not byte-for-byte identical

League CommonMark and Michelf Markdown Extra can produce equivalent HTML with
different serialization. Expected differences include:

- whitespace and line breaks;
- HTML attribute order;
- code block class names, such as `language-markdown`;
- self-closing element formatting; and
- edge-case Markdown interpretation.

Tests should assert elements, attributes, destinations, and parent-child
relationships instead of comparing a complete HTML string byte for byte.
CSS and JavaScript selectors should likewise depend on semantic elements and
attributes rather than serialization details.

## Markdown and HTML review checklist

Before deploying version 2, verify representative documents containing:

- ordinary headings, paragraphs, lists, links, and code blocks;
- tables with column and row headers;
- tables with captions and table attributes;
- figures whose captions contain links, strong text, and inline code;
- root-relative, absolute, protocol-relative, and query-string URLs;
- download links with and without file metadata; and
- any raw HTML accepted by the application.

The repository browser examples render the same Markdown through both public
APIs and through a League CommonMark baseline:

- `/` uses the compatibility facade;
- `/index-v2.php` uses the recommended version 2 API.
- `/index-commonmark.php` uses only League's Core, Table, and Attributes
  extensions, without the Jidaikobo extension.

The first two pages confirm API equivalence within version 2. The baseline
shows whether custom markers and content remain readable when the Jidaikobo
extension is absent. To investigate parsing differences from the released
version 1 engine, compare against the immutable `v1.0.9` tag or an application
test environment pinned to `^1.0`.

## Security-related behavior

Version 2 configures League CommonMark to reject unsafe link schemes and limit
Markdown nesting and delimiter counts. Markdown-provided attributes use an
allowlist.

File metadata resolution still requires the URL origin to match the configured
base URL. The resolved canonical file must be readable and contained by the
configured document root. Encoded traversal, host-prefix lookalikes, and
symlink escapes do not expose file metadata.

The recommended API also canonicalizes the configured document root and
rejects `/` (or another platform's filesystem root), including paths and
symlinks which resolve to it. The compatibility facade keeps permissive
version 1 setters so existing calls do not begin throwing exceptions.

The recommended API escapes raw HTML by default. The compatibility facade
retains version 1's raw-HTML `allow` behavior, so its rendered output still
requires application-appropriate sanitization when Markdown is untrusted.
Applications can select `escape`, `strip`, or `allow` per converter through
`MarkdownOptions::withHtmlInput()`.

## Version 1 maintenance and documentation

Version 1 maintenance lives on the `1.x` branch. Composer constraints using
`^1.0` remain on that major version.

The repository contains an
[archived version 1 README](docs/README-v1.md). It is a frozen documentation
snapshot, not a second set of current documentation. It will not be updated to
describe version 2 behavior.

The `v1.0.9` Git tag remains the authoritative immutable record of the latest
released version 1 source and documentation.
