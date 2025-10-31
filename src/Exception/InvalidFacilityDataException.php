<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 无效的设施数据异常
 */
class InvalidFacilityDataException extends TrainInstitutionException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
