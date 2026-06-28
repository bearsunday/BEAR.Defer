<?php

declare(strict_types=1);

namespace BEAR\Defer;

interface ConnectionCloserInterface
{
    /**
     * Send the buffered response to the client and release the connection if the SAPI allows it
     *
     * On PHP-FPM / LiteSpeed the client connection is closed so deferred work runs without
     * keeping the client waiting. On other web SAPIs (e.g. Apache mod_php) this is best-effort:
     * output is flushed but the connection is not guaranteed to be released. On CLI it is a no-op.
     */
    public function __invoke(): void;
}
