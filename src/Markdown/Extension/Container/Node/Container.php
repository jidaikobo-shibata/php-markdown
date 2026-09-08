<?php

declare(strict_types=1);

namespace Jidaikobo\Markdown\Extension\Container\Node;

use League\CommonMark\Node\Block\AbstractBlock;

final class Container extends AbstractBlock
{
    public const TYPE_NOTE = 'note';
    public const TYPE_ASIDE = 'aside';
    public const TYPE_DETAILS = 'details';

    private string $type;
    private string $variant;
    private string $title;

    public function __construct(string $type, string $variant = '', string $title = '')
    {
        parent::__construct();

        $this->type = $type;
        $this->variant = $variant;
        $this->title = $title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
