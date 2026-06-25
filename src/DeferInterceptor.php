<?php

declare(strict_types=1);

namespace BEAR\Defer;

use BEAR\Defer\Attribute\Defer;
use BEAR\Defer\Exception\LinkRelNotFoundException;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Method;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use Override;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Set;
use Ray\Di\ProviderInterface;
use ReflectionAttribute;

use function assert;
use function uri_template;

/**
 * Turns #[Defer] rels into deferred resource requests after the method runs
 *
 * After interceptor: proceed() first so the resource body is set, then resolve
 * each #[Link] href against the body and enqueue a Request on DeferInterface.
 */
final readonly class DeferInterceptor implements DeferInterceptorInterface
{
    /** @param ProviderInterface<ResourceInterface> $resourceProvider */
    public function __construct(
        #[Set(ResourceInterface::class)]
        private ProviderInterface $resourceProvider,
        private DeferInterface $defer,
    ) {
    }

    /** {@inheritDoc} */
    #[Override]
    public function invoke(MethodInvocation $invocation): ResourceObject
    {
        $ro = $invocation->proceed();
        assert($ro instanceof ResourceObject);
        $method = $invocation->getMethod();
        $defer = $method->getAnnotation(Defer::class);
        assert($defer instanceof Defer);
        $links = $this->linkMap($method->getAttributes(Link::class));
        $resource = $this->resourceProvider->get();
        foreach ($defer->rels as $rel) {
            $link = $links[$rel] ?? throw new LinkRelNotFoundException($rel);
            $uri = uri_template($link->href, (array) $ro->body);
            $this->defer->add($resource->newRequest(Method::from($link->method), $uri));
        }

        return $ro;
    }

    /**
     * @param list<ReflectionAttribute<Link>> $linkAttrs
     *
     * @return array<string, Link>
     */
    private function linkMap(array $linkAttrs): array
    {
        $map = [];
        foreach ($linkAttrs as $attr) {
            $link = $attr->newInstance();
            $map[$link->rel] = $link;
        }

        return $map;
    }
}
