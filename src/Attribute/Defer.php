<?php

declare(strict_types=1);

namespace BEAR\Defer\Attribute;

use Attribute;

use function is_string;

#[Attribute(Attribute::TARGET_METHOD)]
final class Defer
{
    /** @var list<string> */
    public readonly array $rels;

    /** @param non-empty-list<string>|string $rels */
    public function __construct(array|string $rels)
    {
        $this->rels = is_string($rels) ? [$rels] : $rels;
    }
}
