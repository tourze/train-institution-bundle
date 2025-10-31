<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\DuplicateInstitutionCodeException;

/**
 * @internal
 */
#[CoversClass(DuplicateInstitutionCodeException::class)]
final class DuplicateInstitutionCodeExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $institutionCode = 'INST-2024-001';
        $exception = new DuplicateInstitutionCodeException($institutionCode);

        self::assertStringContainsString($institutionCode, $exception->getMessage());
        self::assertSame('机构代码已存在: INST-2024-001', $exception->getMessage());
    }
}
