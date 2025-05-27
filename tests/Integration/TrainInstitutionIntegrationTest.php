<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\TrainInstitutionBundle\Service\ChangeRecordService;
use Tourze\TrainInstitutionBundle\Service\FacilityService;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;
use Tourze\TrainInstitutionBundle\Service\QualificationService;

/**
 * 培训机构Bundle集成测试
 */
class TrainInstitutionIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    /**
     * 测试服务是否正确注册
     */
    public function testServicesAreRegistered(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // 测试核心服务是否注册
        $this->assertTrue($container->has(InstitutionService::class));
        $this->assertTrue($container->has(QualificationService::class));
        $this->assertTrue($container->has(FacilityService::class));
        $this->assertTrue($container->has(ChangeRecordService::class));

        // 测试服务实例化
        $institutionService = $container->get(InstitutionService::class);
        $this->assertInstanceOf(InstitutionService::class, $institutionService);

        $qualificationService = $container->get(QualificationService::class);
        $this->assertInstanceOf(QualificationService::class, $qualificationService);

        $facilityService = $container->get(FacilityService::class);
        $this->assertInstanceOf(FacilityService::class, $facilityService);

        $changeRecordService = $container->get(ChangeRecordService::class);
        $this->assertInstanceOf(ChangeRecordService::class, $changeRecordService);
    }

    /**
     * 测试Command是否正确注册
     */
    public function testCommandsAreRegistered(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // 测试Command服务是否注册
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Command\QualificationExpiryCheckCommand'));
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Command\FacilityInspectionScheduleCommand'));
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Command\InstitutionStatusCheckCommand'));
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Command\InstitutionReportCommand'));
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Command\InstitutionDataSyncCommand'));
    }

    /**
     * 测试Repository是否正确注册
     */
    public function testRepositoriesAreRegistered(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // 测试Repository服务是否注册
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Repository\InstitutionRepository'));
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository'));
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository'));
        $this->assertTrue($container->has('Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository'));
    }
} 