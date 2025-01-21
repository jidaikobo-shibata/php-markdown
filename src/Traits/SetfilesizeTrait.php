<?php

namespace Jidaikobo\Traits;

/**
 * additional anchor processing features.
 */
Trait SetfilesizeTrait
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

    protected function _doAnchors_inline_callback($matches)
    {
        $link_text = $this->runSpanGamut($matches[2]);
        $url = $matches[3] === '' ? $matches[4] : $matches[3];
        $title =& $matches[7];

        // 元のURLを復元
        $unhashed = $this->unhash($url);
        if ($unhashed !== $url) {
            $url = preg_replace('/^<(.*)>$/', '\1', $unhashed);
        }

        // 絶対パスの場合にURLを補完
        if (strpos($url, '/') === 0 && self::$targetUrl) {
            $url = rtrim(self::$targetUrl, '/') . $url;
        }

        // 特定のURLを処理
        if (self::$targetUrl && strpos($url, self::$targetUrl) === 0) {
            $relativePath = str_replace(self::$targetUrl, '', $url);
            $localPath = self::$replacePath . $relativePath;

            // ファイルが存在する場合はファイルサイズを取得
            if (file_exists($localPath) && is_readable($localPath)) {
                $size = filesize($localPath);
                $sizeText = $this->formatFileSize($size);

                // 拡張子を取得
                $extension = pathinfo($localPath, PATHINFO_EXTENSION);

                // リンクテキストに拡張子とサイズを追加
                $link_text .= " ({$extension}, {$sizeText})";
            }
        }

        $url = $this->encodeURLAttribute($url);

        $result = "<a href=\"$url\"";
        if ($title) {
            $title = $this->encodeAttribute($title);
            $result .= " title=\"$title\"";
        }

        $link_text = $this->runSpanGamut($link_text);
        $result .= ">$link_text</a>";

        return $this->hashPart($result);
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
