# Markdown Cheat Sheet

This sheet places Markdown source next to the result rendered by
`jidaikobo/php-markdown`. Open the bundled browser example to see all custom
syntax transformed; repository viewers which do not support these extensions
will intentionally show the original text.

**Contents**

[TOC]

## Basic Markdown

### Paragraphs and line breaks

Markdown source:

```markdown
This is one paragraph.

This is another paragraph.
This line remains in the same paragraph.

Add a backslash at the end of a line\
to create a hard line break.
```

Rendered result:

This is one paragraph.

This is another paragraph.
This line remains in the same paragraph.

Add a backslash at the end of a line\
to create a hard line break.

### Headings

Markdown source:

```markdown
# Level 1 heading
## Level 2 heading
### Level 3 heading
```

Rendered result:

#### Rendered level 4 heading

The page title above demonstrates level 1, and the section hierarchy
demonstrates levels 2 and 3. Heading permalink links are added automatically.

### Emphasis

Markdown source:

```markdown
*Emphasis*, **strong importance**, and ***both***.

Use ~~strikethrough~~ only when a GFM extension providing it is enabled.
```

Rendered result:

*Emphasis*, **strong importance**, and ***both***.

Strikethrough is shown as source here because it is not currently enabled by
this package.

### Links and images

Markdown source:

```markdown
[Example link](https://example.com/ "Optional title")

![A blue square](files/sample-image.svg "Sample image")
```

Rendered result:

[Example link](https://example.com/ "Optional title")

![A blue square](files/sample-image.svg "Sample image")

Use meaningful alternative text for informative images. Use empty alternative
text, `![](image.svg)`, only for genuinely decorative images.

### Unordered and ordered lists

Markdown source:

```markdown
- First item
- Second item
  - Nested item

1. First step
2. Second step
```

Rendered result:

- First item
- Second item
  - Nested item

1. First step
2. Second step

### Block quotations

Markdown source:

```markdown
> Quoted text can contain **other Markdown**.
>
> It can contain multiple paragraphs.
```

Rendered result:

> Quoted text can contain **other Markdown**.
>
> It can contain multiple paragraphs.

Use quotations for content quoted from another source, not merely to indent or
decorate text.

### Inline code and code blocks

Markdown source:

````markdown
Use `inline code` for a short fragment.

```php
echo 'A fenced code block';
```
````

Rendered result:

Use `inline code` for a short fragment.

```php
echo 'A fenced code block';
```

### Thematic breaks

Markdown source:

```markdown
---
```

Rendered result:

---

### Tables

Markdown source:

```markdown
| Name | Value |
| --- | ---: |
| Alpha | 10 |
| Beta | 20 |
```

Rendered result:

| Name | Value |
| --- | ---: |
| Alpha | 10 |
| Beta | 20 |

### Attributes

League CommonMark's Attributes extension is enabled. The package allows `id`,
`class`, `lang`, `title`, and `rel` attributes.

Markdown source:

```markdown
[Japanese page](https://example.com/){.external lang=ja rel=noreferrer}
```

Rendered result:

[Japanese page](https://example.com/){.external lang=ja rel=noreferrer}

## Heading Permalinks and Table of Contents

Heading permalinks are generated automatically for levels 1 through 6. Each
symbol is a keyboard-focusable link whose accessible name is the corresponding
heading text, while each heading remains a valid fragment destination.

A table of contents is generated only where this placeholder occurs:

```markdown
[TOC]
```

The table of contents near the top of this document is the rendered result. It
includes heading levels 2 through 4 and is limited to 100 entries per document.

## jidaikobo/php-markdown Extensions

### Accessible column and row headers

Normal table headings receive `scope="col"`. Add a trailing colon to a cell to
make it a row heading with `scope="row"`; the marker is removed from the HTML.

Markdown source:

```markdown
| Day | Opens | Closes |
| --- | --- | --- |
| Monday: | 09:00 | 17:00 |
| Tuesday: | 10:00 | 18:00 |
```

Rendered result:

| Day | Opens | Closes |
| --- | --- | --- |
| Monday: | 09:00 | 17:00 |
| Tuesday: | 10:00 | 18:00 |

### Table captions

Place one emphasized paragraph immediately before a table, without a blank
line. Inline links and code remain structured inside the caption.

Markdown source:

```markdown
*Population by area in 2026*
| Area | Population |
| --- | ---: |
| East | 12 |
| West | 8 |
```

Rendered result:

*Population by area in 2026*
| Area | Population |
| --- | ---: |
| East | 12 |
| West | 8 |

The version 1 caption-row syntax remains available for compatibility:

```markdown
| Name | Value |
| --- | --- |
| Alpha | 10 |
|: Legacy caption
```

### Figures and figure captions

An image immediately followed by an emphasized line becomes `figure` and
`figcaption`. The caption supports inline Markdown.

Markdown source:

```markdown
![A blue square](files/sample-image.svg "Sample image")
*A caption with a [link](https://example.com/details), **importance**, and `code`*
```

Rendered result:

![A blue square](files/sample-image.svg "Sample image")
*A caption with a [link](https://example.com/details), **importance**, and `code`*

### Root-relative links and local file metadata

When the converter has a base URL, a destination beginning with one `/` is
completed from that URL. When it also resolves safely below the configured
document root, non-image files with an extension receive their type and size.
Extensionless files remain ordinary links.

Markdown source:

```markdown
[Small text download](/files/download.txt)
```

Rendered result in the bundled browser example:

[Small text download](/files/download.txt)

### Notes

Notes render as `div[role="note"]`. `info`, `warn`, and `alert` are presentation
variants; even the static `alert` variant does not become `role="alert"`.
The package outputs stable classes but does not provide CSS for notes. Add
styles appropriate to your application when a visual distinction is needed.

Markdown source:

```markdown
::: note info "Information"
This note contains a [link](https://example.com/) and **structured Markdown**.
:::

::: note warn "Caution"
Read the [checking procedure](https://example.com/check) before changing it.
:::

::: note alert "Important warning"
This operation cannot be undone. Read the [safety notice](https://example.com/caution).
:::
```

Rendered result:

::: note info "Information"
This note contains a [link](https://example.com/) and **structured Markdown**.
:::

::: note warn "Caution"
Read the [checking procedure](https://example.com/check) before changing it.
:::

::: note alert "Important warning"
This operation cannot be undone. Read the [safety notice](https://example.com/caution).
:::

### Asides

Use an aside for content which is tangentially related to its surroundings.

Markdown source:

```markdown
::: aside "Related information"
This becomes a native `aside` element.
:::
```

Rendered result:

::: aside "Related information"
This becomes a native `aside` element.
:::

### Disclosure with details

The title becomes a native `summary`, and the body remains structured
Markdown. Without a title, the summary defaults to `Details`. Browsers provide
the disclosure behavior; its presentation depends on the browser and the CSS
used by the application.

Markdown source:

```markdown
::: details "Show the explanation"
- The first detail
- The second detail
:::
```

Rendered result:

::: details "Show the explanation"
- The first detail
- The second detail
:::

### Nested containers

Use a longer fence for the outer container:

```markdown
:::: aside "Outer container"
::: note info "Inner note"
Nested content.
:::
::::
```

## Accessibility and Security Notes

- Do not rely on color alone to distinguish note variants; keep their visible
  labels meaningful.
- Use `aside` only for tangential content and `details` only when collapsing
  content does not prevent readers from discovering essential information.
- Link text should describe its destination outside the surrounding sentence.
- Table captions describe the table as a whole; row headings identify values
  across each row.
