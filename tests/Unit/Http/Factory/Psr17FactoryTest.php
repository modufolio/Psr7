<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Tests\Unit\Http\Factory;

use Modufolio\Psr7\Http\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class Psr17FactoryTest extends TestCase
{
    protected Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testCreateRequest(): void
    {
        $request = $this->factory->createRequest('GET', 'http://example.com');

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('http://example.com', (string)$request->getUri());
    }


    public function testCreateResponse(): void
    {

        $r = $this->factory->createResponse(200);
        $this->assertEquals('OK', $r->getReasonPhrase());

        $r = $this->factory->createResponse(200, 'Foo');
        $this->assertEquals('Foo', $r->getReasonPhrase());

        // Test for non-standard response codes
        $r = $this->factory->createResponse(567);
        $this->assertEquals('', $r->getReasonPhrase());

        $r = $this->factory->createResponse(567, '');
        $this->assertEquals(567, $r->getStatusCode());
        $this->assertEquals('', $r->getReasonPhrase());

        $r = $this->factory->createResponse(567, 'Foo');
        $this->assertEquals(567, $r->getStatusCode());
        $this->assertEquals('Foo', $r->getReasonPhrase());
    }
}
