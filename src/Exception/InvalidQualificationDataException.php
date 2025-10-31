<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 无效的资质数据异常
 */
class InvalidQualificationDataException extends TrainInstitutionException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
