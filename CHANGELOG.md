# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-06-29

### Added
- Initial release: deferred resource requests for BEAR.Resource — run resource requests *after* the response is transferred, so a resource can return `202 Accepted` immediately and let heavy follow-up work run once the client has its response
- `#[Defer]` attribute declaring which `#[Link]` rels to defer; each `href` is resolved against the resource body after the method runs, so `#[Defer]` on `#[Embed]`-ed child resources is captured too
- `DeferInterface` (`add()` / `flush()`) with the bundled `SyncDefer`, a sequential in-process implementation kept as a request-scoped singleton cleared on every `flush()`
- `DeferModule` decorating an existing `TransferInterface` binding; the responder module is moved to the `'inner'` qualifier and `DeferTransfer` is bound as the new `TransferInterface`
- `ConnectionCloserInterface` with the default `SapiConnectionCloser`, releasing the client connection after transfer via `fastcgi_finish_request()` (PHP-FPM) or `litespeed_finish_request()` (LiteSpeed), falling back to `flush()` (best-effort) on other web SAPIs and doing nothing on CLI
- Conditional defer by injecting `DeferInterface` and enqueuing with `add()`
- `DeferFlushException` aggregating failures so one failing deferred request does not drop the rest

[Unreleased]: https://github.com/bearsunday/BEAR.Defer/compare/0.1.0...HEAD
[0.1.0]: https://github.com/bearsunday/BEAR.Defer/releases/tag/0.1.0
