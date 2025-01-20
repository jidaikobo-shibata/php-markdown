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
        $pattern = '/!\[([^\]]*)\]\(([^)]+)\)\n\*([^\*]+)\*/';

        // Callback to replace matches with figure elements
        return preg_replace_callback($pattern, function ($matches) {
            $alt = htmlspecialchars($matches[1], ENT_QUOTES);
            $url = htmlspecialchars($matches[2], ENT_QUOTES);
            $caption = htmlspecialchars($matches[3], ENT_QUOTES);

            return "<figure>\n" .
                   "  <img src=\"$url\" alt=\"$alt\" />\n" .
                   "  <figcaption>$caption</figcaption>\n" .
                   "</figure>";
        }, $text);
    }
}
