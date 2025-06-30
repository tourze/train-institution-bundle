<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\QualificationExpiredException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class QualificationExpiredExceptionTest extends TestCase
{
    public function testDefaultExceptionMessage(): void
    {
        $exception = new QualificationExpiredException();

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertSame('资质已过期，无法恢复', $exception->getMessage());
    }

    public function testCustomExceptionMessage(): void
    {
        $customMessage = '资质已过期，请申请新的资质';
        $exception = new QualificationExpiredException($customMessage);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertSame($customMessage, $exception->getMessage());
    }
}