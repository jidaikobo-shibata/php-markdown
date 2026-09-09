<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown;

use InvalidArgumentException;
use RuntimeException;

final class CheatSheet
{
    public const LANGUAGE_ENGLISH = 'en';
    public const LANGUAGE_JAPANESE = 'ja';

    /** @var array<string, string> */
    private const FILES = [
        self::LANGUAGE_ENGLISH => 'en.html',
        self::LANGUAGE_JAPANESE => 'ja.html',
    ];

    public static function getHtml(
        string $language = self::LANGUAGE_ENGLISH,
        string $assetBaseUrl = '',
        string $headingIdPrefix = 'content'
    ): string {
        if (!isset(self::FILES[$language])) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported cheat-sheet language "%s".',
                $language
            ));
        }

        $path = dirname(__DIR__, 2) . '/resources/cheatsheet/' . self::FILES[$language];
        $html = file_get_contents($path);
        if ($html === false) {
            throw new RuntimeException(sprintf(
                'Unable to read the bundled cheat-sheet fragment "%s".',
                $language
            ));
        }

        if (preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $headingIdPrefix) !== 1) {
            throw new InvalidArgumentException(
                'Cheat-sheet heading ID prefix must begin with an ASCII letter ' .
                'and contain only letters, digits, _ or -.'
            );
        }

        if ($headingIdPrefix !== 'content') {
            $html = str_replace(
                ['id="content-', 'href="#content-'],
                ['id="' . $headingIdPrefix . '-', 'href="#' . $headingIdPrefix . '-'],
                $html
            );
        }

        $assetPrefix = self::normalizeAssetBaseUrl($assetBaseUrl);
        if ($assetPrefix !== '') {
            $escapedPrefix = htmlspecialchars($assetPrefix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $html = str_replace(
                ['href="files/', 'src="files/'],
                ['href="' . $escapedPrefix . 'files/', 'src="' . $escapedPrefix . 'files/'],
                $html
            );
        }

        return $html;
    }

    private static function normalizeAssetBaseUrl(string $assetBaseUrl): string
    {
        if ($assetBaseUrl === '') {
            return '';
        }

        if (
            $assetBaseUrl !== trim($assetBaseUrl)
            || preg_match('/[\x00-\x20\x7f]/', $assetBaseUrl) === 1
            || strpos($assetBaseUrl, '\\') !== false
        ) {
            throw new InvalidArgumentException(
                'Cheat-sheet asset base URL must not contain whitespace, control characters, or backslashes.'
            );
        }

        if (strncmp($assetBaseUrl, '/', 1) === 0) {
            if (
                strncmp($assetBaseUrl, '//', 2) === 0
                || strpos($assetBaseUrl, '?') !== false
                || strpos($assetBaseUrl, '#') !== false
            ) {
                throw new InvalidArgumentException(
                    'Cheat-sheet asset base URL must be a single-slash root-relative URL without a query or fragment.'
                );
            }

            return rtrim($assetBaseUrl, '/') . '/';
        }

        return MarkdownOptions::defaults()->withBaseUrl($assetBaseUrl)->getBaseUrl() . '/';
    }
}
