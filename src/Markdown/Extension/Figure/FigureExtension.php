<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Figure;

use Jidaikobo\Markdown\Extension\Figure\Node\Figcaption;
use Jidaikobo\Markdown\Extension\Figure\Node\Figure;
use Jidaikobo\Markdown\Extension\Figure\Renderer\FigcaptionRenderer;
use Jidaikobo\Markdown\Extension\Figure\Renderer\FigureRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

final class FigureExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, new FigureProcessor(), -100);
        $environment->addRenderer(Figure::class, new FigureRenderer());
        $environment->addRenderer(Figcaption::class, new FigcaptionRenderer());
    }
}
