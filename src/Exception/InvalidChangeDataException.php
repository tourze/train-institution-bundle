<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 无效的变更数据异常
 */
class InvalidChangeDataException extends TrainInstitutionException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}