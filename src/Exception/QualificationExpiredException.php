<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 资质过期异常
 */
class QualificationExpiredException extends TrainInstitutionException
{
    public function __construct(string $message = '资质已过期，无法恢复')
    {
        parent::__construct($message);
    }
}
