<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidInstitutionDataException;

/**
 * @internal
 */
#[CoversClass(InvalidInstitutionDataException::class)]
final class InvalidInstitutionDataExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = '机构名称不能为空；法人不能为空';
        $exception = new InvalidInstitutionDataException($message);

        self::assertSame($message, $exception->getMessage());
    }
}
