<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown;

/**
 * Immutable options for one Markdown conversion context.
 */
final class MarkdownOptions
{
    private string $baseUrl;

    private string $documentRoot;

    private function __construct(string $baseUrl = '', string $documentRoot = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->documentRoot = rtrim($documentRoot, DIRECTORY_SEPARATOR);
    }

    public static function defaults(): self
    {
        return new self();
    }

    public function withBaseUrl(string $baseUrl): self
    {
        return new self($baseUrl, $this->documentRoot);
    }

    public function withDocumentRoot(string $documentRoot): self
    {
        return new self($this->baseUrl, $documentRoot);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }
}
