<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\TrainInstitutionBundle\TrainInstitutionBundle;

/**
 * @internal
 */
#[CoversClass(TrainInstitutionBundle::class)]
#[RunTestsInSeparateProcesses]
final class TrainInstitutionBundleTest extends AbstractBundleTestCase
{
}
