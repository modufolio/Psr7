# Modufolio HTTP

[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](https://opensource.org/licenses/MIT) [![codecov](https://img.shields.io/codecov/c/github/modufolio/psr7?token=7IT3RSOV2K&style=flat-square)](https://codecov.io/gh/modufolio/psr7)

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
- **ServerRequest**: Server-side request handling with superglobal parsing and automatic body parsing
- **Uri**: URI parsing and manipulation
- **Stream**: Stream implementation for request/response bodies
- **UploadedFile**: File upload handling with validation
- **Emitter**: Response emitters for sending HTTP responses
- **ServerRequestCreator**: Factory for creating ServerRequest from globals
- **ServerRequestCreatorFactory**: Static factory for creating ServerRequestCreator instances
- **Static Response Helpers**: Convenient methods for creating common HTTP responses (JSON, HTML, redirects, etc.)
- **Body Parsers**: Built-in parsers for JSON, XML, and form data with extensible parser registration

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

### Using ServerRequestCreatorFactory

```php
use Modufolio\Psr7\Http\Factory\ServerRequestCreatorFactory;

$creator = ServerRequestCreatorFactory::create();
$request = $creator->fromGlobals();
```

### Static Response Helpers

```php
use Modufolio\Psr7\Http\Response;

// Create JSON responses
$response = Response::json(['message' => 'Hello World'], 200);

// Create HTML responses
$response = Response::html('<h1>Hello World</h1>');

// Create redirects
$response = Response::redirect('https://example.com', 302);

// Create error responses
$response = Response::unauthorized('Invalid credentials');
$response = Response::tooManyRequests('Rate limit exceeded');
```

### Automatic Body Parsing in ServerRequest

```php
use Modufolio\Psr7\Http\ServerRequestCreator;

$creator = new ServerRequestCreator();
$request = $creator->fromGlobals();

// Automatically parses JSON, XML, and form data
$parsedBody = $request->getParsedBody();
```

### Emitting Responses

```php
use Modufolio\Psr7\Http\Response;
use Modufolio\Psr7\Http\Emitter;

$response = Response::json(['status' => 'success']);
$emitter = new Emitter();
$emitter->emit($response); // Sends headers and body to client
```



## License

MIT
