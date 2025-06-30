<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class TrainInstitutionExceptionTest extends TestCase
{
    public function testExceptionCreation(): void
    {
        $message = 'Test exception message';
        $exception = new TrainInstitutionException($message);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $message = 'Test exception message';
        $code = 500;
        $exception = new TrainInstitutionException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new TrainInstitutionException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}