<?php

declare(strict_types=1);

namespace BEAR\Defer;

use PHPUnit\Framework\TestCase;

use function ob_get_clean;
use function ob_start;

final class SapiConnectionCloserTest extends TestCase
{
    public function testPrefersFastcgiFinishRequest(): void
    {
        $closer = new SapiConnectionCloser('fpm-fcgi', static fn (string $fn): bool => $fn === 'fastcgi_finish_request');

        $this->assertSame('fastcgi_finish_request', $closer->strategy());
    }

    public function testFallsBackToLitespeedFinishRequest(): void
    {
        $closer = new SapiConnectionCloser('litespeed', static fn (string $fn): bool => $fn === 'litespeed_finish_request');

        $this->assertSame('litespeed_finish_request', $closer->strategy());
    }

    public function testHasNothingToDoOnCli(): void
    {
        $closer = new SapiConnectionCloser('cli', static fn (): bool => false);

        $this->assertSame('', $closer->strategy());
    }

    public function testFallsBackToFlushOnOtherWebSapi(): void
    {
        $closer = new SapiConnectionCloser('apache2handler', static fn (): bool => false);

        $this->assertSame('flush', $closer->strategy());
    }

    public function testInvokeRunsTheResolvedStrategy(): void
    {
        // 'flush' strategy: __invoke() calls flush(), which is harmless under the CLI runner
        $closer = new SapiConnectionCloser('apache2handler', static fn (): bool => false);

        ob_start();
        $closer();

        $this->assertSame('', ob_get_clean());
    }

    public function testInvokeIsNoopWhenThereIsNothingToDo(): void
    {
        $closer = new SapiConnectionCloser('cli', static fn (): bool => false);

        ob_start();
        $closer();

        $this->assertSame('', ob_get_clean());
    }

    public function testDefaultsToCurrentSapiAndBuiltinFunctionExists(): void
    {
        // Under the CLI test runner neither finish function exists, so there is nothing to do
        $closer = new SapiConnectionCloser();

        $this->assertSame('', $closer->strategy());
    }
}
