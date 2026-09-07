# Changelog

All notable changes to this project will be documented in this file.

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
