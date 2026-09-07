<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension;

use Jidaikobo\Markdown\Extension\Node\Figcaption;
use Jidaikobo\Markdown\Extension\Node\Figure;
use Jidaikobo\Markdown\Extension\Node\TableCaption;
use Jidaikobo\Markdown\MarkdownOptions;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Extension\Table\TableSection;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\Block\Paragraph;

/**
 * Applies Jidaikobo syntax to the parsed node tree.
 */
final class MarkdownProcessor
{
    private MarkdownOptions $options;

    private LocalFileResolver $fileResolver;

    public function __construct(MarkdownOptions $options)
    {
        $this->options = $options;
        $this->fileResolver = new LocalFileResolver($options);
    }

    public function __invoke(DocumentParsedEvent $event): void
    {
        $nodes = iterator_to_array($event->getDocument()->iterator(), false);

        foreach ($nodes as $node) {
            if ($node instanceof TableCell) {
                $this->processTableCell($node);
            }
        }

        foreach ($nodes as $node) {
            if ($node instanceof TableRow) {
                $this->processCaptionRow($node);
            }
        }

        foreach ($nodes as $node) {
            if ($node instanceof Paragraph) {
                $this->processFigure($node);
            }
        }

        foreach ($nodes as $node) {
            if ($node instanceof Link) {
                $this->processLink($node);
            }
        }
    }

    private function processTableCell(TableCell $cell): void
    {
        $section = $cell->parent() instanceof TableRow ? $cell->parent()->parent() : null;
        $isRowHeader = $this->removeTrailingColon($cell);

        if ($isRowHeader) {
            $cell->setType(TableCell::TYPE_HEADER);
            $this->setAttribute($cell, 'scope', 'row');
        } elseif ($section instanceof TableSection && $section->isHead()) {
            $this->setAttribute($cell, 'scope', 'col');
        }
    }

    private function processCaptionRow(TableRow $row): void
    {
        $section = $row->parent();
        if (! $section instanceof TableSection || ! $section->isBody()) {
            return;
        }

        $firstCell = $row->firstChild();
        if (! $firstCell instanceof TableCell || ! $this->removeLeadingColon($firstCell)) {
            return;
        }

        $table = $section->parent();
        if (! $table instanceof Table || $table->firstChild() instanceof TableCaption) {
            return;
        }

        $caption = new TableCaption();
        foreach ($firstCell->children() as $child) {
            $caption->appendChild($child);
        }

        $row->detach();
        $table->prependChild($caption);
    }

    private function processFigure(Paragraph $paragraph): void
    {
        $children = iterator_to_array($paragraph->children(), false);
        if (
            count($children) !== 3 ||
            ! $children[0] instanceof Image ||
            ! $children[1] instanceof Newline ||
            ! $children[2] instanceof Emphasis
        ) {
            return;
        }

        $image = $children[0];
        $emphasis = $children[2];
        $figure = new Figure();
        $figcaption = new Figcaption();

        $figure->appendChild($image);
        foreach ($emphasis->children() as $child) {
            $figcaption->appendChild($child);
        }
        $figure->appendChild($figcaption);
        $paragraph->replaceWith($figure);
    }

    private function processLink(Link $link): void
    {
        $url = $link->getUrl();
        $baseUrl = $this->options->getBaseUrl();

        if ($baseUrl !== '' && strncmp($url, '/', 1) === 0 && strncmp($url, '//', 2) !== 0) {
            $url = $baseUrl . $url;
            $link->setUrl($url);
        }

        $localPath = $this->fileResolver->resolve($url);
        if ($localPath === null || $this->isImage($localPath)) {
            return;
        }

        $size = filesize($localPath);
        if ($size === false) {
            return;
        }

        $extension = strtolower((string) pathinfo($localPath, PATHINFO_EXTENSION));
        $link->appendChild(new Text(sprintf(
            ' (%s, %s)',
            $extension,
            $this->formatFileSize($size)
        )));
    }

    private function removeTrailingColon(TableCell $cell): bool
    {
        $text = $this->lastTextDescendant($cell);
        if ($text === null || preg_match('/:+\s*$/u', $text->getLiteral()) !== 1) {
            return false;
        }

        $literal = (string) preg_replace('/:+\s*$/u', '', $text->getLiteral());
        $text->setLiteral(rtrim($literal));

        return true;
    }

    private function removeLeadingColon(TableCell $cell): bool
    {
        $text = $this->firstTextDescendant($cell);
        if ($text === null || preg_match('/^\s*:/u', $text->getLiteral()) !== 1) {
            return false;
        }

        $text->setLiteral((string) preg_replace('/^\s*:\s*/u', '', $text->getLiteral(), 1));

        return true;
    }

    private function firstTextDescendant(Node $node): ?Text
    {
        foreach ($node->children() as $child) {
            if ($child instanceof Text) {
                return $child;
            }
            $text = $this->firstTextDescendant($child);
            if ($text !== null) {
                return $text;
            }
        }

        return null;
    }

    private function lastTextDescendant(Node $node): ?Text
    {
        $children = array_reverse(iterator_to_array($node->children(), false));
        foreach ($children as $child) {
            if ($child instanceof Text) {
                return $child;
            }
            $text = $this->lastTextDescendant($child);
            if ($text !== null) {
                return $text;
            }
        }

        return null;
    }

    private function setAttribute(Node $node, string $name, string $value): void
    {
        $attributes = (array) $node->data->get('attributes');
        $attributes[$name] = $value;
        $node->data->set('attributes', $attributes);
    }

    private function isImage(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, [
            'png', 'apng', 'jpg', 'jpeg', 'jpe', 'jfif', 'pjpeg', 'pjp',
            'gif', 'bmp', 'tif', 'tiff', 'ico', 'svg', 'svgz', 'webp', 'avif',
        ], true);
    }

    private function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        $displaySize = (float) $size;

        while ($displaySize >= 1024 && $unitIndex < count($units) - 1) {
            $displaySize /= 1024;
            $unitIndex++;
        }

        return round($displaySize, 1) . ' ' . $units[$unitIndex];
    }
}
