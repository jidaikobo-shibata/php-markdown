<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Link;

use Jidaikobo\Markdown\MarkdownOptions;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Inline\Text;

/**
 * Completes root-relative links and adds safe local file metadata.
 */
final class LinkProcessor
{
    private MarkdownOptions $options;

    private LocalFileResolver $fileResolver;

    public function __construct(MarkdownOptions $options)
    {
        $this->options = $options;
        $this->fileResolver = new LocalFileResolver($options);
    }

    public function __invoke(DocumentParsedEvent $event): void
    {
        $nodes = iterator_to_array($event->getDocument()->iterator(), false);

        foreach ($nodes as $node) {
            if ($node instanceof Link) {
                $this->processLink($node);
            }
        }
    }

    private function processLink(Link $link): void
    {
        $url = $link->getUrl();
        $baseUrl = $this->options->getBaseUrl();

        if ($baseUrl !== '' && strncmp($url, '/', 1) === 0 && strncmp($url, '//', 2) !== 0) {
            $url = $baseUrl . $url;
            $link->setUrl($url);
        }

        $localPath = $this->fileResolver->resolve($url);
        if ($localPath === null || $this->isImage($localPath)) {
            return;
        }

        $size = filesize($localPath);
        if ($size === false) {
            return;
        }

        $extension = strtolower((string) pathinfo($localPath, PATHINFO_EXTENSION));
        $link->appendChild(new Text(sprintf(
            ' (%s, %s)',
            $extension,
            $this->formatFileSize($size)
        )));
    }

    private function isImage(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, [
            'png', 'apng', 'jpg', 'jpeg', 'jpe', 'jfif', 'pjpeg', 'pjp',
            'gif', 'bmp', 'tif', 'tiff', 'ico', 'svg', 'svgz', 'webp', 'avif',
        ], true);
    }

    private function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        $displaySize = (float) $size;

        while ($displaySize >= 1024 && $unitIndex < count($units) - 1) {
            $displaySize /= 1024;
            $unitIndex++;
        }

        return round($displaySize, 1) . ' ' . $units[$unitIndex];
    }
}
