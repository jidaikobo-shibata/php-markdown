<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Heading;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

final class AccessibleHeadingPermalinkExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            new HeadingPermalinkAccessibilityProcessor(),
            -200
        );
    }
}
