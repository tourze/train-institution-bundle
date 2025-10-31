<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Exception;

/**
 * 设施不存在异常
 */
class FacilityNotFoundException extends TrainInstitutionException
{
    public function __construct(string $facilityId)
    {
        parent::__construct(sprintf('设施不存在: %s', $facilityId));
    }
}
