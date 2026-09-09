<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Heading;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;
use League\CommonMark\Node\RawMarkupContainerInterface;
use League\CommonMark\Node\StringContainerHelper;

final class HeadingPermalinkAccessibilityProcessor
{
    public function __invoke(DocumentParsedEvent $event): void
    {
        foreach ($event->getDocument()->iterator() as $node) {
            if (!$node instanceof HeadingPermalink) {
                continue;
            }

            $heading = $node->parent();
            if (!$heading instanceof Heading) {
                continue;
            }

            $accessibleName = trim(StringContainerHelper::getChildText(
                $heading,
                [RawMarkupContainerInterface::class]
            ));

            if ($accessibleName === '') {
                $accessibleName = 'Permalink';
            }

            $node->data->set('attributes/aria-label', $accessibleName);
        }
    }
}
