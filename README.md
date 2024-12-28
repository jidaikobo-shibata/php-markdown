[![MIT](https://custom-icon-badges.herokuapp.com/badge/license-MIT-8BB80A.svg?logo=law&logoColor=white)]()
# jidaikobo/php-markdown

A few additions to the popular [michelf/php-markdown](https://github.com/michelf/php-markdown). This library provides custom enhancements and overrides to the original Markdown parser, tailored for specific needs.

---

## Installation

Install via Composer:

```bash
composer require jidaikobo/php-markdown
```

---

## Usage

Here's how you can use the library in your project:

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
";

$html = MarkdownExtra::defaultTransform($table);

echo $html;
```

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

#### 2. Table Captions

If the last row of the table starts with a colon (:), it will be treated as a `caption`:

```markdown
| Name    | Age | City          |
|---------|-----|---------------|
| Alice   | 30  | New York      |
| Bob     | 25  | San Francisco |
|: This is a caption for the table.
```

---

## Requirements

- PHP 7.4 or higher

---

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT), see the [LICENSE file](https://github.com/jidaikobo-shibata/log/tree/main?tab=MIT-1-ov-file) for details

## Author

- [jidaikobo-shibata](https://github.com/jidaikobo-shibata/)

## Link

- preparing

---

## Acknowledgements

This library builds upon the work of Michel Fortin and his excellent `michelf/php-markdown`. Learn more at the [official repository](https://github.com/michelf/php-markdown).
