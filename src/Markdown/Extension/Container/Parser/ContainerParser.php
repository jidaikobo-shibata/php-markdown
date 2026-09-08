<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Container\Parser;

use Jidaikobo\Markdown\Extension\Container\Node\Container;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

final class ContainerParser extends AbstractBlockContinueParser
{
    private int $fenceLength;
    private Container $block;

    public function __construct(int $fenceLength, Container $block)
    {
        $this->fenceLength = $fenceLength;
        $this->block = $block;
    }

    public function getBlock(): Container
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        return true;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): BlockContinue
    {
        $activeBlock = $activeBlockParser->getBlock();
        if ($activeBlock instanceof FencedCode) {
            return BlockContinue::at($cursor);
        }

        if (! $cursor->isIndented() && $cursor->getNextNonSpaceCharacter() === ':') {
            $pattern = '/^[ \t]*:{' . $this->fenceLength . ',}[ \t]*$/';
            if (preg_match($pattern, $cursor->getRemainder()) === 1) {
                return BlockContinue::finished();
            }
        }

        return BlockContinue::at($cursor);
    }
}
