<?php

namespace Jidaikobo\Traits;

/**
 * additional figcaption processing features.
 */
Trait FigcaptionTrait
{
    /**
     * Detects images with captions and transforms them into figure elements.
     *
     * @param string $text The input Markdown text.
     * @return string The text with figure elements.
     */
    protected function processFigures($text)
    {
        // Regex pattern to match images followed by captions
        $pattern = '/!\[([^\]]*)\]\(([^)]+)\)\s*\r?\n\s*\*([^\*]+)\*/m';

        // Callback to replace matches with a protected figure block.
        return preg_replace_callback($pattern, function ($matches) {
            $alt = htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $url = htmlspecialchars($matches[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $caption = htmlspecialchars($matches[3], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $figure = "<figure>\n" .
                      "  <img src=\"$url\" alt=\"$alt\" />\n" .
                      "  <figcaption>$caption</figcaption>\n" .
                      "</figure>";

            return "\n\n" . $this->hashBlock($figure) . "\n\n";
        }, $text);
    }
}
