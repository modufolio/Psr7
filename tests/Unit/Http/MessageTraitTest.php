<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Tests\Unit\Http;

use Modufolio\Psr7\Http\Request;
use Modufolio\Psr7\Http\Stream;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests MessageTrait functionality through the Request class
 */
class MessageTraitTest extends TestCase
{
    public function testGetProtocolVersion(): void
    {
        $request = new Request('GET', '/');
        $this->assertEquals('1.1', $request->getProtocolVersion());
    }

    public function testWithProtocolVersion(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withProtocolVersion('2.0');

        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('2.0', $newRequest->getProtocolVersion());
        $this->assertNotSame($request, $newRequest);
    }

    public function testWithProtocolVersionReturnsSameInstanceWhenUnchanged(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withProtocolVersion('1.1');

        $this->assertSame($request, $newRequest);
    }

    public function testWithProtocolVersionThrowsExceptionForNonScalar(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Protocol version must be a string');

        $request = new Request('GET', '/');
        $request->withProtocolVersion([]);
    }

    public function testGetHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom' => 'value',
        ];
        $request = new Request('GET', '/', $headers);

        $this->assertEquals([
            'Content-Type' => ['application/json'],
            'X-Custom' => ['value'],
        ], $request->getHeaders());
    }

    public function testHasHeader(): void
    {
        $request = new Request('GET', '/', ['Content-Type' => 'application/json']);

        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertTrue($request->hasHeader('content-type'));
        $this->assertTrue($request->hasHeader('CONTENT-TYPE'));
        $this->assertFalse($request->hasHeader('X-Not-Exists'));
    }

    public function testGetHeader(): void
    {
        $request = new Request('GET', '/', ['Content-Type' => 'application/json']);

        $this->assertEquals(['application/json'], $request->getHeader('Content-Type'));
        $this->assertEquals(['application/json'], $request->getHeader('content-type'));
        $this->assertEquals([], $request->getHeader('X-Not-Exists'));
    }

    public function testGetHeaderThrowsExceptionForNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be an RFC 7230 compatible string');

        $request = new Request('GET', '/');
        $request->getHeader(123);
    }

    public function testGetHeaderLine(): void
    {
        $request = new Request('GET', '/', ['X-Multi' => ['value1', 'value2', 'value3']]);

        $this->assertEquals('value1, value2, value3', $request->getHeaderLine('X-Multi'));
        $this->assertEquals('', $request->getHeaderLine('X-Not-Exists'));
    }

    public function testWithHeader(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withHeader('Content-Type', 'application/json');

        $this->assertFalse($request->hasHeader('Content-Type'));
        $this->assertTrue($newRequest->hasHeader('Content-Type'));
        $this->assertEquals(['application/json'], $newRequest->getHeader('Content-Type'));
        $this->assertNotSame($request, $newRequest);
    }

    public function testWithHeaderReplacesExisting(): void
    {
        $request = new Request('GET', '/', ['Content-Type' => 'text/html']);
        $newRequest = $request->withHeader('Content-Type', 'application/json');

        $this->assertEquals(['text/html'], $request->getHeader('Content-Type'));
        $this->assertEquals(['application/json'], $newRequest->getHeader('Content-Type'));
    }

    public function testWithHeaderCaseInsensitive(): void
    {
        $request = new Request('GET', '/', ['Content-Type' => 'text/html']);
        $newRequest = $request->withHeader('content-type', 'application/json');

        $this->assertEquals(['application/json'], $newRequest->getHeader('Content-Type'));
    }

    public function testWithHeaderArrayValue(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withHeader('X-Multi', ['value1', 'value2']);

        $this->assertEquals(['value1', 'value2'], $newRequest->getHeader('X-Multi'));
    }

    public function testWithHeaderNumericValue(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withHeader('X-Number', 123);

        $this->assertEquals(['123'], $newRequest->getHeader('X-Number'));
    }

    public function testWithHeaderTrimsWhitespace(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withHeader('X-Test', '  value  ');

        $this->assertEquals(['value'], $newRequest->getHeader('X-Test'));
    }

    public function testWithHeaderThrowsExceptionForInvalidName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be an RFC 7230 compatible string');

        $request = new Request('GET', '/');
        $request->withHeader('Invalid Header Name', 'value');
    }

    public function testWithHeaderThrowsExceptionForEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be a string or an array of strings, empty array given');

        $request = new Request('GET', '/');
        $request->withHeader('X-Test', []);
    }

    public function testWithHeaderThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be RFC 7230 compatible strings');

        $request = new Request('GET', '/');
        $request->withHeader('X-Test', "invalid\x00value");
    }

    public function testWithAddedHeader(): void
    {
        $request = new Request('GET', '/', ['X-Test' => 'value1']);
        $newRequest = $request->withAddedHeader('X-Test', 'value2');

        $this->assertEquals(['value1'], $request->getHeader('X-Test'));
        $this->assertEquals(['value1', 'value2'], $newRequest->getHeader('X-Test'));
        $this->assertNotSame($request, $newRequest);
    }

    public function testWithAddedHeaderCreatesNewHeader(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withAddedHeader('X-New', 'value');

        $this->assertFalse($request->hasHeader('X-New'));
        $this->assertTrue($newRequest->hasHeader('X-New'));
        $this->assertEquals(['value'], $newRequest->getHeader('X-New'));
    }

    public function testWithAddedHeaderThrowsExceptionForNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be an RFC 7230 compatible string');

        $request = new Request('GET', '/');
        $request->withAddedHeader(123, 'value');
    }

    public function testWithAddedHeaderThrowsExceptionForEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be an RFC 7230 compatible string');

        $request = new Request('GET', '/');
        $request->withAddedHeader('', 'value');
    }

    public function testWithoutHeader(): void
    {
        $request = new Request('GET', '/', ['Content-Type' => 'application/json']);
        $newRequest = $request->withoutHeader('Content-Type');

        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertFalse($newRequest->hasHeader('Content-Type'));
        $this->assertNotSame($request, $newRequest);
    }

    public function testWithoutHeaderCaseInsensitive(): void
    {
        $request = new Request('GET', '/', ['Content-Type' => 'application/json']);
        $newRequest = $request->withoutHeader('content-type');

        $this->assertFalse($newRequest->hasHeader('Content-Type'));
    }

    public function testWithoutHeaderReturnsSameInstanceWhenNotExists(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withoutHeader('X-Not-Exists');

        $this->assertSame($request, $newRequest);
    }

    public function testGetBody(): void
    {
        $request = new Request('GET', '/', [], 'test body');

        $body = $request->getBody();
        $this->assertEquals('test body', (string)$body);
    }

    public function testGetBodyReturnsEmptyStreamWhenNoBodyProvided(): void
    {
        $request = new Request('GET', '/');

        $body = $request->getBody();
        $this->assertEquals('', (string)$body);
    }

    public function testWithBody(): void
    {
        $request = new Request('GET', '/');
        $stream = Stream::create('new body');
        $newRequest = $request->withBody($stream);

        $this->assertEquals('', (string)$request->getBody());
        $this->assertEquals('new body', (string)$newRequest->getBody());
        $this->assertNotSame($request, $newRequest);
    }

    public function testWithBodyReturnsSameInstanceWhenSameStream(): void
    {
        $stream = Stream::create('test');
        $request = new Request('GET', '/', [], $stream);
        $newRequest = $request->withBody($stream);

        $this->assertSame($request, $newRequest);
    }

    public function testNumericHeaderName(): void
    {
        $request = new Request('GET', '/', [123 => 'value']);

        $this->assertTrue($request->hasHeader('123'));
        $this->assertEquals(['value'], $request->getHeader('123'));
    }

    public function testMultipleHeadersWithSameNameDifferentCase(): void
    {
        $request = (new Request('GET', '/'))
            ->withHeader('X-Test', 'value1')
            ->withAddedHeader('x-test', 'value2');

        $this->assertEquals(['value1', 'value2'], $request->getHeader('X-Test'));
        $this->assertEquals(['value1', 'value2'], $request->getHeader('x-test'));
    }

    public function testHeaderPreservesOriginalCase(): void
    {
        $request = new Request('GET', '/', ['X-Custom-Header' => 'value']);

        $headers = $request->getHeaders();
        $this->assertArrayHasKey('X-Custom-Header', $headers);
    }

    public function testWithHeaderAllowsNumericStrings(): void
    {
        $request = new Request('GET', '/');
        $newRequest = $request->withHeader('X-Number', '12345');

        $this->assertEquals(['12345'], $newRequest->getHeader('X-Number'));
    }
}
