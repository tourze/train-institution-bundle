<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidChangeDataException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class InvalidChangeDataExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $message = '变更类型不能为空；变更详情必须是数组格式';
        $exception = new InvalidChangeDataException($message);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
    }
}