<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Http;


use Modufolio\Psr7\Http\Exception\PayloadTooLargeException;
use Psr\Http\Message\{ServerRequestInterface, StreamInterface, UploadedFileInterface, UriInterface};

/**
 * @author Michael Dowling and contributors to guzzlehttp/psr7
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class ServerRequest implements ServerRequestInterface
{
    use MessageTrait;
    use RequestTrait;

    private array $attributes = [];
    private array $cookieParams = [];

    /** @var array|object|null */
    private $parsedBody;
    private array $queryParams = [];
    private array $serverParams;

    /** @var UploadedFileInterface[] */
    private array $uploadedFiles = [];

    protected array $bodyParsers;

    /**
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI
     * @param array $headers Request headers
     * @param string|resource|StreamInterface|null $body Request body
     * @param string $version Protocol version
     * @param array $serverParams Typically the $_SERVER superglobal
     */
    public function __construct(string $method, $uri, array $headers = [], $body = null, string $version = '1.1', array $serverParams = [])
    {
        $this->serverParams = $serverParams;

        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;
        \parse_str($uri->getQuery(), $this->queryParams);

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        // If we got no body, defer initialization of the stream until ServerRequest::getBody()
        if ('' !== $body && null !== $body) {
            $this->stream = Stream::create($body);
        }

        $this->registerMediaTypeParser('application/json', function ($input) {

            if (strlen($input) > 500) {
                throw new PayloadTooLargeException('JSON payload exceeds 500 characters.');
            }

            if(trim($input) === '') {
                return null;
            }

            $result = json_decode($input, true, 50, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY);

            if (!is_array($result)) {
                return null;
            }

            return $result;
        });

        $xmlParserCallable = function ($input) {
            $backup_errors = libxml_use_internal_errors(true);

            $result = simplexml_load_string($input);

            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);

            return $result ?: null;
        };

        $this->registerMediaTypeParser('application/xml', $xmlParserCallable);
        $this->registerMediaTypeParser('text/xml', $xmlParserCallable);

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });

    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @return static
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @return static
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }


    /**
     * Get request content type.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null The request content type, if known
     */
    public function getContentType(): ?string
    {
        $result = $this->getHeader('Content-Type');
        return $result ? $result[0] : null;
    }

    /**
     * Get serverRequest media type, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null The serverRequest media type, minus content-type params
     */
    public function getMediaType(): ?string
    {
        $contentType = $this->getContentType();

        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            if ($contentTypeParts === false) {
                return null;
            }
            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * @return array|object|null
     */
    public function getParsedBody()
    {
        if (!empty($this->parsedBody)) {
            return $this->parsedBody;
        }

        $mediaType = $this->getMediaType();
        if ($mediaType === null) {
            return $this->parsedBody;
        }

        // Check if this specific media type has a parser registered
        if (!isset($this->bodyParsers[$mediaType])) {
            // Look for a media type with a structured syntax suffix (e.g., application/vnd.api+json)
            $parts = explode('+', $mediaType);
            if (count($parts) >= 2) {
                $mediaType = 'application/' . $parts[count($parts) - 1];
            }
        }

        if (isset($this->bodyParsers[$mediaType])) {
            $body = (string)$this->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new \RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }

            $this->parsedBody = $parsed; // Cache the parsed body
            return $parsed;
        }

        return null;
    }

    /**
     * Register media type parser.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $mediaType A HTTP media type (excluding content-type params).
     * @param callable $callable A callable that returns parsed contents for media type.
     * @return static
     */
    public function registerMediaTypeParser(string $mediaType, callable $callable): ServerRequestInterface
    {
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }

        $this->bodyParsers[$mediaType] = $callable;

        return $this;
    }

    /**
     * @return static
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        if (!\is_array($data) && !\is_object($data) && null !== $data) {
            throw new \InvalidArgumentException('First parameter to withParsedBody MUST be object, array or null');
        }

        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return mixed
     */
    public function getAttribute(string $name, $default = null): mixed
    {


        if (false === \array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * @return static
     */
    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * @return static
     */
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        if (false === \array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }
}
