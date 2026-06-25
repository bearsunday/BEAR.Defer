<?php

declare(strict_types=1);

namespace BEAR\Defer;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\TransferInterface;
use Override;
use Ray\Di\Di\Named;

/**
 * Transfer decorator that flushes deferred requests after the response is sent
 *
 * The base transfer ("how to send", environment specific) is injected with the
 * 'base' qualifier; this decorator adds "flush after send", which is the same
 * regardless of the runtime.
 */
final readonly class DeferAwareTransfer implements TransferInterface
{
    public function __construct(
        #[Named('base')]
        private TransferInterface $transfer,
        private DeferInterface $defer,
    ) {
    }

    /** {@inheritDoc} */
    #[Override]
    public function __invoke(ResourceObject $ro, array $server)
    {
        ($this->transfer)($ro, $server);
        $this->defer->flush();
    }
}
