<?php

declare(strict_types=1);

namespace Jidaikobo\Examples\Support;

use Jidaikobo\Markdown\Extension\Container\ContainerExtension;
use Jidaikobo\Markdown\Extension\Figure\FigureExtension;
use Jidaikobo\Markdown\Extension\Link\LinkEnhancementExtension;
use Jidaikobo\Markdown\Extension\Table\AccessibleTableExtension;
use Jidaikobo\Markdown\MarkdownOptions;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkProcessor;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsBuilder;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
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
            'heading_permalink' => [
                'insert' => HeadingPermalinkProcessor::INSERT_AFTER,
            ],
            'table_of_contents' => [
                'position' => TableOfContentsBuilder::POSITION_PLACEHOLDER,
                'placeholder' => '[TOC]',
                'min_heading_level' => 2,
                'max_heading_level' => 4,
                'max_placeholder_entries' => 100,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new TableOfContentsExtension());
        $environment->addExtension(new ContainerExtension());
        $environment->addExtension(new AccessibleTableExtension());
        $environment->addExtension(new FigureExtension());
        $environment->addExtension(new LinkEnhancementExtension($options));
        $environment->addExtension(new Bootstrap5Extension());

        return new MarkdownConverter($environment);
    }
}
