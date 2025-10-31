<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\ChangeRecordNotFoundException;

/**
 * @internal
 */
#[CoversClass(ChangeRecordNotFoundException::class)]
final class ChangeRecordNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $recordId = 'record-123';
        $exception = new ChangeRecordNotFoundException($recordId);

        self::assertStringContainsString($recordId, $exception->getMessage());
        self::assertSame('变更记录不存在: record-123', $exception->getMessage());
    }
}
