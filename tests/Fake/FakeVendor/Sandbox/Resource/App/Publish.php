<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App;

use BEAR\Resource\ResourceObject;
use FakeVendor\Sandbox\PublishLog;

class Publish extends ResourceObject
{
    public function __construct(private readonly PublishLog $log)
    {
    }

    public function onPost(string $id): static
    {
        $this->log->add($id);
        $this->code = 201;

        return $this;
    }
}
