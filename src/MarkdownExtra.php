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

    public function __construct()
    {
        // Run after fenced and indented code blocks have been protected.
        $this->block_gamut['processFigures'] = 55;

        parent::__construct();
    }
}
