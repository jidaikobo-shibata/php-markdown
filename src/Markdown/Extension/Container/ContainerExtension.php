<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Container;

use Jidaikobo\Markdown\Extension\Container\Node\Container;
use Jidaikobo\Markdown\Extension\Container\Parser\ContainerStartParser;
use Jidaikobo\Markdown\Extension\Container\Renderer\ContainerRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

final class ContainerExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(new ContainerStartParser(), 100);
        $environment->addRenderer(Container::class, new ContainerRenderer());
    }
}
