<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Tests\Unit\Http;

use Modufolio\Psr7\Http\Factory\Psr17Factory;
use Modufolio\Psr7\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class ResponseTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $r = new Response();
        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('1.1', $r->getProtocolVersion());
        $this->assertSame('OK', $r->getReasonPhrase());
        $this->assertSame([], $r->getHeaders());
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('', (string)$r->getBody());
    }

    public function testCanConstructWithStatusCode(): void
    {
        $r = new Response(404);
        $this->assertSame(404, $r->getStatusCode());
        $this->assertSame('Not Found', $r->getReasonPhrase());
    }

    public function testCanConstructWithUndefinedStatusCode(): void
    {
        $r = new Response(999);
        $this->assertSame(999, $r->getStatusCode());
        $this->assertSame('', $r->getReasonPhrase());
    }

    public function testCanConstructWithStatusCodeAndEmptyReason(): void
    {
        $r = new Response(404);
        $this->assertSame(404, $r->getStatusCode());
        $this->assertSame('Not Found', $r->getReasonPhrase());
    }

    public function testConstructorDoesNotReadStreamBody(): void
    {
        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->expects($this->never())
            ->method('__toString');

        $r = new Response(200, [], $body);
        $this->assertSame($body, $r->getBody());
    }

    public function testCanConstructWithHeaders(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame('Bar', $r->getHeaderLine('Foo'));
        $this->assertSame(['Bar'], $r->getHeader('Foo'));
    }

    public function testCanConstructWithHeadersAsArray(): void
    {
        $r = new Response(200, [
            'Foo' => ['baz', 'bar'],
        ]);
        $this->assertSame(['Foo' => ['baz', 'bar']], $r->getHeaders());
        $this->assertSame('baz, bar', $r->getHeaderLine('Foo'));
        $this->assertSame(['baz', 'bar'], $r->getHeader('Foo'));
    }

    public function testCanConstructWithBody(): void
    {
        $r = new Response(200, [], 'baz');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('baz', (string)$r->getBody());
    }

    public function testFalseyBody(): void
    {
        $r = new Response(200, [], '0');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('0', (string)$r->getBody());
    }

    public function testCanConstructWithReason(): void
    {
        $r = new Response(reason: 'bar');
        $this->assertSame('bar', $r->getReasonPhrase());

        $r = new Response(reason: '0');
        $this->assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
    }

    public function testCanConstructWithProtocolVersion(): void
    {
        $r = new Response(version: '1000');
        $this->assertSame('1000', $r->getProtocolVersion());
    }

    public function testWithStatusCodeAndNoReason(): void
    {
        $r = (new Response())->withStatus(201);
        $this->assertSame(201, $r->getStatusCode());
        $this->assertSame('Created', $r->getReasonPhrase());
    }

    public function testWithStatusCodeAndReason(): void
    {
        $r = (new Response())->withStatus(201, 'Foo');
        $this->assertSame(201, $r->getStatusCode());
        $this->assertSame('Foo', $r->getReasonPhrase());

        $r = (new Response())->withStatus(201, '0');
        $this->assertSame(201, $r->getStatusCode());
        $this->assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
    }

    public function testWithProtocolVersion(): void
    {
        $r = (new Response())->withProtocolVersion('1000');
        $this->assertSame('1000', $r->getProtocolVersion());
    }

    public function testSameInstanceWhenSameProtocol(): void
    {
        $r = new Response();
        $this->assertSame($r, $r->withProtocolVersion('1.1'));
    }

    public function testWithBody(): void
    {
        $b = (new Psr17Factory())->createStream('0');
        $r = (new Response())->withBody($b);
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertSame('0', (string)$r->getBody());
    }

    public function testSameInstanceWhenSameBody(): void
    {
        $r = new Response();
        $b = $r->getBody();
        $this->assertSame($r, $r->withBody($b));
    }

    public function testWithHeader(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('baZ', 'Bam');
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam']], $r2->getHeaders());
        $this->assertSame('Bam', $r2->getHeaderLine('baz'));
        $this->assertSame(['Bam'], $r2->getHeader('baz'));
    }

    public function testWithHeaderAsArray(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('baZ', ['Bam', 'Bar']);
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam', 'Bar']], $r2->getHeaders());
        $this->assertSame('Bam, Bar', $r2->getHeaderLine('baz'));
        $this->assertSame(['Bam', 'Bar'], $r2->getHeader('baz'));
    }

    public function testWithHeaderReplacesDifferentCase(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withHeader('foO', 'Bam');
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['foO' => ['Bam']], $r2->getHeaders());
        $this->assertSame('Bam', $r2->getHeaderLine('foo'));
        $this->assertSame(['Bam'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeader(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('foO', 'Baz');
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar', 'Baz']], $r2->getHeaders());
        $this->assertSame('Bar, Baz', $r2->getHeaderLine('foo'));
        $this->assertSame(['Bar', 'Baz'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeaderAsArray(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('foO', ['Baz', 'Bam']);
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar', 'Baz', 'Bam']], $r2->getHeaders());
        $this->assertSame('Bar, Baz, Bam', $r2->getHeaderLine('foo'));
        $this->assertSame(['Bar', 'Baz', 'Bam'], $r2->getHeader('foo'));
    }

    public function testWithAddedHeaderThatDoesNotExist(): void
    {
        $r = new Response(200, ['Foo' => 'Bar']);
        $r2 = $r->withAddedHeader('nEw', 'Baz');
        $this->assertSame(['Foo' => ['Bar']], $r->getHeaders());
        $this->assertSame(['Foo' => ['Bar'], 'nEw' => ['Baz']], $r2->getHeaders());
        $this->assertSame('Baz', $r2->getHeaderLine('new'));
        $this->assertSame(['Baz'], $r2->getHeader('new'));
    }

    public function testWithoutHeaderThatExists(): void
    {
        $r = new Response(200, ['Foo' => 'Bar', 'Baz' => 'Bam']);
        $r2 = $r->withoutHeader('foO');
        $this->assertTrue($r->hasHeader('foo'));
        $this->assertSame(['Foo' => ['Bar'], 'Baz' => ['Bam']], $r->getHeaders());
        $this->assertFalse($r2->hasHeader('foo'));
        $this->assertSame(['Baz' => ['Bam']], $r2->getHeaders());
    }

    public function testWithoutHeaderThatDoesNotExist(): void
    {
        $r = new Response(200, ['Baz' => 'Bam']);
        $r2 = $r->withoutHeader('foO');
        $this->assertSame($r, $r2);
        $this->assertFalse($r2->hasHeader('foo'));
        $this->assertSame(['Baz' => ['Bam']], $r2->getHeaders());
    }

    public function testSameInstanceWhenRemovingMissingHeader(): void
    {
        $r = new Response();
        $this->assertSame($r, $r->withoutHeader('foo'));
    }

    public static function trimmedHeaderValues(): array
    {
        return [
            [new Response(200, ['OWS' => " \t \tFoo\t \t "])],
            [(new Response())->withHeader('OWS', " \t \tFoo\t \t ")],
            [(new Response())->withAddedHeader('OWS', " \t \tFoo\t \t ")],
        ];
    }

    #[DataProvider('trimmedHeaderValues')]
    public function testHeaderValuesAreTrimmed($r): void
    {
        $this->assertSame(['OWS' => ['Foo']], $r->getHeaders());
        $this->assertSame('Foo', $r->getHeaderLine('OWS'));
        $this->assertSame(['Foo'], $r->getHeader('OWS'));
    }


    public function invalidWithHeaderProvider(): iterable
    {
        return [
            ['foo', [], 'Header values must be a string or an array of strings, empty array given'],
            ['foo', new \stdClass(),  'Header values must be RFC 7230 compatible strings'],
            [[], 'foo', 'Header name must be an RFC 7230 compatible string'],
            [false, 'foo', 'Header name must be an RFC 7230 compatible string'],
            [new \stdClass(), 'foo', 'Header name must be an RFC 7230 compatible string'],
            ['', 'foo', 'Header name must be an RFC 7230 compatible string'],
            ["Content-Type\r\n\r\n", 'foo', 'Header name must be an RFC 7230 compatible string'],
            ["Content-Type\r\n", 'foo', 'Header name must be an RFC 7230 compatible string'],
            ["Content-Type\n", 'foo', 'Header name must be an RFC 7230 compatible string'],
            ["\r\nContent-Type", 'foo', 'Header name must be an RFC 7230 compatible string'],
            ["\nContent-Type", 'foo', 'Header name must be an RFC 7230 compatible string'],
            ["\n", 'foo', 'Header name must be an RFC 7230 compatible string'],
            ["\r\n", 'foo', 'Header name must be an RFC 7230 compatible string'],
            ["\t", 'foo', 'Header name must be an RFC 7230 compatible string'],
        ];
    }

    public function testEmptyResponse(): void
    {
        $r = Response::empty();
        $this->assertSame(204, $r->getStatusCode());
        $this->assertSame('', (string)$r->getBody());
    }

    public function testJsonResponse(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $r = Response::json($data);

        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('application/json', $r->getHeaderLine('Content-Type'));

        $decoded = json_decode((string)$r->getBody(), true);
        $this->assertSame($data, $decoded);
    }

    public function testJsonResponseWithCustomStatus(): void
    {
        $data = ['error' => 'not found'];
        $r = Response::json($data, 404);

        $this->assertSame(404, $r->getStatusCode());
        $this->assertSame('application/json', $r->getHeaderLine('Content-Type'));
    }

    public function testJsonResponseWithPrettyPrint(): void
    {
        $data = ['foo' => 'bar'];
        $r = Response::json($data, pretty: true);

        $body = (string)$r->getBody();
        $this->assertStringContainsString("\n", $body);
        $this->assertStringContainsString('    ', $body); // Indentation
    }

    public function testJsonResponseWithString(): void
    {
        $jsonString = '{"foo":"bar"}';
        $r = Response::json($jsonString);

        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('application/json', $r->getHeaderLine('Content-Type'));
        $this->assertSame('{"foo":"bar"}', (string)$r->getBody());
    }

    public function testJsonResponseWithCustomHeaders(): void
    {
        $data = ['foo' => 'bar'];
        $r = Response::json($data, headers: ['X-Custom' => 'value']);

        $this->assertSame('value', $r->getHeaderLine('X-Custom'));
        $this->assertSame('application/json', $r->getHeaderLine('Content-Type'));
    }

    public function testJsonResponseThrowsOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Invalid JSON payload');

        Response::json('not valid json');
    }

    public function testUnauthorizedResponse(): void
    {
        $r = Response::unauthorized();

        $this->assertSame(401, $r->getStatusCode());
        $this->assertSame('Unauthorized', (string)$r->getBody());
    }

    public function testUnauthorizedResponseWithCustomMessage(): void
    {
        $r = Response::unauthorized('Please log in');

        $this->assertSame(401, $r->getStatusCode());
        $this->assertSame('Please log in', (string)$r->getBody());
    }

    public function testUnavailableResponse(): void
    {
        $r = Response::unavailable();

        $this->assertSame(503, $r->getStatusCode());
        $this->assertSame('Service Unavailable', (string)$r->getBody());
    }

    public function testUnavailableResponseWithCustomMessage(): void
    {
        $r = Response::unavailable('Maintenance mode');

        $this->assertSame(503, $r->getStatusCode());
        $this->assertSame('Maintenance mode', (string)$r->getBody());
    }

    public function testTooManyRequestsResponse(): void
    {
        $r = Response::tooManyRequests();

        $this->assertSame(429, $r->getStatusCode());
        $this->assertSame('Too Many Requests', (string)$r->getBody());
    }

    public function testTooManyRequestsResponseWithCustomMessage(): void
    {
        $r = Response::tooManyRequests('Rate limit exceeded');

        $this->assertSame(429, $r->getStatusCode());
        $this->assertSame('Rate limit exceeded', (string)$r->getBody());
    }

    public function testHtmlResponse(): void
    {
        $html = '<h1>Hello World</h1>';
        $r = Response::html($html);

        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('text/html', $r->getHeaderLine('Content-Type'));
        $this->assertSame($html, (string)$r->getBody());
    }

    public function testHtmlResponseWithCustomStatus(): void
    {
        $html = '<h1>Not Found</h1>';
        $r = Response::html($html, 404);

        $this->assertSame(404, $r->getStatusCode());
        $this->assertSame('text/html', $r->getHeaderLine('Content-Type'));
        $this->assertSame($html, (string)$r->getBody());
    }

    public function testRedirectResponse(): void
    {
        $url = '/dashboard';
        $r = Response::redirect($url);

        $this->assertSame(302, $r->getStatusCode());
        $this->assertSame($url, $r->getHeaderLine('Location'));

        $body = (string)$r->getBody();
        $this->assertStringContainsString('<!DOCTYPE html>', $body);
        $this->assertStringContainsString($url, $body);
    }

    public function testRedirectResponseWith301(): void
    {
        $url = '/new-location';
        $r = Response::redirect($url, 301);

        $this->assertSame(301, $r->getStatusCode());
        $this->assertSame($url, $r->getHeaderLine('Location'));
    }

    public function testRedirectResponseEscapesUrl(): void
    {
        $url = '/page?foo=bar&baz=<script>';
        $r = Response::redirect($url);

        $body = (string)$r->getBody();
        $this->assertStringContainsString('&lt;script&gt;', $body);
        $this->assertStringNotContainsString('<script>', $body);
    }

    public function testRedirectResponseThrowsOnInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The redirect status code must be one of: 301, 302, 303, 307, 308');

        Response::redirect('/test', 305);
    }
}
