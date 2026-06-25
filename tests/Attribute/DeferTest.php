<?php

declare(strict_types=1);

namespace BEAR\Defer\Attribute;

use BEAR\Defer\Exception\InvalidDeferRelException;
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

    public function testEmptyArrayThrows(): void
    {
        $this->expectException(InvalidDeferRelException::class);

        new Defer([]);
    }

    public function testEmptyStringRelThrows(): void
    {
        $this->expectException(InvalidDeferRelException::class);

        new Defer(['']);
    }
}
