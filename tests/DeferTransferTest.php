<?php

declare(strict_types=1);

namespace BEAR\Defer;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\TransferInterface;
use Override;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DeferTransferTest extends TestCase
{
    public function testReleasesConnectionThenFlushesAfterBaseTransfer(): void
    {
        $calls = new CallLog();
        $transfer = new class ($calls) implements TransferInterface {
            public function __construct(private readonly CallLog $calls)
            {
            }

            #[Override]
            public function __invoke(ResourceObject $ro, array $server)
            {
                $this->calls->record('transfer');
            }
        };
        $defer = new class ($calls) implements DeferInterface {
            public function __construct(private readonly CallLog $calls)
            {
            }

            #[Override]
            public function add(callable $request): void
            {
            }

            #[Override]
            public function flush(): void
            {
                $this->calls->record('flush');
            }
        };

        $decorator = new DeferTransfer($transfer, new FakeConnectionCloser($calls), $defer);
        $decorator(new FakeResourceObject(), []);

        $this->assertSame(['transfer', 'close', 'flush'], $calls->calls);
    }

    public function testFlushesEvenWhenConnectionCloserThrows(): void
    {
        $calls = new CallLog();
        $transfer = new class ($calls) implements TransferInterface {
            public function __construct(private readonly CallLog $calls)
            {
            }

            #[Override]
            public function __invoke(ResourceObject $ro, array $server)
            {
                $this->calls->record('transfer');
            }
        };
        $defer = new class ($calls) implements DeferInterface {
            public function __construct(private readonly CallLog $calls)
            {
            }

            #[Override]
            public function add(callable $request): void
            {
            }

            #[Override]
            public function flush(): void
            {
                $this->calls->record('flush');
            }
        };
        $close = new class implements ConnectionCloserInterface {
            #[Override]
            public function __invoke(): void
            {
                throw new RuntimeException('closer failed');
            }
        };

        $decorator = new DeferTransfer($transfer, $close, $defer);

        try {
            $decorator(new FakeResourceObject(), []);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertSame('closer failed', $e->getMessage());
        }

        // The closer threw, but the deferred queue was still flushed
        $this->assertSame(['transfer', 'flush'], $calls->calls);
    }
}
