<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Container\Parser;

use Jidaikobo\Markdown\Extension\Container\Node\Container;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class ContainerStartParser implements BlockStartParserInterface
{
    private const NOTE_VARIANTS = ['info', 'warn', 'alert'];

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== ':') {
            return BlockStart::none();
        }

        $cursor->advanceToNextNonSpaceOrTab();
        $line = $cursor->getRemainder();
        if (preg_match('/^(:{3,})[ \t]+(note|aside|details)(?:[ \t]+(.*?))?[ \t]*$/u', $line, $matches) !== 1) {
            return BlockStart::none();
        }

        $fenceLength = strlen($matches[1]);
        $type = $matches[2];
        $arguments = isset($matches[3]) ? trim($matches[3]) : '';
        [$variant, $title] = $this->parseArguments($type, $arguments);

        $cursor->advanceToEnd();

        return BlockStart::of(
            new ContainerParser($fenceLength, new Container($type, $variant, $title))
        )->at($cursor);
    }

    /**
     * @return array{string, string}
     */
    private function parseArguments(string $type, string $arguments): array
    {
        if ($type !== Container::TYPE_NOTE) {
            return ['', $this->unquote($arguments)];
        }

        if ($arguments === '') {
            return ['info', ''];
        }

        $parts = preg_split('/[ \t]+/', $arguments, 2);
        $first = $parts[0] ?? '';
        if (! in_array($first, self::NOTE_VARIANTS, true)) {
            return ['info', $this->unquote($arguments)];
        }

        return [$first, $this->unquote($parts[1] ?? '')];
    }

    private function unquote(string $value): string
    {
        $length = strlen($value);
        if ($length < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[$length - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
