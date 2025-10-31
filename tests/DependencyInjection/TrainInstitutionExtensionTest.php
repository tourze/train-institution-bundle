<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\TrainInstitutionBundle\DependencyInjection\TrainInstitutionExtension;

/**
 * @internal
 */
#[CoversClass(TrainInstitutionExtension::class)]
final class TrainInstitutionExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private TrainInstitutionExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new TrainInstitutionExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoadServices(): void
    {
        $this->extension->load([], $this->container);

        // 测试服务类是否被正确加载
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Service\InstitutionService'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Service\ChangeRecordService'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Service\FacilityService'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Service\QualificationService'));
    }

    public function testLoadRepositories(): void
    {
        $this->extension->load([], $this->container);

        // 测试仓库类是否被正确加载
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Repository\InstitutionRepository'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Repository\InstitutionFacilityRepository'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository'));
    }

    public function testLoadCommands(): void
    {
        $this->extension->load([], $this->container);

        // 测试命令类是否被正确加载
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\InstitutionDataSyncCommand'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\InstitutionStatusCheckCommand'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\FacilityInspectionScheduleCommand'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\QualificationExpiryCheckCommand'));
        self::assertTrue($this->container->hasDefinition('Tourze\TrainInstitutionBundle\Command\InstitutionReportCommand'));
    }

    public function testServicesAutowiring(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务的自动装配设置
        $serviceDefinition = $this->container->getDefinition('Tourze\TrainInstitutionBundle\Service\InstitutionService');
        self::assertTrue($serviceDefinition->isAutowired());
        self::assertTrue($serviceDefinition->isAutoconfigured());
    }
}
