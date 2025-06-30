<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\DuplicateInstitutionCodeException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class DuplicateInstitutionCodeExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $institutionCode = 'INST-2024-001';
        $exception = new DuplicateInstitutionCodeException($institutionCode);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertStringContainsString($institutionCode, $exception->getMessage());
        $this->assertSame('机构代码已存在: INST-2024-001', $exception->getMessage());
    }
}