<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Table;

use Jidaikobo\Markdown\Extension\Table\Node\TableCaption;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Extension\Table\TableSection;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;

/**
 * Adds accessible headers and captions to parsed League table nodes.
 */
final class TableProcessor
{
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
                $this->processLegacyCaptionRow($node);
            }
        }

        foreach ($nodes as $node) {
            if ($node instanceof Table) {
                $this->processLeadingCaption($node);
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

    private function processLegacyCaptionRow(TableRow $row): void
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

    private function processLeadingCaption(Table $table): void
    {
        if ($table->firstChild() instanceof TableCaption) {
            return;
        }

        $paragraph = $table->previous();
        if (! $paragraph instanceof Paragraph || ! $this->isDirectlyBefore($paragraph, $table)) {
            return;
        }

        $children = iterator_to_array($paragraph->children(), false);
        if (count($children) !== 1 || ! $children[0] instanceof Emphasis) {
            return;
        }

        $caption = new TableCaption();
        foreach ($children[0]->children() as $child) {
            $caption->appendChild($child);
        }

        $paragraph->detach();
        $table->prependChild($caption);
    }

    private function isDirectlyBefore(Paragraph $paragraph, Table $table): bool
    {
        $paragraphEnd = $paragraph->getEndLine();
        $tableStart = $table->getStartLine();

        return $paragraphEnd !== null && $tableStart !== null && $paragraphEnd + 1 === $tableStart;
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
}
