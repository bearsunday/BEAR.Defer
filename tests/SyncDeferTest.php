<?php

declare(strict_types=1);

namespace BEAR\Defer;

use BEAR\Defer\Exception\DeferFlushException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function testFlushRunsAllRequestsEvenWhenOneThrows(): void
    {
        $defer = new SyncDefer();
        $ran = [];
        $defer->add(static function () use (&$ran): void {
            $ran[] = 'a';
        });
        $defer->add(static function (): void {
            throw new RuntimeException('boom');
        });
        $defer->add(static function () use (&$ran): void {
            $ran[] = 'c';
        });

        try {
            $defer->flush();
            $this->fail('Expected DeferFlushException');
        } catch (DeferFlushException $e) {
            $this->assertCount(1, $e->errors);
            $this->assertSame('boom', $e->getPrevious()?->getMessage());
            $this->assertStringContainsString('1 deferred', $e->getMessage());
        }

        $this->assertSame(['a', 'c'], $ran);
    }
}
