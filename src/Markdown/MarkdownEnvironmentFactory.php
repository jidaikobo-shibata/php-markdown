<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown;

use Jidaikobo\Markdown\Extension\Container\ContainerExtension;
use Jidaikobo\Markdown\Extension\Figure\FigureExtension;
use Jidaikobo\Markdown\Extension\Heading\AccessibleHeadingPermalinkExtension;
use Jidaikobo\Markdown\Extension\Link\LinkEnhancementExtension;
use Jidaikobo\Markdown\Extension\Table\AccessibleTableExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkProcessor;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsBuilder;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

final class MarkdownEnvironmentFactory
{
    public static function create(?MarkdownOptions $options = null): Environment
    {
        $options = $options ?? MarkdownOptions::defaults();
        $configuration = ConfigurationMerger::merge([
            'html_input' => MarkdownOptions::HTML_INPUT_ESCAPE,
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'max_delimiters_per_line' => 1000,
            'attributes' => [
                'allow' => ['id', 'class', 'lang', 'title', 'rel'],
            ],
            'heading_permalink' => [
                'insert' => HeadingPermalinkProcessor::INSERT_AFTER,
                'apply_id_to_heading' => true,
                'aria_hidden' => false,
            ],
            'table_of_contents' => [
                'position' => TableOfContentsBuilder::POSITION_PLACEHOLDER,
                'placeholder' => '[TOC]',
                'min_heading_level' => 2,
                'max_heading_level' => 4,
                'max_placeholder_entries' => 100,
            ],
        ], $options->getLeagueConfiguration());

        // withHtmlInput() is the explicit security API and takes precedence over
        // a duplicate html_input entry in the generic League configuration.
        $configuration['html_input'] = $options->getHtmlInput();

        $environment = new Environment($configuration);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new AccessibleHeadingPermalinkExtension());
        $environment->addExtension(new TableOfContentsExtension());
        $environment->addExtension(new ContainerExtension());
        $environment->addExtension(new AccessibleTableExtension());
        $environment->addExtension(new FigureExtension());
        $environment->addExtension(new LinkEnhancementExtension($options));

        foreach ($options->getLeagueExtensions() as $extension) {
            $environment->addExtension($extension);
        }

        return $environment;
    }
}
