<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Table;

use Jidaikobo\Markdown\Extension\Table\Node\TableCaption;
use Jidaikobo\Markdown\Extension\Table\Renderer\TableCaptionRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

final class AccessibleTableExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, new TableProcessor(), -100);
        $environment->addRenderer(TableCaption::class, new TableCaptionRenderer());
    }
}
