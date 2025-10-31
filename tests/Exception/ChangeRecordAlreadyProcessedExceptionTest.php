<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\ChangeRecordAlreadyProcessedException;

/**
 * @internal
 */
#[CoversClass(ChangeRecordAlreadyProcessedException::class)]
final class ChangeRecordAlreadyProcessedExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessageForApproval(): void
    {
        $exception = new ChangeRecordAlreadyProcessedException('审批');

        self::assertSame('该变更记录已处理，无法重复审批', $exception->getMessage());
    }

    public function testExceptionMessageForRejection(): void
    {
        $exception = new ChangeRecordAlreadyProcessedException('拒绝');

        self::assertSame('该变更记录已处理，无法重复拒绝', $exception->getMessage());
    }
}
