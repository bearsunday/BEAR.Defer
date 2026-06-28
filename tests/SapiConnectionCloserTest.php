<?php

declare(strict_types=1);

namespace BEAR\Defer;

use PHPUnit\Framework\TestCase;

use function ob_get_clean;
use function ob_start;

final class SapiConnectionCloserTest extends TestCase
{
    public function testRunsWithoutOutputUnderTheTestRunner(): void
    {
        // CLI has no client connection to release; the call must complete without emitting output
        $closer = new SapiConnectionCloser();

        ob_start();
        $closer();

        $this->assertSame('', ob_get_clean());
    }
}
