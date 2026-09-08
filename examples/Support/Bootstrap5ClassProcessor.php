<?php

declare(strict_types=1);

namespace Jidaikobo\Examples\Support;

use Jidaikobo\Markdown\Extension\Figure\Node\Figcaption;
use Jidaikobo\Markdown\Extension\Figure\Node\Figure;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Node\Node;

final class Bootstrap5ClassProcessor
{
    public function __invoke(DocumentParsedEvent $event): void
    {
        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Table) {
                $this->addClasses($node, 'table table-striped table-bordered align-middle');
            } elseif ($node instanceof Figure) {
                $this->addClasses($node, 'figure');
            } elseif ($node instanceof Figcaption) {
                $this->addClasses($node, 'figure-caption');
            } elseif ($node instanceof Image && $node->parent() instanceof Figure) {
                $this->addClasses($node, 'figure-img img-fluid rounded');
            }
        }
    }

    private function addClasses(Node $node, string $classes): void
    {
        $attributes = (array) $node->data->get('attributes');
        $existing = $attributes['class'] ?? '';
        $classList = is_array($existing) ? $existing : preg_split('/\s+/', trim((string) $existing));

        if ($classList === false) {
            $classList = [];
        }

        $classList = array_merge($classList, explode(' ', $classes));
        $classList = array_values(array_unique(array_filter($classList)));
        $attributes['class'] = implode(' ', $classList);
        $node->data->set('attributes', $attributes);
    }
}
