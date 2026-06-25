<?php

declare(strict_types=1);

namespace BEAR\Defer\Exception;

use function sprintf;

final class LinkRelNotFoundException extends LogicException
{
    public function __construct(string $rel)
    {
        parent::__construct(sprintf('No #[Link] found for rel "%s" declared in #[Defer]', $rel));
    }
}
