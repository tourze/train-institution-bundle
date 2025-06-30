<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\ChangeRecordNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class ChangeRecordNotFoundExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $recordId = 'record-123';
        $exception = new ChangeRecordNotFoundException($recordId);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertStringContainsString($recordId, $exception->getMessage());
        $this->assertSame('变更记录不存在: record-123', $exception->getMessage());
    }
}