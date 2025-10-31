<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidChangeDataException;

/**
 * @internal
 */
#[CoversClass(InvalidChangeDataException::class)]
final class InvalidChangeDataExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = '变更类型不能为空；变更详情必须是数组格式';
        $exception = new InvalidChangeDataException($message);

        self::assertSame($message, $exception->getMessage());
    }
}
