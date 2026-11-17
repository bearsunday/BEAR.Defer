<?php

declare(strict_types=1);

namespace BEAR\Defer\Attribute;

use Attribute;
use BEAR\Defer\Exception\InvalidDeferRelException;

use function is_string;

/**
 * Runs the requests of the listed `#[Link]` rels after the response
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/defer.html#declare-what-to-defer
 * @see \BEAR\Defer\DeferInterceptor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Defer
{
    /** @var non-empty-list<string> */
    public readonly array $rels;

    /** @param list<string>|string $rels Rels of `#[Link]` on the same method, one or more */
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
