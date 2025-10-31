<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidQualificationDataException;

/**
 * @internal
 */
#[CoversClass(InvalidQualificationDataException::class)]
final class InvalidQualificationDataExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = '资质类型不能为空；证书编号不能为空';
        $exception = new InvalidQualificationDataException($message);

        self::assertSame($message, $exception->getMessage());
    }
}
