<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;

/**
 * @internal
 */
#[CoversClass(InstitutionNotFoundException::class)]
final class InstitutionNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $institutionId = 'inst-123';
        $exception = new InstitutionNotFoundException($institutionId);

        self::assertStringContainsString($institutionId, $exception->getMessage());
        self::assertSame('机构不存在: inst-123', $exception->getMessage());
    }
}
