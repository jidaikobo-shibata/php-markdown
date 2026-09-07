<?php

declare(strict_types=1);

namespace Jidaikobo;

use Jidaikobo\Markdown\MarkdownConverter;
use Jidaikobo\Markdown\MarkdownOptions;

/**
 * Backward-compatible facade for the version 1 public API.
 *
 * Version 2 no longer extends Michelf\MarkdownExtra. New code should create a
 * MarkdownConverter with explicit MarkdownOptions instead.
 */
class MarkdownExtra
{
    /** @var string */
    protected static $targetUrl = '';

    /** @var string */
    protected static $replacePath = '';

    /**
     * @param string $url Base URL used to complete root-relative links.
     */
    public static function setTargetUrl($url): void
    {
        self::$targetUrl = rtrim($url, '/');
    }

    /**
     * @param string $path Document root used to resolve downloadable files.
     */
    public static function setReplacePath($path): void
    {
        self::$replacePath = rtrim($path, '/');
    }

    /**
     * @param string $markdown Markdown source.
     */
    public static function defaultTransform($markdown): string
    {
        return self::createConverter()->convert($markdown);
    }

    /**
     * @param string $markdown Markdown source.
     */
    public function transform($markdown): string
    {
        return self::defaultTransform($markdown);
    }

    private static function createConverter(): MarkdownConverter
    {
        $options = MarkdownOptions::defaults()
            ->withBaseUrl(self::$targetUrl)
            ->withDocumentRoot(self::$replacePath);

        return new MarkdownConverter($options);
    }
}
