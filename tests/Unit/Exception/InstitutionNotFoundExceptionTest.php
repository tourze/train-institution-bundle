<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class InstitutionNotFoundExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $institutionId = 'inst-123';
        $exception = new InstitutionNotFoundException($institutionId);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertStringContainsString($institutionId, $exception->getMessage());
        $this->assertSame('机构不存在: inst-123', $exception->getMessage());
    }
}