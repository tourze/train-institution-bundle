<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\DuplicateCertificateNumberException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class DuplicateCertificateNumberExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $certificateNumber = 'CERT-2024-001';
        $exception = new DuplicateCertificateNumberException($certificateNumber);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertStringContainsString($certificateNumber, $exception->getMessage());
        $this->assertSame('证书编号已存在: CERT-2024-001', $exception->getMessage());
    }
}