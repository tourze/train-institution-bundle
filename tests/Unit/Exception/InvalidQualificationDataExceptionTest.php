<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidQualificationDataException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class InvalidQualificationDataExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $message = '资质类型不能为空；证书编号不能为空';
        $exception = new InvalidQualificationDataException($message);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
    }
}