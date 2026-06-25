<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App;

use BEAR\Defer\Attribute\Defer;
use BEAR\Resource\ResourceObject;

class BadRel extends ResourceObject
{
    #[Defer(['missing'])]
    public function onGet(): static
    {
        $this->body = [];

        return $this;
    }
}
