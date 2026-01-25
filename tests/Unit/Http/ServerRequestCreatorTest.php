<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Tests\Unit\Http;

use Modufolio\Psr7\Http\Factory\Psr17Factory;
use Modufolio\Psr7\Http\ServerRequestCreator;
use Modufolio\Psr7\Http\Stream;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

class ServerRequestCreatorTest extends TestCase
{
    private ServerRequestCreator $creator;
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
        $this->creator = new ServerRequestCreator(
            $this->factory,
            $this->factory,
            $this->factory,
            $this->factory
        );
    }

    public function testFromArraysCreatesBasicServerRequest(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getUri()->getPath());
        $this->assertEquals('1.1', $request->getProtocolVersion());
    }

    public function testFromArraysWithHeaders(): void
    {
        $server = ['REQUEST_METHOD' => 'GET'];
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'custom-value',
        ];

        $request = $this->creator->fromArrays($server, $headers);

        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertEquals(['application/json'], $request->getHeader('Content-Type'));
        $this->assertEquals(['custom-value'], $request->getHeader('X-Custom-Header'));
    }

    public function testFromArraysWithNumericHeader(): void
    {
        $server = ['REQUEST_METHOD' => 'GET'];
        $headers = [
            123 => 'numeric-header-value',
        ];

        $request = $this->creator->fromArrays($server, $headers);

        $this->assertTrue($request->hasHeader('123'));
    }

    public function testFromArraysWithCookies(): void
    {
        $server = ['REQUEST_METHOD' => 'GET'];
        $cookies = ['session' => 'abc123', 'user' => 'john'];

        $request = $this->creator->fromArrays($server, [], $cookies);

        $this->assertEquals(['session' => 'abc123', 'user' => 'john'], $request->getCookieParams());
    }

    public function testFromArraysWithQueryParams(): void
    {
        $server = ['REQUEST_METHOD' => 'GET'];
        $get = ['foo' => 'bar', 'baz' => 'qux'];

        $request = $this->creator->fromArrays($server, [], [], $get);

        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $request->getQueryParams());
    }

    public function testFromArraysWithParsedBody(): void
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $post = ['username' => 'john', 'password' => 'secret'];

        $request = $this->creator->fromArrays($server, [], [], [], $post);

        $this->assertEquals(['username' => 'john', 'password' => 'secret'], $request->getParsedBody());
    }

    public function testFromArraysWithStringBody(): void
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $body = 'test body content';

        $request = $this->creator->fromArrays($server, [], [], [], null, [], $body);

        $this->assertEquals('test body content', (string)$request->getBody());
    }

    public function testFromArraysWithResourceBody(): void
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'resource content');
        rewind($resource);

        $request = $this->creator->fromArrays($server, [], [], [], null, [], $resource);

        $this->assertStringContainsString('resource content', (string)$request->getBody());
        fclose($resource);
    }

    public function testFromArraysWithStreamBody(): void
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $stream = Stream::create('stream content');

        $request = $this->creator->fromArrays($server, [], [], [], null, [], $stream);

        $this->assertEquals('stream content', (string)$request->getBody());
    }

    public function testFromArraysWithInvalidBodyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The $body parameter to ServerRequestCreator::fromArrays must be string, resource or StreamInterface');

        $server = ['REQUEST_METHOD' => 'POST'];
        $this->creator->fromArrays($server, [], [], [], null, [], 123);
    }

    public function testFromArraysWithUploadedFiles(): void
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $files = [
            'avatar' => [
                'tmp_name' => __FILE__,
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'size' => filesize(__FILE__),
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $request = $this->creator->fromArrays($server, [], [], [], null, $files);
        $uploadedFiles = $request->getUploadedFiles();

        $this->assertArrayHasKey('avatar', $uploadedFiles);
        $this->assertInstanceOf(UploadedFileInterface::class, $uploadedFiles['avatar']);
        $this->assertEquals('avatar.jpg', $uploadedFiles['avatar']->getClientFilename());
    }

    public function testFromArraysWithNestedUploadedFiles(): void
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $files = [
            'files' => [
                'tmp_name' => [__FILE__, __FILE__],
                'name' => ['file1.txt', 'file2.txt'],
                'type' => ['text/plain', 'text/plain'],
                'size' => [filesize(__FILE__), filesize(__FILE__)],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            ],
        ];

        $request = $this->creator->fromArrays($server, [], [], [], null, $files);
        $uploadedFiles = $request->getUploadedFiles();

        $this->assertArrayHasKey('files', $uploadedFiles);
        $this->assertIsArray($uploadedFiles['files']);
        $this->assertCount(2, $uploadedFiles['files']);
        $this->assertEquals('file1.txt', $uploadedFiles['files'][0]->getClientFilename());
        $this->assertEquals('file2.txt', $uploadedFiles['files'][1]->getClientFilename());
    }

    public function testFromArraysWithUploadError(): void
    {
        $server = ['REQUEST_METHOD' => 'POST'];
        $files = [
            'avatar' => [
                'tmp_name' => '',
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'size' => 0,
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ];

        $request = $this->creator->fromArrays($server, [], [], [], null, $files);
        $uploadedFiles = $request->getUploadedFiles();

        $this->assertArrayHasKey('avatar', $uploadedFiles);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $uploadedFiles['avatar']->getError());
    }

    public function testFromArraysWithProtocolVersion(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/2.0',
        ];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals('2.0', $request->getProtocolVersion());
    }

    public function testFromArraysDefaultsToHttp11(): void
    {
        $server = ['REQUEST_METHOD' => 'GET'];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals('1.1', $request->getProtocolVersion());
    }

    public function testFromArraysMissingRequestMethodThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot determine HTTP method');

        $this->creator->fromArrays([]);
    }

    public function testGetHeadersFromServerWithHttpHeaders(): void
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CUSTOM_HEADER' => 'custom-value',
        ];

        $headers = ServerRequestCreator::getHeadersFromServer($server);

        $this->assertArrayHasKey('host', $headers);
        $this->assertEquals('example.com', $headers['host']);
        $this->assertArrayHasKey('accept', $headers);
        $this->assertEquals('application/json', $headers['accept']);
        $this->assertArrayHasKey('x-custom-header', $headers);
        $this->assertEquals('custom-value', $headers['x-custom-header']);
    }

    public function testGetHeadersFromServerWithContentHeaders(): void
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => '123',
        ];

        $headers = ServerRequestCreator::getHeadersFromServer($server);

        $this->assertArrayHasKey('content-type', $headers);
        $this->assertEquals('application/json', $headers['content-type']);
        $this->assertArrayHasKey('content-length', $headers);
        $this->assertEquals('123', $headers['content-length']);
    }

    public function testGetHeadersFromServerWithRedirectPrefix(): void
    {
        $server = [
            'REDIRECT_HTTP_HOST' => 'redirect.example.com',
            'HTTP_HOST' => 'example.com',
        ];

        $headers = ServerRequestCreator::getHeadersFromServer($server);

        // Should not overwrite existing header
        $this->assertEquals('example.com', $headers['host']);
    }

    public function testGetHeadersFromServerIgnoresNonHttpVars(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME' => 'example.com',
            'DOCUMENT_ROOT' => '/var/www',
        ];

        $headers = ServerRequestCreator::getHeadersFromServer($server);

        $this->assertArrayNotHasKey('request-method', $headers);
        $this->assertArrayNotHasKey('server-name', $headers);
        $this->assertArrayNotHasKey('document-root', $headers);
    }

    public function testFromArraysCreatesUriWithScheme(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => 'https',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path',
        ];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals('https', $request->getUri()->getScheme());
        $this->assertEquals('example.com', $request->getUri()->getHost());
        $this->assertEquals('/path', $request->getUri()->getPath());
    }

    public function testFromArraysCreatesUriWithHttpsFromServerVar(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com',
        ];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals('https', $request->getUri()->getScheme());
    }

    public function testFromArraysCreatesUriWithPort(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com:8080',
        ];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals('example.com', $request->getUri()->getHost());
        $this->assertEquals(8080, $request->getUri()->getPort());
    }

    public function testFromArraysCreatesUriWithServerPort(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => '9000',
        ];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals(9000, $request->getUri()->getPort());
    }

    public function testFromArraysCreatesUriWithQueryString(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/path?foo=bar',
            'QUERY_STRING' => 'foo=bar&baz=qux',
        ];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals('/path', $request->getUri()->getPath());
        $this->assertEquals('foo=bar&baz=qux', $request->getUri()->getQuery());
    }

    public function testFromArraysWithXForwardedProto(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'REQUEST_SCHEME' => 'http',
        ];

        $request = $this->creator->fromArrays($server);

        // X-Forwarded-Proto should take precedence
        $this->assertEquals('https', $request->getUri()->getScheme());
    }

    public function testFromArraysDefaultsToHttpScheme(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
        ];

        $request = $this->creator->fromArrays($server);

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testFromGlobalsWithDefaultGetMethod(): void
    {
        // Save original values
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalCookie = $_COOKIE;
        $originalFiles = $_FILES;

        try {
            $_SERVER = [];
            $_GET = [];
            $_POST = [];
            $_COOKIE = [];
            $_FILES = [];

            $request = $this->creator->fromGlobals();

            $this->assertEquals('GET', $request->getMethod());
        } finally {
            // Restore original values
            $_SERVER = $originalServer;
            $_GET = $originalGet;
            $_POST = $originalPost;
            $_COOKIE = $originalCookie;
            $_FILES = $originalFiles;
        }
    }

    public function testFromGlobalsWithPostFormData(): void
    {
        // Save original values
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalCookie = $_COOKIE;
        $originalFiles = $_FILES;

        try {
            $_SERVER = ['REQUEST_METHOD' => 'POST'];
            $_GET = [];
            $_POST = ['username' => 'john'];
            $_COOKIE = [];
            $_FILES = [];

            // Mock getallheaders if it doesn't exist
            if (!function_exists('getallheaders')) {
                eval('function getallheaders() { return ["Content-Type" => "application/x-www-form-urlencoded"]; }');
            }

            $request = $this->creator->fromGlobals();

            $this->assertEquals('POST', $request->getMethod());
        } finally {
            // Restore original values
            $_SERVER = $originalServer;
            $_GET = $originalGet;
            $_POST = $originalPost;
            $_COOKIE = $originalCookie;
            $_FILES = $originalFiles;
        }
    }
}
