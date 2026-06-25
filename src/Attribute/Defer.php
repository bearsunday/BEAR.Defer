<?php

declare(strict_types=1);

namespace BEAR\Defer\Attribute;

use Attribute;
use BEAR\Defer\Exception\InvalidDeferRelException;

use function is_string;

#[Attribute(Attribute::TARGET_METHOD)]
final class Defer
{
    /** @var non-empty-list<string> */
    public readonly array $rels;

    /** @param list<string>|string $rels */
    public function __construct(array|string $rels)
    {
        $rels = is_string($rels) ? [$rels] : $rels;
        if ($rels === []) {
            throw new InvalidDeferRelException('#[Defer] requires at least one rel.');
        }

        foreach ($rels as $rel) {
            if ($rel === '') {
                throw new InvalidDeferRelException('#[Defer] rel must be a non-empty string.');
            }
        }

        $this->rels = $rels;
    }
}
