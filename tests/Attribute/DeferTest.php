<?php

declare(strict_types=1);

namespace BEAR\Defer\Attribute;

use PHPUnit\Framework\TestCase;

final class DeferTest extends TestCase
{
    public function testSingleRelGivenAsStringBecomesList(): void
    {
        $defer = new Defer('publish');

        $this->assertSame(['publish'], $defer->rels);
    }

    public function testMultipleRelsGivenAsArray(): void
    {
        $defer = new Defer(['publish', 'note']);

        $this->assertSame(['publish', 'note'], $defer->rels);
    }
}
