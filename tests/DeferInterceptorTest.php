<?php

declare(strict_types=1);

namespace BEAR\Defer;

use BEAR\Defer\Attribute\Defer;
use BEAR\Defer\Exception\LinkRelNotFoundException;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use FakeVendor\Sandbox\PublishLog;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;

use function assert;

final class DeferInterceptorTest extends TestCase
{
    private function injector(): Injector
    {
        return new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new ResourceModule('FakeVendor\\Sandbox'));
                $this->bind(PublishLog::class)->in(Scope::SINGLETON);
                $this->bind(DeferInterface::class)->to(SpyDefer::class)->in(Scope::SINGLETON);
                $this->bind(DeferInterceptorInterface::class)->to(DeferInterceptor::class);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->annotatedWith(Defer::class),
                    [DeferInterceptorInterface::class],
                );
            }
        });
    }

    public function testDeferEnqueuesResolvedRequest(): void
    {
        $injector = $this->injector();
        $resource = $injector->getInstance(ResourceInterface::class);
        $spy = $injector->getInstance(DeferInterface::class);
        assert($spy instanceof SpyDefer);

        $ro = $resource->post('app://self/article', ['title' => 'Hi', 'body' => 'Body']);

        $this->assertSame(202, $ro->code);
        $this->assertCount(2, $spy->added);
        $publish = $spy->added[0];
        $note = $spy->added[1];
        assert($publish instanceof RequestInterface);
        assert($note instanceof RequestInterface);
        $this->assertStringContainsString('publish', $publish->toUri());
        $this->assertStringContainsString('id=123', $publish->toUri());
        $this->assertStringContainsString('note', $note->toUri());
    }

    public function testThrowsWhenRelHasNoLink(): void
    {
        $injector = $this->injector();
        $resource = $injector->getInstance(ResourceInterface::class);

        $this->expectException(LinkRelNotFoundException::class);
        $this->expectExceptionMessage('missing');
        $resource->get('app://self/bad-rel');
    }
}
