<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown;

use League\CommonMark\MarkdownConverter as LeagueMarkdownConverter;

/**
 * Converts Markdown using the League CommonMark based version 2 engine.
 */
final class MarkdownConverter
{
    private LeagueMarkdownConverter $converter;

    public function __construct(?MarkdownOptions $options = null)
    {
        $this->converter = new LeagueMarkdownConverter(
            MarkdownEnvironmentFactory::create($options)
        );
    }

    public function convert(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
