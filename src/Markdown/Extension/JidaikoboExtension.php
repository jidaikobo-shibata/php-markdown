<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension;

use Jidaikobo\Markdown\Extension\Node\Figcaption;
use Jidaikobo\Markdown\Extension\Node\Figure;
use Jidaikobo\Markdown\Extension\Node\TableCaption;
use Jidaikobo\Markdown\Extension\Renderer\FigcaptionRenderer;
use Jidaikobo\Markdown\Extension\Renderer\FigureRenderer;
use Jidaikobo\Markdown\Extension\Renderer\TableCaptionRenderer;
use Jidaikobo\Markdown\MarkdownOptions;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

final class JidaikoboExtension implements ExtensionInterface
{
    private MarkdownOptions $options;

    public function __construct(MarkdownOptions $options)
    {
        $this->options = $options;
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            new MarkdownProcessor($this->options),
            -100
        );
        $environment->addRenderer(Figure::class, new FigureRenderer());
        $environment->addRenderer(Figcaption::class, new FigcaptionRenderer());
        $environment->addRenderer(TableCaption::class, new TableCaptionRenderer());
    }
}
