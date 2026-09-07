# Changelog

All notable changes to this project will be documented in this file.

## 2.0.0 - 2026-09-07

### Added

- An immutable `MarkdownOptions` configuration object.
- A per-instance `Jidaikobo\Markdown\MarkdownConverter` API.
- AST nodes and renderers for table captions and figures.
- Separate browser examples for the compatibility facade and version 2 API.
- A version 2 upgrade guide and a frozen version 1.0.9 README archive.

### Changed

- The Markdown engine is now `league/commonmark` 2.x.
- Custom table, figure, URL, and file metadata behavior is applied to the
  parsed syntax tree instead of by regular-expression HTML rewriting.
- `Jidaikobo\MarkdownExtra` is now a compatibility facade over the new
  converter.

### Removed

- Inheritance from `Michelf\MarkdownExtra`.
- Compatibility with Michelf-specific public parser properties.
- The guarantee of byte-for-byte identical HTML output to version 1.

## 1.0.9 - 2026-09-07

### Added

- Browser examples covering custom tables, figures, links, and boundary cases.
- Regression checks runnable with `composer test`.
- Markdown Extra attributes on tables with captions.
- Inline Markdown support inside figcaptions.

### Changed

- Figure parsing now delegates image and inline parsing to `michelf/php-markdown`.
- Figure syntax inside fenced and indented code blocks is left unchanged.
- Inline link attributes are preserved when file metadata processing is enabled.
- SVG, WebP, AVIF, and other common image links no longer receive file metadata.
- Root-relative URL completion leaves protocol-relative URLs unchanged.
- Composer archives exclude local dependencies, logs, and legacy browser files.

### Security

- Local file metadata resolution now compares URL origins and canonical paths.
- Paths outside the configured document root are rejected, including encoded
  traversal paths and paths which escape through symbolic links.
