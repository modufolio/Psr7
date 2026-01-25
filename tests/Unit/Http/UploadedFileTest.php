<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Tests\Unit\Http;

use Modufolio\Psr7\Http\Factory\Psr17Factory;
use Modufolio\Psr7\Http\Stream;
use Modufolio\Psr7\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    protected array $cleanup;

    protected function setUp(): void
    {
        $this->cleanup = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $file) {
            if (\is_string($file) && \file_exists($file)) {
                \unlink($file);
            }
        }
    }

    public function testGetStreamReturnsOriginalStreamObject(): void
    {
        $stream = Stream::create('');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);

        $this->assertSame($stream, $upload->getStream());
    }

    public function testGetStreamReturnsWrappedPhpStream(): void
    {
        $stream = \fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();

        $this->assertSame($stream, $uploadStream);
    }

    public function testSuccessful(): void
    {
        $stream = Stream::create('Foo bar!');
        $upload = new UploadedFile($stream, $stream->getSize(), \UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->assertEquals($stream->getSize(), $upload->getSize());
        $this->assertEquals('filename.txt', $upload->getClientFilename());
        $this->assertEquals('text/plain', $upload->getClientMediaType());

        $this->cleanup[] = $to = \tempnam(\sys_get_temp_dir(), 'successful');
        $upload->moveTo($to);
        $this->assertFileExists($to);
        $this->assertEquals($stream->__toString(), \file_get_contents($to));
    }

    public static function invalidMovePaths(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.1],
            'empty' => [''],
            'array' => [['filename']],
            'object' => [(object)['filename']],
        ];
    }

    #[DataProvider('invalidMovePaths')]
    public function testMoveRaisesExceptionForInvalidPath($path): void
    {
        $stream = (new Psr17Factory())->createStream('Foo bar!');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);

        $this->cleanup[] = $path;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('path');
        $upload->moveTo($path);
    }

    public function testMoveCannotBeCalledMoreThanOnce(): void
    {
        $stream = (new Psr17Factory())->createStream('Foo bar!');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);

        $this->cleanup[] = $to = \tempnam(\sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        $this->assertTrue(\file_exists($to));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('moved');
        $upload->moveTo($to);
    }

    public function testCannotRetrieveStreamAfterMove(): void
    {
        $stream = (new Psr17Factory())->createStream('Foo bar!');
        $upload = new UploadedFile($stream, 0, \UPLOAD_ERR_OK);

        $this->cleanup[] = $to = \tempnam(\sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        $this->assertFileExists($to);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('moved');
        $upload->getStream();
    }

    public static function nonOkErrorStatus(): array
    {
        return [
            'UPLOAD_ERR_INI_SIZE' => [\UPLOAD_ERR_INI_SIZE],
            'UPLOAD_ERR_FORM_SIZE' => [\UPLOAD_ERR_FORM_SIZE],
            'UPLOAD_ERR_PARTIAL' => [\UPLOAD_ERR_PARTIAL],
            'UPLOAD_ERR_NO_FILE' => [\UPLOAD_ERR_NO_FILE],
            'UPLOAD_ERR_NO_TMP_DIR' => [\UPLOAD_ERR_NO_TMP_DIR],
            'UPLOAD_ERR_CANT_WRITE' => [\UPLOAD_ERR_CANT_WRITE],
            'UPLOAD_ERR_EXTENSION' => [\UPLOAD_ERR_EXTENSION],
        ];
    }

    #[DataProvider('nonOkErrorStatus')]
    public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent($status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->assertSame($status, $uploadedFile->getError());
    }

    #[DataProvider('nonOkErrorStatus')]
    public function testMoveToRaisesExceptionWhenErrorStatusPresent($status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('upload error');
        $uploadedFile->moveTo(__DIR__ . 'UploadedFileTest.php/' . \uniqid());
    }

    #[DataProvider('nonOkErrorStatus')]
    public function testGetStreamRaisesExceptionWhenErrorStatusPresent($status): void
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('upload error');
        $uploadedFile->getStream();
    }

    public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided(): void
    {
        $this->cleanup[] = $from = \tempnam(\sys_get_temp_dir(), 'copy_from');
        $this->cleanup[] = $to = \tempnam(\sys_get_temp_dir(), 'copy_to');

        \copy(__FILE__, $from);

        $uploadedFile = new UploadedFile($from, 100, \UPLOAD_ERR_OK, \basename($from), 'text/plain');
        $uploadedFile->moveTo($to);

        $this->assertFileEquals(__FILE__, $to);
    }
}
