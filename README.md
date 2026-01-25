# Modufolio HTTP

PSR-7 HTTP message implementation with additional utilities for building HTTP applications.

## Installation

```bash
composer require modufolio/http
```

## Requirements

- PHP 8.2 or higher
- PSR-7 HTTP Message interfaces
- PSR-17 HTTP Factory interfaces

## Features

This package provides a complete PSR-7 implementation including:

- **Request/Response**: Full PSR-7 HTTP message implementation
- **ServerRequest**: Server-side request handling with superglobal parsing
- **Uri**: URI parsing and manipulation
- **Stream**: Stream implementation for request/response bodies
- **UploadedFile**: File upload handling with validation
- **Emitter**: Response emitters for sending HTTP responses
- **ServerRequestCreator**: Factory for creating ServerRequest from globals

## Usage

### Creating a Response

```php
use Modufolio\Psr7\Http\Response;

$response = new Response(200, ['Content-Type' => 'application/json'], '{"message":"Hello"}');
```

### Creating a ServerRequest from globals

```php
use Modufolio\Psr7\Http\ServerRequestCreator;

$creator = new ServerRequestCreator();
$request = $creator->fromGlobals();
```

### Working with Streams

```php
use Modufolio\Psr7\Http\Stream;

$stream = Stream::create('Hello World');
echo $stream->getContents(); // "Hello World"
```

### Handling Uploaded Files

```php
use Modufolio\Psr7\Http\UploadedFile;

$uploadedFile = new UploadedFile(
    '/tmp/phpYzdqkD',
    'document.pdf',
    'application/pdf',
    null,
    UPLOAD_ERR_OK
);

$uploadedFile->moveTo('/uploads/document.pdf');
```

## Testing

```bash
composer test
```

## License

MIT
