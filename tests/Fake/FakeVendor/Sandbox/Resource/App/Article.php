<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App;

use BEAR\Defer\Attribute\Defer;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Article extends ResourceObject
{
    #[Defer(['publish', 'note'])]
    #[Link(rel: 'publish', href: 'app://self/publish{?id}', method: 'post')]
    #[Link(rel: 'note', href: 'app://self/note{?id}', method: 'post')]
    public function onPost(string $title, string $body): static
    {
        $this->code = 202;
        $this->body = ['id' => '123'];

        return $this;
    }
}
