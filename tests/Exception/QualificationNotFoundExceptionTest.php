<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\QualificationNotFoundException;

/**
 * @internal
 */
#[CoversClass(QualificationNotFoundException::class)]
final class QualificationNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $qualificationId = 'qual-123';
        $exception = new QualificationNotFoundException($qualificationId);

        self::assertStringContainsString($qualificationId, $exception->getMessage());
        self::assertSame('资质不存在: qual-123', $exception->getMessage());
    }
}
