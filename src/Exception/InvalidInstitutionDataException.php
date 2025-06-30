<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 无效的机构数据异常
 */
class InvalidInstitutionDataException extends TrainInstitutionException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}