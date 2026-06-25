# BEAR.Defer

[![Continuous Integration](https://github.com/bearsunday/BEAR.Defer/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/bearsunday/BEAR.Defer/actions/workflows/continuous-integration.yml)
[![Static Analysis](https://github.com/bearsunday/BEAR.Defer/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/bearsunday/BEAR.Defer/actions/workflows/static-analysis.yml)

Deferred resource requests for BEAR.Resource — run resource requests *after* the response is transferred.

A resource accepts a request, returns `202 Accepted` immediately, and the heavy follow-up work (indexing, notification, …) runs after the response has been sent to the client. The resource only declares *what* to defer; *when* and *where* it runs is decided by bindings, outside the resource — so the same application code works on CLI, PHP-FPM, Swoole, or a queue without change.

Design background: [bearsunday/BEAR.Resource#373](https://github.com/bearsunday/BEAR.Resource/issues/373).

## Requirements

- PHP 8.2+
- bear/resource ^1.32

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

### 3. Install the module

`DeferModule` decorates `TransferInterface`. Provide your real responder as the `'base'` transfer (it defaults to `NullResponder`).

```php
use BEAR\Defer\Module\DeferModule;
use BEAR\Resource\TransferInterface;

protected function configure(): void
{
    $this->install(new DeferModule());
    $this->bind(TransferInterface::class)->annotatedWith('base')->to(YourHttpResponder::class);
}
```

`#[Defer]` references `#[Link]` rels, so the deferred transition stays hypermedia-driven and surfaces in ALPS as a deferred transition.

## How it works

- **`DeferInterceptor`** — an *After* interceptor bound to `#[Defer]`. Once the method has run (so the body is set), it resolves each `#[Link]` href against the body and enqueues a `Request` on `DeferInterface`. Collecting at execution time means `#[Defer]` on `#[Embed]`-ed child resources is captured too.
- **`DeferTransfer`** — decorates `TransferInterface`: runs the base transfer ("how to send"), then calls `DeferInterface::flush()` ("flush after send").
- **Binding** — the base transfer carries the `'base'` qualifier; the application swaps it for the real responder. The resource never sees any of this.

## Execution strategy

The strategy is chosen by binding `DeferInterface`; the application code never changes.

| Implementation | Where | Concurrency | Package |
|---|---|---|---|
| `SyncDefer` (bundled) | same process, post-transfer | sequential | this package |
| `AsyncDefer` | same process, post-transfer | Fiber concurrent | BEAR.Async adapter |
| `QueueDefer` | separate process | worker-distributed | queue adapter |

`SyncDefer` keeps the request-local queue in a singleton cleared on every `flush()`, so it is correct on PHP-FPM / CLI as long as `flush()` runs for every request. Concurrent and out-of-process strategies are provided as separate adapter packages.

## Error handling

| Exception | When |
|---|---|
| `InvalidDeferRelException` | `#[Defer]` is given an empty rel or an empty list |
| `LinkRelNotFoundException` | a `#[Defer]` rel has no matching `#[Link]` |
| `DeferFlushException` | one or more deferred requests threw during `flush()` |

The first two are configuration errors raised at interceptor time — *before* the response is sent — so they surface as a normal error instead of a silently failed `202`. `DeferFlushException` is raised after transfer: `flush()` runs every queued request even if some throw, then aggregates the failures (`$e->errors`) so one failure never drops the rest.

## Swoole / long-running workers

`DeferInterface` is a singleton whose queue is cleared at the request boundary by `flush()`. This gives per-request flushing on PHP-FPM / CLI without relying on process isolation. On a strictly coroutine-concurrent runtime where a single worker interleaves requests, per-request isolation must be provided by the runtime adapter; the core package does not address coroutine isolation.

## License

MIT — see [LICENSE](LICENSE).
