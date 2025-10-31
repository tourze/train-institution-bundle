<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\QualificationExpiredException;

/**
 * @internal
 */
#[CoversClass(QualificationExpiredException::class)]
final class QualificationExpiredExceptionTest extends AbstractExceptionTestCase
{
    public function testDefaultExceptionMessage(): void
    {
        $exception = new QualificationExpiredException();

        self::assertSame('资质已过期，无法恢复', $exception->getMessage());
    }

    public function testCustomExceptionMessage(): void
    {
        $customMessage = '资质已过期，请申请新的资质';
        $exception = new QualificationExpiredException($customMessage);

        self::assertSame($customMessage, $exception->getMessage());
    }
}
