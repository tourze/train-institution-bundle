<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 资质不存在异常
 */
class QualificationNotFoundException extends TrainInstitutionException
{
    public function __construct(string $qualificationId)
    {
        parent::__construct(sprintf('资质不存在: %s', $qualificationId));
    }
}
