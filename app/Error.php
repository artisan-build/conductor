<?php

declare(strict_types=1);

namespace Conductor;

class Error
{
    public function __construct(
        public readonly string $type,
        public readonly string $message,
    ) {}
}
