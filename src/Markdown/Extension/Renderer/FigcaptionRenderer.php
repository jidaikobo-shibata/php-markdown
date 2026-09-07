<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Renderer;

use Jidaikobo\Markdown\Extension\Node\Figcaption;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final class FigcaptionRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        Figcaption::assertInstanceOf($node);

        return new HtmlElement('figcaption', [], $childRenderer->renderNodes($node->children()));
    }
}
