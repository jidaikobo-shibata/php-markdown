<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Link;

use Jidaikobo\Markdown\MarkdownOptions;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

final class LinkEnhancementExtension implements ExtensionInterface
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
            new LinkProcessor($this->options),
            -100
        );
    }
}
