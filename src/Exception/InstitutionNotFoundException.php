<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 机构不存在异常
 */
class InstitutionNotFoundException extends TrainInstitutionException
{
    public function __construct(string $institutionId)
    {
        parent::__construct(sprintf('机构不存在: %s', $institutionId));
    }
}
