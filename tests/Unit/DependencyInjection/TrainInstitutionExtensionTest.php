<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\TrainInstitutionBundle\DependencyInjection\TrainInstitutionExtension;

class TrainInstitutionExtensionTest extends TestCase
{
    private TrainInstitutionExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new TrainInstitutionExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadServices(): void
    {
        $this->extension->load([], $this->container);

        // 测试服务类是否被正确加载
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Service\InstitutionService'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Service\ChangeRecordService'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Service\FacilityService'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Service\QualificationService'));
    }

    public function testLoadRepositories(): void
    {
        $this->extension->load([], $this->container);

        // 测试仓库类是否被正确加载
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Repository\InstitutionRepository'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository'));
    }

    public function testLoadCommands(): void
    {
        $this->extension->load([], $this->container);

        // 测试命令类是否被正确加载
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\InstitutionDataSyncCommand'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\InstitutionStatusCheckCommand'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\FacilityInspectionScheduleCommand'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\QualificationExpiryCheckCommand'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\InstitutionReportCommand'));
    }

    public function testServicesArePublic(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务的公开性设置
        $serviceDefinition = $this->container->getDefinition('Tourze\TrainInstitutionBundle\Service\InstitutionService');
        $this->assertFalse($serviceDefinition->isPublic());
    }

    public function testServicesAutowiring(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务的自动装配设置
        $serviceDefinition = $this->container->getDefinition('Tourze\TrainInstitutionBundle\Service\InstitutionService');
        $this->assertTrue($serviceDefinition->isAutowired());
        $this->assertTrue($serviceDefinition->isAutoconfigured());
    }
}