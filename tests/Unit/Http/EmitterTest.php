<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Tests\Unit\Http;

use Modufolio\Psr7\Http\Emitter;
use Modufolio\Psr7\Http\Response;
use Modufolio\Psr7\Http\Stream;
use PHPUnit\Framework\TestCase;

class EmitterTest extends TestCase
{
    private Emitter $emitter;

    protected function setUp(): void
    {
        $this->emitter = new Emitter();
    }

    public function testEmitSimpleResponse(): void
    {
        $response = new Response(200, [], 'Hello World');

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('Hello World', $output);
    }

    public function testEmitResponseWithHeaders(): void
    {
        $response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                'X-Custom-Header' => 'custom-value'
            ],
            '{"message":"test"}'
        );

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('{"message":"test"}', $output);
    }

    public function testEmitResponseWithMultipleHeaderValues(): void
    {
        $response = new Response(
            200,
            [
                'Set-Cookie' => ['cookie1=value1', 'cookie2=value2']
            ],
            'content'
        );

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('content', $output);
    }

    public function testEmitResponseWithCustomStatusCode(): void
    {
        $response = new Response(404, [], 'Not Found');

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('Not Found', $output);
    }

    public function testEmitResponseWithProtocolVersion(): void
    {
        $response = (new Response(200, [], 'test'))
            ->withProtocolVersion('1.0');

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('test', $output);
    }

    public function testEmitResponseWithSeekableStream(): void
    {
        $stream = Stream::create('Test content from stream');
        // Read some content to move the pointer
        $stream->read(4);

        $response = (new Response())->withBody($stream);

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        // Should rewind and output full content
        $this->assertEquals('Test content from stream', $output);
    }

    public function testEmitResponseWithLargeContent(): void
    {
        // Create content larger than the read buffer (8KB chunks)
        $largeContent = str_repeat('x', 10000);
        $response = new Response(200, [], $largeContent);

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals($largeContent, $output);
        $this->assertEquals(10000, strlen($output));
    }

    public function testEmitEmptyResponse(): void
    {
        $response = new Response(204);

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    public function testEmitResponseWithReason(): void
    {
        $response = new Response(200);

        ob_start();
        $this->emitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }
}
