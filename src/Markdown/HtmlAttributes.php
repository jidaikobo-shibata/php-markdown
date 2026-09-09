<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown;

use League\CommonMark\Node\Node;

final class HtmlAttributes
{
    /**
     * @return array<string, array<string>|bool|string>
     */
    public static function fromNode(Node $node): array
    {
        $rawAttributes = $node->data->get('attributes');
        if (!is_array($rawAttributes)) {
            return [];
        }

        $attributes = [];
        foreach ($rawAttributes as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            if (is_string($value) || is_bool($value)) {
                $attributes[$name] = $value;
                continue;
            }

            if (is_array($value) && self::containsOnlyStrings($value)) {
                /** @var array<array-key, string> $value */
                $attributes[$name] = array_values($value);
            }
        }

        return $attributes;
    }

    /** @param array<mixed> $values */
    private static function containsOnlyStrings(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_string($value)) {
                return false;
            }
        }

        return true;
    }
}
