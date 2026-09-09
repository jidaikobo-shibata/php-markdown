<?php

declare(strict_types=1);

namespace Jidaikobo\Examples\Support;

use Jidaikobo\Markdown\MarkdownEnvironmentFactory;
use Jidaikobo\Markdown\MarkdownOptions;
use League\CommonMark\MarkdownConverter;

final class Bootstrap5ExampleConverter
{
    public static function create(MarkdownOptions $options): MarkdownConverter
    {
        $options = $options->withLeagueExtension(new Bootstrap5Extension());

        return new MarkdownConverter(MarkdownEnvironmentFactory::create($options));
    }
}
