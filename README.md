[![MIT](https://custom-icon-badges.herokuapp.com/badge/license-MIT-8BB80A.svg?logo=law&logoColor=white)](https://github.com/jidaikobo-shibata/php-markdown?tab=MIT-1-ov-file)

# jidaikobo/php-markdown

Accessible Markdown extensions built on
[league/commonmark](https://commonmark.thephpleague.com/). The package adds
table scopes and captions, figures, local file metadata, and root-relative URL
completion.

Version 2 uses League CommonMark internally. The package name and the primary
`Jidaikobo\MarkdownExtra` API from version 1 remain available through a
compatibility facade.

## Installation

Install via Composer:

```bash
composer require jidaikobo/php-markdown
```

## Usage

New code should use an explicitly configured converter:

```php
require 'vendor/autoload.php';

use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;

$options = MarkdownOptions::defaults()
    ->withBaseUrl('https://example.com')
    ->withDocumentRoot('/var/www/public_html/example.com');

$converter = new MarkdownConverter($options);
$html = $converter->convert($markdown);
```

The version 1 facade remains supported for existing callers:

```php
require 'vendor/autoload.php';

use Jidaikobo\MarkdownExtra;

$table = "
## heading

| Header 1 | Header 2 |
|----------|----------|
| Row 1   :| Cell 1   |
| Row 2   :| Cell 2   |
|:capt.


| scope row:| scope col |
|-----------|-----------|
| Row 1    :| Row2      |
";

$html = MarkdownExtra::defaultTransform($table);

echo $html;
```

`new MarkdownExtra()->transform($markdown)` is also supported. Version 2 does
not retain inheritance from `Michelf\MarkdownExtra`, Michelf's public parser
properties, or byte-for-byte identical HTML output.

### Custom Enhancements

This library adds specific parsing behaviors:

#### 1. Row Headers in Tables

By adding a colon (:) at the end of a cell, you can mark it as a row header (`th`):

```markdown
| Name     | Age | City       |
|----------|-----|------------|
| Alice   :| 30  | New York   |
| Bob     :| 25  | San Francisco |
```

You can change the scope of `th` to row by adding a colon (:) to the end of the header cell:

```markdown
| Values  :| Age | City       |
|----------|-----|------------|
| Alice   :| 30  | New York   |
| Bob     :| 25  | San Francisco |
```

#### 2. Table Captions and Attributes

If the last row of the table starts with a colon (:), it will be treated as a `caption`:

```markdown
| Name    | Age | City          |
|---------|-----|---------------|
| Alice   | 30  | New York      |
| Bob     | 25  | San Francisco |
|: This is a caption for the table.
```

Markdown Extra attributes immediately following a table are applied to the
`table` element without preventing caption generation:

```markdown
| Name  | Value |
|-------|-------|
| Alice | 10    |
|: Results
{#results .summary}
```

#### 3. Add file type and size to Link text

When the link destination is a local file, the file type and file size are
added to the link text. Configure the relationship between a public URL and
its document root:

```php
$options = MarkdownOptions::defaults()
    ->withBaseUrl('https://example.com')
    ->withDocumentRoot('/var/www/public_html/example.com');
```

```markdown
[link text](https://example.com/files/example.zip)
```

```HTML
<a href="https://example.com/files/example.zip">link text (zip, 1.2 MB)</a>
```

The resolved path must remain below the configured document root. Query
strings and fragments are ignored when resolving the local file. Common image
formats, including SVG, WebP, and AVIF, do not receive a size suffix.

#### 4. figcaption

An image on its own line followed by an emphasized caption is converted into
a figure. Inline Markdown inside the caption is supported.

```markdown
![Example](https://example.com/files/example.jpg "Image title"){.example-image}
*A caption with a [link](https://example.com/details) and **strong text***
```

```HTML
<figure>
  <img src="https://example.com/files/example.jpg" alt="Example" title="Image title" class="example-image" />
  <figcaption>A caption with a <a href="https://example.com/details">link</a> and <strong>strong text</strong></figcaption>
</figure>
```

Fenced and indented code blocks are protected and are not converted to
figures.

#### 5. Root-relative URLs

When `withBaseUrl()` (or the compatibility `setTargetUrl()`) is configured,
link URLs beginning with a single `/` are completed using that base URL.
Protocol-relative URLs beginning with `//` are left unchanged.

## Browser Example

The repository includes a self-contained browser example. After installing
the Composer dependencies, start PHP's built-in web server from the project
root:

```bash
php -S 127.0.0.1:8000 -t examples
```

Open <http://127.0.0.1:8000/> in a browser.

## Development

Run the regression checks with:

```bash
composer test
```

Run static analysis with:

```bash
composer phpstan
```

## Requirements

- PHP 7.4 or higher

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT), see the [LICENSE file](https://github.com/jidaikobo-shibata/php-markdown?tab=MIT-1-ov-file) for details

## Author

- [jidaikobo-shibata](https://github.com/jidaikobo-shibata/)

## Link

- [jidaikobo/php-markdown - Packagist](https://packagist.org/packages/jidaikobo/php-markdown)
- [jidaikobo-shibata/php-markdown - GitHub](https://github.com/jidaikobo-shibata/php-markdown)

## Acknowledgements

Version 2 is built on [League CommonMark](https://commonmark.thephpleague.com/).
Versions 1.x were built on Michel Fortin's
[`michelf/php-markdown`](https://github.com/michelf/php-markdown).
