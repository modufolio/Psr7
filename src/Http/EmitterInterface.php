<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Http;

use Psr\Http\Message\ResponseInterface;

interface EmitterInterface
{
    public function emit(ResponseInterface $response): void;

}
