<?php

declare(strict_types=1);

namespace Jidaikobo\Examples\Support;

use Jidaikobo\Markdown\Extension\Figure\Node\Figcaption;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

final class Bootstrap5Extension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, new Bootstrap5ClassProcessor(), -200);
        $environment->addRenderer(Figcaption::class, new Bootstrap5FigcaptionRenderer(), 10);
    }
}
