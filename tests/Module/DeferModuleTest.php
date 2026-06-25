<?php

declare(strict_types=1);

namespace BEAR\Defer\Module;

use BEAR\Defer\DeferTransfer;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\TransferInterface;
use FakeVendor\Sandbox\PublishLog;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;

final class DeferModuleTest extends TestCase
{
    public function testDeferredRequestRunsOnFlush(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new ResourceModule('FakeVendor\\Sandbox'));
                $this->install(new DeferModule());
                $this->bind(PublishLog::class)->in(Scope::SINGLETON);
            }
        });
        $resource = $injector->getInstance(ResourceInterface::class);
        $transfer = $injector->getInstance(TransferInterface::class);
        $log = $injector->getInstance(PublishLog::class);
        $this->assertInstanceOf(DeferTransfer::class, $transfer);

        $ro = $resource->post('app://self/article', ['title' => 'Hi', 'body' => 'Body']);

        $this->assertSame(202, $ro->code);
        $this->assertSame([], $log->ids);

        // base transfer (NullResponder) runs, then deferred requests are flushed
        $transfer($ro, []);

        $this->assertSame(['123'], $log->ids);
    }
}
