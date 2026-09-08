<?php

declare(strict_types=1);

namespace Jidaikobo\Examples\Support;

use Jidaikobo\Markdown\Extension\Figure\FigureExtension;
use Jidaikobo\Markdown\Extension\Link\LinkEnhancementExtension;
use Jidaikobo\Markdown\Extension\Table\AccessibleTableExtension;
use Jidaikobo\Markdown\MarkdownOptions;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

final class Bootstrap5ExampleConverter
{
    public static function create(MarkdownOptions $options): MarkdownConverter
    {
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
        $environment->addExtension(new AccessibleTableExtension());
        $environment->addExtension(new FigureExtension());
        $environment->addExtension(new LinkEnhancementExtension($options));
        $environment->addExtension(new Bootstrap5Extension());

        return new MarkdownConverter($environment);
    }
}
