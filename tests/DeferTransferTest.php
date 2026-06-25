<?php

declare(strict_types=1);

namespace BEAR\Defer;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\TransferInterface;
use Override;
use PHPUnit\Framework\TestCase;

final class DeferTransferTest extends TestCase
{
    public function testFlushesAfterBaseTransfer(): void
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

        $decorator = new DeferTransfer($transfer, $defer);
        $decorator(new FakeResourceObject(), []);

        $this->assertSame(['transfer', 'flush'], $calls->calls);
    }
}
