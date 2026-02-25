<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Tests\Unit\Http\Factory;

use Modufolio\Psr7\Http\Factory\ServerRequestCreatorFactory;
use Modufolio\Psr7\Http\ServerRequestCreatorInterface;
use PHPUnit\Framework\TestCase;

class ServerRequestCreatorFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $creator = ServerRequestCreatorFactory::create();

        $this->assertInstanceOf(ServerRequestCreatorInterface::class, $creator);
    }
}