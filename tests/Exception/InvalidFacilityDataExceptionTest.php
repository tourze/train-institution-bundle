<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidFacilityDataException;

/**
 * @internal
 */
#[CoversClass(InvalidFacilityDataException::class)]
final class InvalidFacilityDataExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = '设施类型不能为空；设施面积必须是大于0的数值';
        $exception = new InvalidFacilityDataException($message);

        self::assertSame($message, $exception->getMessage());
    }
}
