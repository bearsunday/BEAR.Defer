<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App;

use BEAR\Resource\ResourceObject;

class Note extends ResourceObject
{
    public function onPost(string $id): static
    {
        $this->code = 201;

        return $this;
    }
}
