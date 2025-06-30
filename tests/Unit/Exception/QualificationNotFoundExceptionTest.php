<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\QualificationNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class QualificationNotFoundExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $qualificationId = 'qual-123';
        $exception = new QualificationNotFoundException($qualificationId);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertStringContainsString($qualificationId, $exception->getMessage());
        $this->assertSame('资质不存在: qual-123', $exception->getMessage());
    }
}