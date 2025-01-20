<?php

namespace Jidaikobo;

use Michelf\MarkdownExtra as BaseMarkdownExtra;

/**
 * A custom MarkdownExtra class with additional table processing features.
 *
 * This class extends the Michelf\MarkdownExtra to add support for:
 * - additional table support
 * - additional figcaption support
 */
class MarkdownExtra extends BaseMarkdownExtra
{
    use Traits\TableTrait;
    use Traits\FigcaptionTrait;
    use Traits\SetfilesizeTrait;

    /**
     * Overrides the parent transform method to process figures.
     *
     * @param string $text The Markdown text to be transformed.
     * @return string The transformed text with figures.
     */
    public function transform(string $text): string
    {
        // First, process figures for images with captions.
        $text = $this->processFigures($text);

        // Use the parent class's transform method for the rest.
        return parent::transform($text);
    }
}
