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

After reviewing this guide, opt in to the current version 2 beta explicitly:

```bash
composer require jidaikobo/php-markdown:2.0.0-beta.1
```

Do not change a production constraint to `^2.0` until a stable version 2 has
been released and tested for that application.

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
- table rows beginning with `:` become captions;
- an image followed by an emphasized caption becomes a figure;
- local download links can display file type and size; and
- link destinations beginning with `/` can be completed from a base URL.

The compatibility facade now delegates to the same League CommonMark engine
as the new API. It does not contain or emulate the old Michelf parser.

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

Before testing or deploying the version 2 beta, verify representative
documents containing:

- ordinary headings, paragraphs, lists, links, and code blocks;
- tables with column and row headers;
- tables with captions and table attributes;
- figures whose captions contain links, strong text, and inline code;
- root-relative, absolute, protocol-relative, and query-string URLs;
- download links with and without file metadata; and
- any raw HTML accepted by the application.

The repository browser examples render the same Markdown through both public
APIs:

- `/` uses the compatibility facade;
- `/index-v2.php` uses the recommended version 2 API.

These pages confirm API equivalence within version 2. To investigate parsing
differences from the released version 1 engine, compare against the immutable
`v1.0.9` tag or an application test environment pinned to `^1.0`.

## Security-related behavior

Version 2 configures League CommonMark to reject unsafe link schemes and limit
Markdown nesting and delimiter counts. Markdown-provided attributes use an
allowlist.

File metadata resolution still requires the URL origin to match the configured
base URL. The resolved canonical file must be readable and contained by the
configured document root. Encoded traversal, host-prefix lookalikes, and
symlink escapes do not expose file metadata.

Raw HTML remains enabled for compatibility. Continue to sanitize rendered HTML
when Markdown comes from an untrusted source.

## Version 1 maintenance and documentation

Version 1 maintenance lives on the `1.x` branch. Composer constraints using
`^1.0` remain on that major version.

The repository contains an
[archived version 1 README](docs/README-v1.md). It is a frozen documentation
snapshot, not a second set of current documentation. It will not be updated to
describe version 2 behavior.

The `v1.0.9` Git tag remains the authoritative immutable record of the latest
released version 1 source and documentation.
