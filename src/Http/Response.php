<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Http;

use const ENT_QUOTES;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\{ResponseInterface, StreamInterface};

use function sprintf;

class Response implements ResponseInterface
{
    use MessageTrait;

    /** @var array Map of standard HTTP status code/reason phrases */
    private const PHRASES = [
        100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing',
        200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-status', 208 => 'Already Reported',
        300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => 'Switch Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
        400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Unordered Collection', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 511 => 'Network Authentication Required',
    ];

    private const REDIRECT_CODES = [301, 302, 303, 307, 308];

    private string $reasonPhrase = '';

    private int $statusCode;

    /**
     * @param int $status Status code
     * @param array $headers Response headers
     * @param string|StreamInterface $body Response body
     * @param string $version Protocol version
     * @param string $reason Reason phrase (when empty a default will be used based on the status code)
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        StreamInterface|string $body = '',
        string $version = '1.1',
        string $reason = ''
    ) {
        if ('' !== $body) {
            $this->stream = Stream::create($body);
        }

        $this->statusCode = $status;
        $this->setHeaders($headers);
        $this->reasonPhrase = '' === $reason && isset(self::PHRASES[$this->statusCode])
            ? self::PHRASES[$status]
            : $reason;
        $this->protocol = $version;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function withStatus(int $code, $reasonPhrase = ''): self
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(sprintf(
                'Status code has to be an integer between 100 and 599. A status code of %d was given',
                $code
            ));
        }

        $new = clone $this;
        $new->statusCode = $code;
        if ('' === $reasonPhrase && isset(self::PHRASES[$new->statusCode])) {
            $reasonPhrase = self::PHRASES[$new->statusCode];
        }
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    public static function empty(): self
    {
        return new self(204);
    }

    /**
     * @throws JsonException
     */
    public static function json(
        string|array $body = '',
        int|null $code = null,
        bool|null $pretty = null,
        array $headers = []
    ): self {
        $status = $code ?? 200;

        $jsonOptions = JSON_THROW_ON_ERROR | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        if ($pretty ?? false) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        if (is_string($body)) {
            if (!json_validate($body)) {
                throw new JsonException('Invalid JSON payload');
            }
            $bodyContent = $body;
        } else {
            $bodyContent = json_encode($body, $jsonOptions);
        }

        return new self(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            Stream::create($bodyContent)
        );
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(401, [], Stream::create($message));
    }

    public static function unavailable(string $message = 'Service Unavailable'): self
    {
        return new self(503, [], Stream::create($message));
    }

    public static function tooManyRequests(string $message = 'Too Many Requests'): self
    {
        return new self(429, [], Stream::create($message));
    }

    public static function html(string $data, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html'], Stream::create($data));
    }

    public static function redirect(string $url, int $status = 302): self
    {
        if (!in_array($status, self::REDIRECT_CODES, true)) {
            throw new InvalidArgumentException(sprintf(
                'The redirect status code must be one of: %s',
                implode(', ', self::REDIRECT_CODES)
            ));
        }

        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        $body = <<<HTML
        <!DOCTYPE html>
        <html>
            <head>
                <meta charset="UTF-8" />
                <meta http-equiv="refresh" content="0;url='$safeUrl'" />
                <title>Redirecting to $safeUrl</title>
            </head>
            <body>
                Redirecting to <a href="$safeUrl">$safeUrl</a>.
            </body>
        </html>
        HTML;

        return new self($status, ['Location' => $url], $body);
    }
}