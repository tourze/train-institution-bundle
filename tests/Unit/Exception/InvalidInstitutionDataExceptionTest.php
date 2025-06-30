<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Exception\InvalidInstitutionDataException;
use Tourze\TrainInstitutionBundle\Exception\TrainInstitutionException;

class InvalidInstitutionDataExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $message = '机构名称不能为空；法人不能为空';
        $exception = new InvalidInstitutionDataException($message);

        $this->assertInstanceOf(TrainInstitutionException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
    }
}