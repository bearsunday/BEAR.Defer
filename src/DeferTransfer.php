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
 * 'inner' qualifier. This decorator runs it, releases the client connection when
 * the SAPI allows it, then flushes the deferred requests — so the client is not
 * kept waiting for the deferred work.
 */
final readonly class DeferTransfer implements TransferInterface
{
    public function __construct(
        #[Named('inner')]
        private TransferInterface $transfer,
        private ConnectionCloserInterface $close,
        private DeferInterface $defer,
    ) {
    }

    /** {@inheritDoc} */
    #[Override]
    public function __invoke(ResourceObject $ro, array $server)
    {
        ($this->transfer)($ro, $server);
        try {
            ($this->close)();
        } finally {
            $this->defer->flush();
        }
    }
}
