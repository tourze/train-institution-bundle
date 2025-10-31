<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\FacilityNotFoundException;

/**
 * @internal
 */
#[CoversClass(FacilityNotFoundException::class)]
final class FacilityNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $facilityId = 'facility-123';
        $exception = new FacilityNotFoundException($facilityId);

        self::assertStringContainsString($facilityId, $exception->getMessage());
        self::assertSame('设施不存在: facility-123', $exception->getMessage());
    }
}
