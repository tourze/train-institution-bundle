<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidFacilityDataException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class InvalidFacilityDataExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $message = '设施类型不能为空；设施面积必须是大于0的数值';
        $exception = new InvalidFacilityDataException($message);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
    }
}