<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Http;

class Header
{
    public const INERTIA = 'X-Inertia';
    public const PARTIAL_COMPONENT = 'X-Inertia-Partial-Component';
    public const PARTIAL_ONLY = 'X-Inertia-Partial-Data';
    public const PARTIAL_EXCEPT = 'X-Inertia-Partial-Except';
}
