<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 注册号重复异常
 */
class DuplicateRegistrationNumberException extends TrainInstitutionException
{
    public function __construct(string $registrationNumber)
    {
        parent::__construct(sprintf('注册号已存在: %s', $registrationNumber));
    }
}