<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 证书编号重复异常
 */
class DuplicateCertificateNumberException extends TrainInstitutionException
{
    public function __construct(string $certificateNumber)
    {
        parent::__construct(sprintf('证书编号已存在: %s', $certificateNumber));
    }
}
