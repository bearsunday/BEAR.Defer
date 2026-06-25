# BEAR.Defer

Deferred resource requests for BEAR.Resource — run resource requests *after* the response is transferred.

A resource accepts a request, returns `202 Accepted` immediately, and the heavy follow-up work (indexing, notification, ...) runs after the response has been sent to the client.

See the design RFC: [bearsunday/BEAR.Resource#373](https://github.com/bearsunday/BEAR.Resource/issues/373).

## Installation

```bash
composer require bear/defer
```

## Usage

Declare the follow-up transitions with `#[Defer]`, referencing `#[Link]` rels so no URI is hardcoded:

```php
use BEAR\Defer\Attribute\Defer;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Article extends ResourceObject
{
    #[Defer(['publish', 'release-note'])]
    #[Link(rel: 'publish', href: 'app://self/article/publish{?id}', method: 'post')]
    #[Link(rel: 'release-note', href: 'app://self/release-note{?id}', method: 'post')]
    public function onPost(string $title, string $body): static
    {
        $id = $this->articles->save($title, $body); // light work only
        $this->code = 202;                           // Accepted
        $this->body = ['id' => $id];

        return $this; // no defer call in the body
    }
}
```

Install the module:

```php
use BEAR\Defer\Module\DeferModule;

$this->install(new DeferModule());
```

`#[Defer]` references `#[Link]` rels, so the deferred transition stays hypermedia-driven and surfaces in ALPS as a deferred transition. Each rel's `href` is resolved against the resource body after the method runs, and the resulting request is enqueued.

## How it works

- `DeferInterceptor` — an *After* interceptor bound to `#[Defer]`. Once the method has run (so the body is set), it resolves each `#[Link]` href against the body and enqueues a `Request` on `DeferInterface`.
- `DeferAwareTransfer` — decorates `TransferInterface`. It runs the base transfer ("how to send"), then calls `DeferInterface::flush()` ("flush after send").
- The base transfer is injected with the `'base'` qualifier and defaults to `NullResponder`. The application overrides it with the real responder:

```php
$this->bind(TransferInterface::class)->annotatedWith('base')->to(YourHttpResponder::class);
```

So the resource only declares *what* to defer; *when* and *where* it runs is decided by the bindings, outside the resource.

## Execution strategy

The bundled `SyncDefer` runs deferred requests sequentially, in-process, after the transfer. It is bound as a singleton and cleared on every `flush()`, so it is correct on PHP-FPM / CLI (one request per process).

Concurrent (`AsyncDefer`, Fiber / BEAR.Async) and out-of-process (`QueueDefer`) strategies are provided as separate adapter packages — the application code (`#[Defer]`) does not change, only the binding does.

## Swoole / long-running workers

`DeferInterface` is request-scoped by being a singleton that `flush()` clears at the request boundary. On a strictly coroutine-concurrent runtime where a single worker interleaves requests, per-request isolation must be provided by the runtime adapter; the core package does not address coroutine isolation.
