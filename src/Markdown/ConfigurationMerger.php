<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown;

final class ConfigurationMerger
{
    /**
     * Recursively merges configuration maps while replacing list values.
     *
     * @template TKey of array-key
     *
     * @param array<TKey, mixed> $base
     * @param array<TKey, mixed> $overrides
     *
     * @return array<TKey, mixed>
     */
    public static function merge(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (
                isset($base[$key])
                && is_array($base[$key])
                && is_array($value)
                && self::isMap($base[$key])
                && self::isMap($value)
            ) {
                $base[$key] = self::merge($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /** @param array<array-key, mixed> $value */
    private static function isMap(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        $expectedKey = 0;
        foreach (array_keys($value) as $key) {
            if ($key !== $expectedKey) {
                return true;
            }
            $expectedKey++;
        }

        return false;
    }
}
