<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\FacilityNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class FacilityNotFoundExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $facilityId = 'facility-123';
        $exception = new FacilityNotFoundException($facilityId);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertStringContainsString($facilityId, $exception->getMessage());
        $this->assertSame('设施不存在: facility-123', $exception->getMessage());
    }
}