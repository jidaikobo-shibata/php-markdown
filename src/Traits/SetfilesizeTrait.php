<?php

namespace Jidaikobo\Traits;

/**
 * additional anchor processing features.
 */
trait SetfilesizeTrait
{
    protected static $targetUrl = '';
    protected static $replacePath = '';

    public static function setTargetUrl($url)
    {
        self::$targetUrl = rtrim($url, '/');
    }

    public static function setReplacePath($path)
    {
        self::$replacePath = rtrim($path, '/');
    }

    // The method name is defined by the parent parser's callback API.
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps,PSR2.Methods.MethodDeclaration.Underscore
    protected function _doAnchors_inline_callback($matches)
    {
        $link_text = $this->runSpanGamut($matches[2]);
        $url = $matches[3] === '' ? $matches[4] : $matches[3];
        $title_quote =& $matches[6];
        $title =& $matches[7];
        $attr = $this->doExtraAttributes('a', $matches[8] ?? '');

        // 元のURLを復元
        $unhashed = $this->unhash($url);
        if ($unhashed !== $url) {
            $url = preg_replace('/^<(.*)>$/', '\1', $unhashed);
        }

        // 絶対パスの場合にURLを補完
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0 && self::$targetUrl) {
            $url = rtrim(self::$targetUrl, '/') . $url;
        }

        $localPath = $this->resolveLocalPath($url);
        if ($localPath !== null) {
            $size = filesize($localPath);
            if ($size !== false) {
                $sizeText = $this->formatFileSize($size);

                // 拡張子を取得
                $extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));

                // 画像は除外する
                $imageExtensions = [
                    'png', 'apng', 'jpg', 'jpeg', 'jpe', 'jfif', 'pjpeg', 'pjp',
                    'gif', 'bmp', 'tif', 'tiff', 'ico', 'svg', 'svgz', 'webp', 'avif',
                ];
                if (!in_array($extension, $imageExtensions, true)) {
                    // リンクテキストに拡張子とサイズを追加
                    $link_text .= " ({$extension}, {$sizeText})";
                }
            }
        }

        $url = $this->encodeURLAttribute($url);

        $result = "<a href=\"$url\"";
        if (isset($title) && $title_quote) {
            $title = $this->encodeAttribute($title);
            $result .= " title=\"$title\"";
        }
        $result .= $attr;

        $link_text = $this->runSpanGamut($link_text);
        $result .= ">$link_text</a>";

        return $this->hashPart($result);
    }

    /**
     * Resolves a configured public URL to a readable file below its document root.
     *
     * @param string $url The link URL.
     *
     * @return string|null The canonical local file path, or null when not local.
     */
    protected function resolveLocalPath($url)
    {
        if (self::$targetUrl === '' || self::$replacePath === '') {
            return null;
        }

        $target = parse_url(self::$targetUrl);
        $candidate = parse_url($url);
        if (!is_array($target) || !is_array($candidate)) {
            return null;
        }

        if (!$this->hasSameUrlOrigin($target, $candidate)) {
            return null;
        }

        $targetPath = rawurldecode($target['path'] ?? '');
        $candidatePath = rawurldecode($candidate['path'] ?? '');
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

        $relativePath = substr($candidatePath, strlen($targetPath));
        $documentRoot = realpath(self::$replacePath);
        if ($documentRoot === false || !is_dir($documentRoot)) {
            return null;
        }

        $localPath = realpath($documentRoot . DIRECTORY_SEPARATOR . ltrim($relativePath, '/'));
        if ($localPath === false) {
            return null;
        }

        $documentRootPrefix = rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($localPath, $documentRootPrefix) !== 0) {
            return null;
        }

        if (!is_file($localPath) || !is_readable($localPath)) {
            return null;
        }

        return $localPath;
    }

    /**
     * Checks whether two parsed URLs have the same scheme, host, and port.
     *
     * @param array $target The configured target URL.
     * @param array $candidate The link URL.
     *
     * @return bool Whether both URLs share an origin.
     */
    protected function hasSameUrlOrigin(array $target, array $candidate)
    {
        $targetScheme = strtolower($target['scheme'] ?? '');
        $candidateScheme = strtolower($candidate['scheme'] ?? '');
        $targetHost = strtolower($target['host'] ?? '');
        $candidateHost = strtolower($candidate['host'] ?? '');

        if (
            $targetScheme === '' ||
            $targetHost === '' ||
            $targetScheme !== $candidateScheme ||
            $targetHost !== $candidateHost
        ) {
            return false;
        }

        return $this->getUrlPort($target) === $this->getUrlPort($candidate);
    }

    /**
     * Gets an explicit or scheme-default URL port.
     *
     * @param array $url A parsed URL.
     *
     * @return int|null The normalized port.
     */
    protected function getUrlPort(array $url)
    {
        if (isset($url['port'])) {
            return (int) $url['port'];
        }

        $scheme = strtolower($url['scheme'] ?? '');
        if ($scheme === 'http') {
            return 80;
        }
        if ($scheme === 'https') {
            return 443;
        }

        return null;
    }

    /**
     * Formats a file size in human-readable format.
     *
     * @param int $size The file size in bytes.
     * @return string The formatted file size.
     */
    protected function formatFileSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        return round($size, 1) . ' ' . $units[$unitIndex];
    }
}
