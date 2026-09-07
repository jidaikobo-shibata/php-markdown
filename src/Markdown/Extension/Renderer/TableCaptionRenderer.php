<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Renderer;

use Jidaikobo\Markdown\Extension\Node\TableCaption;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final class TableCaptionRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        TableCaption::assertInstanceOf($node);

        return new HtmlElement('caption', [], $childRenderer->renderNodes($node->children()));
    }
}
