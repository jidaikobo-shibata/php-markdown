# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added

- Add immutable options for selecting the raw HTML policy and registering
  additional League CommonMark extensions and configuration.
- Add CSS-free English and Japanese cheat-sheet fragments with a PSR-4 loader
  API and configurable sample-asset base URL for CMS integration.

### Changed

- Escape raw HTML by default in the recommended API while retaining `allow`
  behavior in the version 1 compatibility facade.
- Make heading permalinks keyboard focusable and give them the corresponding
  heading text as an accessible name.
- Restrict legacy table-caption rows to a final row whose remaining cells are
  empty, and require row-header markers to be in the final inline content.
- Keep extensionless local-file links free of file metadata.
- Label notes and asides without generated IDs so independently converted
  fragments can be combined without ID collisions.
- Validate and normalize base URLs and document roots in the recommended API,
  while keeping compatibility setters non-throwing.

### Removed

- Remove the unused `michelf/php-markdown` development dependency from the
  version 2 branch.

## 2.0.0-beta.2 - 2026-09-08

### Added

- Enable League CommonMark heading permalinks and placeholder-based tables of
  contents.
- Add English and Japanese Markdown cheat sheets with source and rendered
  examples, plus generated static Pico CSS and Bootstrap presentations.
- Add fenced `note` blocks with `info`, `warn`, and `alert` presentation
  variants. All use the static-document `note` role and may have a visible,
  accessible label.
- Add fenced native `aside` and `details` blocks with nested Markdown support.

### Changed

- Add Bootstrap presentation classes to the browser example through an
  example-only extension, including accessible `alert-link` colors for links
  inside notes.

## 2.0.0-beta.1 - 2026-09-08

### Added

- An immutable `MarkdownOptions` configuration object.
- A per-instance `Jidaikobo\Markdown\MarkdownConverter` API.
- AST nodes and renderers for table captions and figures.
- Separate browser examples for the compatibility facade and version 2 API.
- A standard League CommonMark baseline example for checking how custom syntax
  degrades when the Jidaikobo extension is disabled.
- A version 2 upgrade guide and a frozen version 1.0.9 README archive.
- A readable leading table-caption syntax using an emphasized paragraph
  immediately before the table.

### Changed

- The Markdown engine is now `league/commonmark` 2.x.
- Custom table, figure, URL, and file metadata behavior is applied to the
  parsed syntax tree instead of by regular-expression HTML rewriting.
- `Jidaikobo\MarkdownExtra` is now a compatibility facade over the new
  converter.
- Jidaikobo processing is divided into table, figure, and link feature
  extensions, each with its own processor and registrations.

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
