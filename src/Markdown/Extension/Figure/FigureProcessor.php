<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Figure;

use Jidaikobo\Markdown\Extension\Figure\Node\Figcaption;
use Jidaikobo\Markdown\Extension\Figure\Node\Figure;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;

/**
 * Converts a standalone image and emphasized next line into a figure.
 */
final class FigureProcessor
{
    public function __invoke(DocumentParsedEvent $event): void
    {
        $nodes = iterator_to_array($event->getDocument()->iterator(), false);

        foreach ($nodes as $node) {
            if ($node instanceof Paragraph) {
                $this->processFigure($node);
            }
        }
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

        $figure = new Figure();
        $figcaption = new Figcaption();

        $figure->appendChild($children[0]);
        foreach ($children[2]->children() as $child) {
            $figcaption->appendChild($child);
        }
        $figure->appendChild($figcaption);
        $paragraph->replaceWith($figure);
    }
}
