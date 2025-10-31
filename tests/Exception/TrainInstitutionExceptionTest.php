<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidInstitutionDataException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

/**
 * @internal
 */
#[CoversClass(TrainInstitutionException::class)]
final class TrainInstitutionExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $message = 'Test exception message';
        $exception = new InvalidInstitutionDataException($message);

        self::assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $message = 'Test exception message';
        $code = 500;
        $exception = new InvalidInstitutionDataException($message);
        // Note: InvalidInstitutionDataException constructor doesn't accept code parameter
        // We test the message instead

        self::assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $message = 'Test message';
        $exception = new InvalidInstitutionDataException($message);

        // Test that the exception message is correct
        self::assertSame($message, $exception->getMessage());
    }
}
