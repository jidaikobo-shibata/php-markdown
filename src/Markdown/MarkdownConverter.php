<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown;

use Jidaikobo\Markdown\Extension\JidaikoboExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter as LeagueMarkdownConverter;

/**
 * Converts Markdown using the League CommonMark based version 2 engine.
 */
final class MarkdownConverter
{
    private LeagueMarkdownConverter $converter;

    public function __construct(?MarkdownOptions $options = null)
    {
        $options = $options ?? MarkdownOptions::defaults();
        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'max_delimiters_per_line' => 1000,
            'attributes' => [
                'allow' => ['id', 'class', 'lang', 'title', 'rel'],
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new JidaikoboExtension($options));

        $this->converter = new LeagueMarkdownConverter($environment);
    }

    public function convert(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
