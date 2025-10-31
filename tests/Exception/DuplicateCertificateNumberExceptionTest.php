<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\DuplicateCertificateNumberException;

/**
 * @internal
 */
#[CoversClass(DuplicateCertificateNumberException::class)]
final class DuplicateCertificateNumberExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $certificateNumber = 'CERT-2024-001';
        $exception = new DuplicateCertificateNumberException($certificateNumber);

        self::assertStringContainsString($certificateNumber, $exception->getMessage());
        self::assertSame('证书编号已存在: CERT-2024-001', $exception->getMessage());
    }
}
