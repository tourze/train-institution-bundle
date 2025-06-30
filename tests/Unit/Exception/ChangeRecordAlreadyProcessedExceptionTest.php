<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\ChangeRecordAlreadyProcessedException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class ChangeRecordAlreadyProcessedExceptionTest extends TestCase
{
    public function testExceptionMessageForApproval(): void
    {
        $exception = new ChangeRecordAlreadyProcessedException('审批');

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertSame('该变更记录已处理，无法重复审批', $exception->getMessage());
    }

    public function testExceptionMessageForRejection(): void
    {
        $exception = new ChangeRecordAlreadyProcessedException('拒绝');

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertSame('该变更记录已处理，无法重复拒绝', $exception->getMessage());
    }
}