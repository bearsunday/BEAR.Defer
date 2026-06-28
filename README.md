# BEAR.Defer

[![Continuous Integration](https://github.com/bearsunday/BEAR.Defer/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/bearsunday/BEAR.Defer/actions/workflows/continuous-integration.yml)
[![Static Analysis](https://github.com/bearsunday/BEAR.Defer/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/bearsunday/BEAR.Defer/actions/workflows/static-analysis.yml)

Deferred resource requests for BEAR.Resource — run resource requests *after* the response is transferred.

A resource accepts a request, returns `202 Accepted` immediately, and the heavy follow-up work (indexing, notification, …) runs after the response has been sent to the client. The resource only declares *what* to defer; *when* and *where* it runs is decided by bindings, outside the resource — so the same application code works on CLI, PHP-FPM, Swoole, or a queue without change.

Design background: [bearsunday/BEAR.Resource#373](https://github.com/bearsunday/BEAR.Resource/issues/373).

## Installation

```bash
composer require bear/defer
```

## Usage

### 1. Declare what to defer

Annotate the accepting resource with `#[Defer]`, listing `#[Link]` rels (no hardcoded URIs). Each rel's `href` is resolved against the resource body after the method runs.

```php
use BEAR\Defer\Attribute\Defer;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
    ) {
    }

    #[Defer(['publish', 'release-note'])]
    #[Link(rel: 'publish',      href: 'app://self/article/publish{?id}', method: 'post')]
    #[Link(rel: 'release-note', href: 'app://self/release-note{?id}',    method: 'post')]
    public function onPost(string $title, string $body): static
    {
        $id = $this->articles->save($title, $body); // light work only
        $this->code = 202;                           // Accepted
        $this->body = ['id' => $id];

        return $this; // no defer call in the body
    }
}
```

### 2. The follow-up resources are ordinary resources

They don't know they are deferred — any resource can be the target.

```php
class Publish extends ResourceObject
{
    public function onPost(string $id): static
    {
        $this->indexer->index($id);   // heavy work, runs after the response is sent
        $this->notifier->notify($id);

        return $this;
    }
}
```

### Conditional defer

When the follow-up work is conditional, inject `DeferInterface` and `ResourceInterface` directly and call `$defer->add()` manually. This bypasses `#[Defer]` and gives full control over what gets enqueued.

```php
use BEAR\Defer\DeferInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

class Article extends ResourceObject
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly DeferInterface $defer,
    ) {
    }

    public function onPost(string $title, string $body, bool $publish = false): static
    {
        $id = $this->articles->save($title, $body);
        $this->code = 202;
        $this->body = ['id' => $id];

        if ($publish) {
            $request = $this->resource->post->uri('app://self/publish')->withQuery(['id' => $id]);
            $this->defer->add($request);
        }

        return $this;
    }
}
```

### 3. Install the module

`DeferModule` decorates an existing `TransferInterface` binding. Pass the module that provides your real responder to the `DeferModule` constructor; `rename()` moves that binding to the `'inner'` qualifier automatically.

```php
use BEAR\Defer\Module\DeferModule;

protected function configure(): void
{
    $this->install(new DeferModule(new YourHttpResponderModule()));
}
```

`#[Defer]` references `#[Link]` rels, so the deferred transition stays hypermedia-driven and surfaces in ALPS as a deferred transition.

## How it works

- **`DeferInterceptor`** — an *After* interceptor bound to `#[Defer]`. Once the method has run (so the body is set), it resolves each `#[Link]` href against the body and enqueues a `Request` on `DeferInterface`. Collecting at execution time means `#[Defer]` on `#[Embed]`-ed child resources is captured too.
- **`DeferTransfer`** — decorates `TransferInterface`: runs the base transfer ("how to send"), releases the client connection via `ConnectionCloserInterface` when the SAPI supports it, then calls `DeferInterface::flush()` ("flush after send").
- **Binding** — `DeferModule` receives the responder module via its constructor. `rename(TransferInterface::class, 'inner')` moves that module's `TransferInterface` binding to the `'inner'` qualifier, then `DeferTransfer` is bound as the new `TransferInterface`. The resource never sees any of this.

## Execution strategy

The bundled `SyncDefer` runs deferred requests sequentially, in-process, after the transfer. It keeps request-local state in a singleton cleared on every `flush()`, so it is correct on PHP-FPM / CLI as long as `flush()` runs for every request.

The strategy is chosen by binding `DeferInterface`; the application code (`#[Defer]`) never changes.

## Releasing the client connection

So the client gets its response *before* the deferred work runs, `DeferTransfer` releases the connection between the transfer and the flush, through `ConnectionCloserInterface` (default: `SapiConnectionCloser`):

- **PHP-FPM** — `fastcgi_finish_request()`
- **LiteSpeed** — `litespeed_finish_request()`
- **Other web SAPIs** (e.g. Apache `mod_php`) — `flush()`, best-effort; no guaranteed early return
- **CLI** — nothing to release

A runtime with a different mechanism (e.g. Swoole's `$response->end()`) binds its own `ConnectionCloserInterface` to replace it.

## Swoole / long-running workers

`DeferInterface` is a singleton whose queue is cleared at the request boundary by `flush()`. This gives per-request flushing on PHP-FPM / CLI without relying on process isolation. On a strictly coroutine-concurrent runtime where a single worker interleaves requests, per-request isolation must be provided by the runtime adapter; the core package does not address coroutine isolation.

