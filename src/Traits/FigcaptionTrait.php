<?php

namespace Jidaikobo\Traits;

/**
 * additional figcaption processing features.
 */
trait FigcaptionTrait
{
    /**
     * Detects images with captions and transforms them into figure elements.
     *
     * @param string $text The input Markdown text.
     * @return string The text with figure elements.
     */
    protected function processFigures($text)
    {
        // Find two-line figure candidates. Image parsing itself is delegated
        // to Michelf's span gamut so titles, attributes, references, and
        // nested URL parentheses follow the base parser's rules.
        $pattern = '{
            ^[ ]{0,3}(!\[[^\n]*\][^\n]*)[ ]*\n
            [ ]{0,3}\*(.+)\*[ ]*$
        }mx';

        // Callback to replace matches with a protected figure block.
        return preg_replace_callback($pattern, function ($matches) {
            $image = $this->runSpanGamut(trim($matches[1]));
            $imageHtml = trim($this->unhash($image));

            // A line beginning with image-like text is not necessarily a
            // valid standalone image. Leave it untouched unless the base
            // parser produced exactly one img element.
            if (!preg_match('/\A<img\b[^>]*\/?>(?:\s*)\z/s', $imageHtml)) {
                return $matches[0];
            }

            $caption = $this->runSpanGamut(trim($matches[2]));

            $figure = "<figure>\n" .
                      "  $image\n" .
                      "  <figcaption>$caption</figcaption>\n" .
                      "</figure>";

            return "\n\n" . $this->hashBlock($figure) . "\n\n";
        }, $text);
    }
}
