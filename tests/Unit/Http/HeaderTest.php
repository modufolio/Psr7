<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Tests\Unit\Http;

use Modufolio\Psr7\Http\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function testInertiaConstant(): void
    {
        $this->assertEquals('X-Inertia', Header::INERTIA);
    }

    public function testPartialComponentConstant(): void
    {
        $this->assertEquals('X-Inertia-Partial-Component', Header::PARTIAL_COMPONENT);
    }

    public function testPartialOnlyConstant(): void
    {
        $this->assertEquals('X-Inertia-Partial-Data', Header::PARTIAL_ONLY);
    }

    public function testPartialExceptConstant(): void
    {
        $this->assertEquals('X-Inertia-Partial-Except', Header::PARTIAL_EXCEPT);
    }
}
