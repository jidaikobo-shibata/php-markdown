<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Figure\Renderer;

use Jidaikobo\Markdown\Extension\Figure\Node\Figure;
use Jidaikobo\Markdown\HtmlAttributes;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final class FigureRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        Figure::assertInstanceOf($node);
        $separator = $childRenderer->getInnerSeparator();

        return new HtmlElement(
            'figure',
            HtmlAttributes::fromNode($node),
            $separator . $childRenderer->renderNodes($node->children()) . $separator
        );
    }
}
