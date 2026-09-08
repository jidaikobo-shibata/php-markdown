<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Container\Renderer;

use Jidaikobo\Markdown\Extension\Container\Node\Container;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

final class ContainerRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        Container::assertInstanceOf($node);
        /** @var Container $node */

        $type = $node->getType();
        if ($type === Container::TYPE_DETAILS) {
            return $this->renderDetails($node, $childRenderer);
        }

        return $this->renderSection($node, $childRenderer);
    }

    private function renderDetails(Container $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $attributes = $this->withClasses($node, ['details']);
        $summary = $node->getTitle() !== '' ? $node->getTitle() : 'Details';
        $separator = $childRenderer->getInnerSeparator();
        $contents = $separator . new HtmlElement('summary', [], Xml::escape($summary));
        $contents .= $separator . $childRenderer->renderNodes($node->children()) . $separator;

        return new HtmlElement('details', $attributes, $contents);
    }

    private function renderSection(Container $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $type = $node->getType();
        $classes = [$type];
        $attributes = (array) $node->data->get('attributes');

        if ($type === Container::TYPE_NOTE) {
            $classes[] = 'note-' . $node->getVariant();
        }

        $attributes = $this->withClasses($node, $classes, $attributes);
        if ($type === Container::TYPE_NOTE) {
            $attributes['role'] = 'note';
        }

        $separator = $childRenderer->getInnerSeparator();
        $contents = $separator;

        if ($node->getTitle() !== '') {
            $labelId = 'jidaikobo-' . $type . '-' . (string) $node->getStartLine() . '-label';
            $attributes['aria-labelledby'] = $labelId;
            $contents .= new HtmlElement(
                'p',
                ['id' => $labelId, 'class' => $type . '-label'],
                Xml::escape($node->getTitle())
            );
            $contents .= $separator;
        }

        $contents .= $childRenderer->renderNodes($node->children()) . $separator;

        return new HtmlElement($type === Container::TYPE_ASIDE ? 'aside' : 'div', $attributes, $contents);
    }

    /**
     * @param string[]                    $classes
     * @param array<string, string|bool> $attributes
     *
     * @return array<string, string|bool>
     */
    private function withClasses(Container $node, array $classes, ?array $attributes = null): array
    {
        $attributes = $attributes ?? (array) $node->data->get('attributes');
        $existing = $attributes['class'] ?? '';
        if (is_array($existing)) {
            $existing = implode(' ', $existing);
        }

        $attributes['class'] = trim(implode(' ', $classes) . ' ' . (string) $existing);

        return $attributes;
    }
}
