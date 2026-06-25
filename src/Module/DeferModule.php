<?php

declare(strict_types=1);

namespace BEAR\Defer\Module;

use BEAR\Defer\Attribute\Defer;
use BEAR\Defer\DeferInterceptor;
use BEAR\Defer\DeferInterceptorInterface;
use BEAR\Defer\DeferInterface;
use BEAR\Defer\DeferTransfer;
use BEAR\Defer\SyncDefer;
use BEAR\Resource\NullResponder;
use BEAR\Resource\TransferInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class DeferModule extends AbstractModule
{
    /** {@inheritDoc} */
    #[Override]
    protected function configure(): void
    {
        $this->bind(DeferInterface::class)->to(SyncDefer::class)->in(Scope::SINGLETON);
        $this->bind(DeferInterceptorInterface::class)->to(DeferInterceptor::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(Defer::class),
            [DeferInterceptorInterface::class],
        );
        // base transfer: replaced by the application (e.g. HttpResponder); NullResponder by default
        $this->bind(TransferInterface::class)->annotatedWith('base')->to(NullResponder::class);
        $this->bind(TransferInterface::class)->to(DeferTransfer::class);
    }
}
