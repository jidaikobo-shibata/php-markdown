<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Link;

use Jidaikobo\Markdown\MarkdownOptions;

/**
 * Maps same-origin URLs to readable files contained by a document root.
 */
final class LocalFileResolver
{
    private MarkdownOptions $options;

    public function __construct(MarkdownOptions $options)
    {
        $this->options = $options;
    }

    public function resolve(string $url): ?string
    {
        if ($this->options->getBaseUrl() === '' || $this->options->getDocumentRoot() === '') {
            return null;
        }

        $target = parse_url($this->options->getBaseUrl());
        $candidate = parse_url($url);
        if (! is_array($target) || ! is_array($candidate) || ! $this->hasSameOrigin($target, $candidate)) {
            return null;
        }

        $targetPath = rawurldecode((string) ($target['path'] ?? ''));
        $candidatePath = rawurldecode((string) ($candidate['path'] ?? ''));
        if (strpos($targetPath, "\0") !== false || strpos($candidatePath, "\0") !== false) {
            return null;
        }

        $targetPath = rtrim($targetPath, '/');
        if (
            $targetPath !== '' &&
            $candidatePath !== $targetPath &&
            strpos($candidatePath, $targetPath . '/') !== 0
        ) {
            return null;
        }

        $documentRoot = realpath($this->options->getDocumentRoot());
        if ($documentRoot === false || ! is_dir($documentRoot)) {
            return null;
        }

        $relativePath = substr($candidatePath, strlen($targetPath));
        $localPath = realpath($documentRoot . DIRECTORY_SEPARATOR . ltrim($relativePath, '/'));
        if ($localPath === false) {
            return null;
        }

        $rootPrefix = rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($localPath, $rootPrefix) !== 0) {
            return null;
        }

        return is_file($localPath) && is_readable($localPath) ? $localPath : null;
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $candidate
     */
    private function hasSameOrigin(array $target, array $candidate): bool
    {
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));
        $candidateScheme = strtolower((string) ($candidate['scheme'] ?? ''));
        $targetHost = strtolower((string) ($target['host'] ?? ''));
        $candidateHost = strtolower((string) ($candidate['host'] ?? ''));

        if (
            $targetScheme === '' ||
            $targetHost === '' ||
            $targetScheme !== $candidateScheme ||
            $targetHost !== $candidateHost
        ) {
            return false;
        }

        return $this->getPort($target) === $this->getPort($candidate);
    }

    /** @param array<string, mixed> $url */
    private function getPort(array $url): ?int
    {
        if (isset($url['port'])) {
            return (int) $url['port'];
        }

        $scheme = strtolower((string) ($url['scheme'] ?? ''));
        if ($scheme === 'http') {
            return 80;
        }
        if ($scheme === 'https') {
            return 443;
        }

        return null;
    }
}
