<?php

declare(strict_types=1);

namespace BEAR\Defer;

use Override;

use function flush;
use function function_exists;

use const PHP_SAPI;

/**
 * Closes the client connection after the response is sent, when the SAPI supports it
 *
 * Releases the connection on PHP-FPM (fastcgi_finish_request) and LiteSpeed
 * (litespeed_finish_request) so deferred work runs without keeping the client
 * waiting. Other web SAPIs (e.g. Apache mod_php) fall back to flush(), which is
 * best-effort and does not guarantee release. CLI has no client connection.
 *
 * Runtimes that need a different mechanism (e.g. Swoole's $response->end()) bind
 * their own ConnectionCloserInterface and replace this entirely.
 */
final class SapiConnectionCloser implements ConnectionCloserInterface
{
    #[Override]
    public function __invoke(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();

            return;
        }

        if (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();

            return;
        }

        if (PHP_SAPI !== 'cli') {
            flush(); // best-effort on other web SAPIs (e.g. Apache mod_php)
        }
    }
}
