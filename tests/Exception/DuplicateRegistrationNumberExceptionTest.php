<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\DuplicateRegistrationNumberException;

/**
 * @internal
 */
#[CoversClass(DuplicateRegistrationNumberException::class)]
final class DuplicateRegistrationNumberExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $registrationNumber = 'REG-2024-001';
        $exception = new DuplicateRegistrationNumberException($registrationNumber);

        self::assertStringContainsString($registrationNumber, $exception->getMessage());
        self::assertSame('注册号已存在: REG-2024-001', $exception->getMessage());
    }
}
