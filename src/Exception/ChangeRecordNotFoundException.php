<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 变更记录不存在异常
 */
class ChangeRecordNotFoundException extends TrainInstitutionException
{
    public function __construct(string $recordId)
    {
        parent::__construct(sprintf('变更记录不存在: %s', $recordId));
    }
}
