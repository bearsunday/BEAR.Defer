<?php

declare(strict_types=1);

namespace BEAR\Defer;

use PHPUnit\Framework\TestCase;

final class SyncDeferTest extends TestCase
{
    public function testAddedRequestsRunInOrderOnFlush(): void
    {
        $defer = new SyncDefer();
        $log = [];
        $defer->add(static function () use (&$log): void {
            $log[] = 'a';
        });
        $defer->add(static function () use (&$log): void {
            $log[] = 'b';
        });

        $defer->flush();

        $this->assertSame(['a', 'b'], $log);
    }

    public function testFlushClearsQueueAndIsIdempotent(): void
    {
        $defer = new SyncDefer();
        $count = 0;
        $defer->add(static function () use (&$count): void {
            $count++;
        });

        $defer->flush();
        $defer->flush();

        $this->assertSame(1, $count);
    }
}
