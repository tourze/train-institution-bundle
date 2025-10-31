<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 变更记录已处理异常
 */
class ChangeRecordAlreadyProcessedException extends TrainInstitutionException
{
    public function __construct(string $action)
    {
        parent::__construct(sprintf('该变更记录已处理，无法重复%s', $action));
    }
}
