<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 机构代码重复异常
 */
class DuplicateInstitutionCodeException extends TrainInstitutionException
{
    public function __construct(string $institutionCode)
    {
        parent::__construct(sprintf('机构代码已存在: %s', $institutionCode));
    }
}
