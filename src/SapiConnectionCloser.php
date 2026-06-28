<?php

declare(strict_types=1);

namespace BEAR\Defer;

use Override;

use function in_array;
use function is_callable;

use const PHP_SAPI;

/**
 * Closes the client connection after the response is sent, when the SAPI supports it
 *
 * Mirrors the strategy used by mainstream frameworks: prefer fastcgi_finish_request()
 * (PHP-FPM) or litespeed_finish_request() (LiteSpeed) for a real early return; fall back
 * to flush() on other web SAPIs (best-effort, e.g. Apache mod_php); do nothing on CLI
 * where there is no client connection to release.
 */
final class SapiConnectionCloser implements ConnectionCloserInterface
{
    /** @var callable(string): bool */
    private $functionExists;
    private readonly string $sapi;

    /** @param (callable(string): bool)|null $functionExists overridable for testing; defaults to the built-in function_exists() */
    public function __construct(string|null $sapi = null, callable|null $functionExists = null)
    {
        $this->sapi = $sapi ?? PHP_SAPI;
        $this->functionExists = $functionExists ?? 'function_exists';
    }

    #[Override]
    public function __invoke(): void
    {
        $finish = $this->strategy();
        if (is_callable($finish)) {
            $finish();
        }
    }

    /**
     * Name of the function that releases the client connection, or '' when there is nothing to do
     *
     * @return 'fastcgi_finish_request'|'litespeed_finish_request'|'flush'|''
     */
    public function strategy(): string
    {
        if (($this->functionExists)('fastcgi_finish_request')) {
            return 'fastcgi_finish_request';
        }

        if (($this->functionExists)('litespeed_finish_request')) {
            return 'litespeed_finish_request';
        }

        if (in_array($this->sapi, ['cli', 'phpdbg', 'embed'], true)) {
            return ''; // no client connection to release
        }

        // Other web SAPIs (e.g. Apache mod_php): best-effort flush, no guaranteed release
        return 'flush';
    }
}
