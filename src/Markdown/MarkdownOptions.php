<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown;

use InvalidArgumentException;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Immutable options for one Markdown conversion context.
 */
final class MarkdownOptions
{
    public const HTML_INPUT_ALLOW = 'allow';
    public const HTML_INPUT_ESCAPE = 'escape';
    public const HTML_INPUT_STRIP = 'strip';

    private string $baseUrl;

    private string $documentRoot;

    private string $htmlInput;

    /** @var array<string, mixed> */
    private array $leagueConfiguration;

    /** @var ExtensionInterface[] */
    private array $leagueExtensions;

    /**
     * @param array<string, mixed> $leagueConfiguration
     * @param ExtensionInterface[] $leagueExtensions
     */
    private function __construct(
        string $baseUrl = '',
        string $documentRoot = '',
        string $htmlInput = self::HTML_INPUT_ESCAPE,
        array $leagueConfiguration = [],
        array $leagueExtensions = []
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->documentRoot = rtrim($documentRoot, DIRECTORY_SEPARATOR);
        $this->htmlInput = $htmlInput;
        $this->leagueConfiguration = $leagueConfiguration;
        $this->leagueExtensions = $leagueExtensions;
    }

    public static function defaults(): self
    {
        return new self();
    }

    /**
     * Creates options without version 2 input validation for the legacy facade.
     *
     * @internal
     */
    public static function forCompatibilityApi(string $baseUrl, string $documentRoot): self
    {
        return new self(
            $baseUrl,
            $documentRoot,
            self::HTML_INPUT_ALLOW
        );
    }

    public function withBaseUrl(string $baseUrl): self
    {
        $baseUrl = $this->normalizeBaseUrl($baseUrl);

        return new self(
            $baseUrl,
            $this->documentRoot,
            $this->htmlInput,
            $this->leagueConfiguration,
            $this->leagueExtensions
        );
    }

    public function withDocumentRoot(string $documentRoot): self
    {
        $documentRoot = $this->normalizeDocumentRoot($documentRoot);

        return new self(
            $this->baseUrl,
            $documentRoot,
            $this->htmlInput,
            $this->leagueConfiguration,
            $this->leagueExtensions
        );
    }

    public function withHtmlInput(string $htmlInput): self
    {
        $allowedValues = [
            self::HTML_INPUT_ALLOW,
            self::HTML_INPUT_ESCAPE,
            self::HTML_INPUT_STRIP,
        ];

        if (!in_array($htmlInput, $allowedValues, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported html_input value "%s".',
                $htmlInput
            ));
        }

        return new self(
            $this->baseUrl,
            $this->documentRoot,
            $htmlInput,
            $this->leagueConfiguration,
            $this->leagueExtensions
        );
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function withLeagueConfiguration(array $configuration): self
    {
        return new self(
            $this->baseUrl,
            $this->documentRoot,
            $this->htmlInput,
            ConfigurationMerger::merge($this->leagueConfiguration, $configuration),
            $this->leagueExtensions
        );
    }

    public function withLeagueExtension(ExtensionInterface $extension): self
    {
        $extensions = $this->leagueExtensions;
        $extensions[] = $extension;

        return new self(
            $this->baseUrl,
            $this->documentRoot,
            $this->htmlInput,
            $this->leagueConfiguration,
            $extensions
        );
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    public function getHtmlInput(): string
    {
        return $this->htmlInput;
    }

    /** @return array<string, mixed> */
    public function getLeagueConfiguration(): array
    {
        return $this->leagueConfiguration;
    }

    /** @return ExtensionInterface[] */
    public function getLeagueExtensions(): array
    {
        return $this->leagueExtensions;
    }

    private function normalizeBaseUrl(string $baseUrl): string
    {
        if ($baseUrl === '') {
            return '';
        }

        if (
            $baseUrl !== trim($baseUrl)
            || preg_match('/[\x00-\x20\x7f]/', $baseUrl) === 1
            || strpos($baseUrl, '\\') !== false
        ) {
            throw new InvalidArgumentException(
                'Base URL must not contain whitespace, control characters, or backslashes.'
            );
        }

        $parts = parse_url($baseUrl);
        if (!is_array($parts)) {
            throw new InvalidArgumentException('Base URL must be a valid absolute HTTP or HTTPS URL.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || ($parts['host'] ?? '') === '') {
            throw new InvalidArgumentException('Base URL must be an absolute HTTP or HTTPS URL with a host.');
        }

        foreach (['user', 'pass', 'query', 'fragment'] as $unsupportedPart) {
            if (array_key_exists($unsupportedPart, $parts)) {
                throw new InvalidArgumentException(sprintf(
                    'Base URL must not contain a %s component.',
                    $unsupportedPart
                ));
            }
        }

        return rtrim($baseUrl, '/');
    }

    private function normalizeDocumentRoot(string $documentRoot): string
    {
        if ($documentRoot === '') {
            return '';
        }

        if (preg_match('/[\x00-\x1f\x7f]/', $documentRoot) === 1) {
            throw new InvalidArgumentException('Document root must not contain control characters.');
        }

        if (!$this->isAbsolutePath($documentRoot)) {
            throw new InvalidArgumentException('Document root must be an absolute path.');
        }

        $resolvedRoot = realpath($documentRoot);
        if ($resolvedRoot === false || !is_dir($resolvedRoot)) {
            throw new InvalidArgumentException('Document root must be an existing directory.');
        }

        if ($this->isFileSystemRoot($resolvedRoot)) {
            throw new InvalidArgumentException('Document root must not be a filesystem root directory.');
        }

        return rtrim($resolvedRoot, '/\\');
    }

    private function isAbsolutePath(string $path): bool
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return strncmp($path, '/', 1) === 0;
        }

        return preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
            || preg_match('/^\\\\\\\\[^\\\\]+\\\\[^\\\\]+/', $path) === 1;
    }

    private function isFileSystemRoot(string $path): bool
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return preg_match('#^/+$#', $path) === 1;
        }

        return preg_match('/^[A-Za-z]:[\\\\\/]*$/', $path) === 1
            || preg_match('/^\\\\\\\\[^\\\\]+\\\\[^\\\\]+[\\\\]*$/', $path) === 1;
    }
}
