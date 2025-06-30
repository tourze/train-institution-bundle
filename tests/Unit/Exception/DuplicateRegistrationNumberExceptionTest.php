<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\DuplicateRegistrationNumberException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class DuplicateRegistrationNumberExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $registrationNumber = 'REG-2024-001';
        $exception = new DuplicateRegistrationNumberException($registrationNumber);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertStringContainsString($registrationNumber, $exception->getMessage());
        $this->assertSame('注册号已存在: REG-2024-001', $exception->getMessage());
    }
}