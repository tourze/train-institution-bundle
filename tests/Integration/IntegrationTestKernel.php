<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Tourze\TrainInstitutionBundle\TrainInstitutionBundle;

/**
 * 集成测试专用内核
 */
class IntegrationTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new TrainInstitutionBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'test' => true,
            'secret' => 'test-secret',
        ]);

        // 配置Doctrine
        $container->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'path' => ':memory:',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'TrainInstitutionBundle' => [
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/../../src/Entity',
                        'prefix' => 'Tourze\TrainInstitutionBundle\Entity',
                        'alias' => 'TrainInstitution',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);

        // 加载Bundle的服务配置
        $loader->load(__DIR__ . '/../../src/Resources/config/services.yaml');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // 测试路由配置
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/train_institution_bundle_test/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/train_institution_bundle_test/logs';
    }
} 