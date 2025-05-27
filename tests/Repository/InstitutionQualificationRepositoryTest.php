<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;
use Tourze\TrainInstitutionBundle\Repository\InstitutionQualificationRepository;
use Tourze\TrainInstitutionBundle\Tests\Integration\IntegrationTestKernel;

/**
 * InstitutionQualificationRepository 单元测试
 */
class InstitutionQualificationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private InstitutionQualificationRepository $repository;
    private Institution $testInstitution;

    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(InstitutionQualification::class);

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . InstitutionQualification::class)->execute();
        $this->entityManager->createQuery('DELETE FROM ' . Institution::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();

        // 创建测试机构
        $this->testInstitution = Institution::create(
            '测试培训机构',
            'TEST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区测试路123号',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456789'
        );
        $this->entityManager->persist($this->testInstitution);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试资质
     */
    private function createTestQualification(
        string $type = '安全培训资质',
        string $name = '安全生产培训机构资质证书',
        string $certNumber = 'CERT001',
        ?\DateTimeInterface $validFrom = null,
        ?\DateTimeInterface $validTo = null,
        string $status = '有效',
        array $scope = ['特种作业培训']
    ): InstitutionQualification {
        $validFrom = $validFrom ?? new \DateTimeImmutable('-1 month');
        $validTo = $validTo ?? new \DateTimeImmutable('+1 year');

        $qualification = InstitutionQualification::create(
            $this->testInstitution,
            $type,
            $name,
            $certNumber,
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            $validFrom,
            $validTo,
            $scope,
            $status
        );

        $this->entityManager->persist($qualification);
        $this->entityManager->flush();

        return $qualification;
    }

    /**
     * 测试根据机构查找所有资质
     */
    public function test_findByInstitution_returnsAllQualifications(): void
    {
        $qualification1 = $this->createTestQualification('安全培训资质', '资质1', 'CERT001');
        sleep(1); // 确保时间差异
        $qualification2 = $this->createTestQualification('办学许可证', '资质2', 'CERT002');

        $results = $this->repository->findByInstitution($this->testInstitution);

        $this->assertCount(2, $results);
        // 验证包含所有资质，不依赖特定顺序
        $certNumbers = array_map(fn($q) => $q->getCertificateNumber(), $results);
        $this->assertContains('CERT001', $certNumbers);
        $this->assertContains('CERT002', $certNumbers);
    }

    /**
     * 测试根据机构查找有效资质
     */
    public function test_findValidByInstitution_returnsOnlyValidQualifications(): void
    {
        // 有效资质
        $validQualification = $this->createTestQualification(
            '安全培训资质',
            '有效资质',
            'CERT001',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            '有效'
        );

        // 已过期资质
        $expiredQualification = $this->createTestQualification(
            '安全培训资质',
            '过期资质',
            'CERT002',
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-1 year'),
            '有效'
        );

        // 暂停资质
        $suspendedQualification = $this->createTestQualification(
            '安全培训资质',
            '暂停资质',
            'CERT003',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            '暂停'
        );

        // 尚未生效资质
        $futureQualification = $this->createTestQualification(
            '安全培训资质',
            '未来资质',
            'CERT004',
            new \DateTimeImmutable('+1 month'),
            new \DateTimeImmutable('+2 years'),
            '有效'
        );

        $results = $this->repository->findValidByInstitution($this->testInstitution);

        $this->assertCount(1, $results);
        $this->assertEquals('CERT001', $results[0]->getCertificateNumber());
    }

    /**
     * 测试根据证书编号查找资质
     */
    public function test_findByCertificateNumber_returnsCorrectQualification(): void
    {
        $qualification = $this->createTestQualification('安全培训资质', '测试资质', 'CERT001');
        $this->createTestQualification('办学许可证', '其他资质', 'CERT002');

        $result = $this->repository->findByCertificateNumber('CERT001');

        $this->assertNotNull($result);
        $this->assertEquals('CERT001', $result->getCertificateNumber());
        $this->assertEquals('测试资质', $result->getQualificationName());
    }

    /**
     * 测试根据不存在的证书编号查找
     */
    public function test_findByCertificateNumber_withNonExistentNumber_returnsNull(): void
    {
        $this->createTestQualification('安全培训资质', '测试资质', 'CERT001');

        $result = $this->repository->findByCertificateNumber('NONEXISTENT');

        $this->assertNull($result);
    }

    /**
     * 测试根据资质类型查找资质
     */
    public function test_findByQualificationType_returnsCorrectQualifications(): void
    {
        $this->createTestQualification('安全培训资质', '资质1', 'CERT001');
        $this->createTestQualification('安全培训资质', '资质2', 'CERT002');
        $this->createTestQualification('办学许可证', '资质3', 'CERT003');

        $results = $this->repository->findByQualificationType('安全培训资质');

        $this->assertCount(2, $results);
        foreach ($results as $qualification) {
            $this->assertEquals('安全培训资质', $qualification->getQualificationType());
        }
    }

    /**
     * 测试查找即将到期的资质 - 默认30天
     */
    public function test_findExpiringSoon_withDefaultDays_returnsExpiringQualifications(): void
    {
        // 15天后到期
        $expiringSoon = $this->createTestQualification(
            '安全培训资质',
            '即将到期',
            'CERT001',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+15 days')
        );

        // 45天后到期
        $expiringLater = $this->createTestQualification(
            '安全培训资质',
            '较晚到期',
            'CERT002',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+45 days')
        );

        // 已过期
        $expired = $this->createTestQualification(
            '安全培训资质',
            '已过期',
            'CERT003',
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-1 year')
        );

        $results = $this->repository->findExpiringSoon();

        $this->assertCount(1, $results);
        $this->assertEquals('CERT001', $results[0]->getCertificateNumber());
    }

    /**
     * 测试查找即将到期的资质 - 自定义天数
     */
    public function test_findExpiringSoon_withCustomDays_returnsExpiringQualifications(): void
    {
        // 15天后到期
        $expiringSoon = $this->createTestQualification(
            '安全培训资质',
            '即将到期',
            'CERT001',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+15 days')
        );

        // 45天后到期
        $expiringLater = $this->createTestQualification(
            '安全培训资质',
            '较晚到期',
            'CERT002',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+45 days')
        );

        $results = $this->repository->findExpiringSoon(60);

        $this->assertCount(2, $results);
        // 验证按到期时间升序排列
        $this->assertEquals('CERT001', $results[0]->getCertificateNumber());
        $this->assertEquals('CERT002', $results[1]->getCertificateNumber());
    }

    /**
     * 测试查找已过期的资质
     */
    public function test_findExpired_returnsExpiredQualifications(): void
    {
        // 有效资质
        $valid = $this->createTestQualification(
            '安全培训资质',
            '有效资质',
            'CERT001',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year')
        );

        // 已过期资质1
        $expired1 = $this->createTestQualification(
            '安全培训资质',
            '过期资质1',
            'CERT002',
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-1 year')
        );

        // 已过期资质2
        $expired2 = $this->createTestQualification(
            '安全培训资质',
            '过期资质2',
            'CERT003',
            new \DateTimeImmutable('-3 years'),
            new \DateTimeImmutable('-2 years')
        );

        $results = $this->repository->findExpired();

        $this->assertCount(2, $results);
        // 验证按到期时间倒序排列
        $this->assertEquals('CERT002', $results[0]->getCertificateNumber());
        $this->assertEquals('CERT003', $results[1]->getCertificateNumber());
    }

    /**
     * 测试根据发证机关查找资质
     */
    public function test_findByIssuingAuthority_returnsCorrectQualifications(): void
    {
        // 创建不同发证机关的资质
        $qualification1 = InstitutionQualification::create(
            $this->testInstitution,
            '安全培训资质',
            '资质1',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year')
        );

        $qualification2 = InstitutionQualification::create(
            $this->testInstitution,
            '办学许可证',
            '资质2',
            'CERT002',
            '教育部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year')
        );

        $qualification3 = InstitutionQualification::create(
            $this->testInstitution,
            '安全培训资质',
            '资质3',
            'CERT003',
            '应急管理部',
            new \DateTimeImmutable('-1 year'),
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year')
        );

        $this->entityManager->persist($qualification1);
        $this->entityManager->persist($qualification2);
        $this->entityManager->persist($qualification3);
        $this->entityManager->flush();

        $results = $this->repository->findByIssuingAuthority('应急管理部');

        $this->assertCount(2, $results);
        foreach ($results as $qualification) {
            $this->assertEquals('应急管理部', $qualification->getIssuingAuthority());
        }
    }

    /**
     * 测试根据机构和资质类型查找资质
     */
    public function test_findByInstitutionAndType_returnsCorrectQualifications(): void
    {
        $this->createTestQualification('安全培训资质', '资质1', 'CERT001');
        $this->createTestQualification('安全培训资质', '资质2', 'CERT002');
        $this->createTestQualification('办学许可证', '资质3', 'CERT003');

        $results = $this->repository->findByInstitutionAndType($this->testInstitution, '安全培训资质');

        $this->assertCount(2, $results);
        foreach ($results as $qualification) {
            $this->assertEquals('安全培训资质', $qualification->getQualificationType());
            $this->assertSame($this->testInstitution, $qualification->getInstitution());
        }
    }

    /**
     * 测试检查机构是否有指定类型的有效资质 - 有效
     */
    public function test_hasValidQualification_withValidQualification_returnsTrue(): void
    {
        $this->createTestQualification(
            '安全培训资质',
            '有效资质',
            'CERT001',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            '有效'
        );

        $result = $this->repository->hasValidQualification($this->testInstitution, '安全培训资质');

        $this->assertTrue($result);
    }

    /**
     * 测试检查机构是否有指定类型的有效资质 - 无效
     */
    public function test_hasValidQualification_withInvalidQualification_returnsFalse(): void
    {
        // 已过期资质
        $this->createTestQualification(
            '安全培训资质',
            '过期资质',
            'CERT001',
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-1 year'),
            '有效'
        );

        $result = $this->repository->hasValidQualification($this->testInstitution, '安全培训资质');

        $this->assertFalse($result);
    }

    /**
     * 测试检查机构是否有指定类型的有效资质 - 不存在
     */
    public function test_hasValidQualification_withNonExistentType_returnsFalse(): void
    {
        $this->createTestQualification('安全培训资质', '资质', 'CERT001');

        $result = $this->repository->hasValidQualification($this->testInstitution, '不存在的资质类型');

        $this->assertFalse($result);
    }

    /**
     * 测试获取资质统计信息
     */
    public function test_getStatistics_returnsCorrectStatistics(): void
    {
        // 有效资质
        $this->createTestQualification(
            '安全培训资质',
            '有效资质1',
            'CERT001',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            '有效'
        );

        // 即将到期资质
        $this->createTestQualification(
            '办学许可证',
            '即将到期',
            'CERT002',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+15 days'),
            '有效'
        );

        // 暂停资质
        $this->createTestQualification(
            '安全培训资质',
            '暂停资质',
            'CERT003',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+1 year'),
            '暂停'
        );

        // 已过期资质
        $this->createTestQualification(
            '安全培训资质',
            '过期资质',
            'CERT004',
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-1 year'),
            '有效'
        );

        $stats = $this->repository->getStatistics();

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['valid']); // 有效且在有效期内的资质
        $this->assertEquals(1, $stats['expiring_soon']); // 30天内到期的资质

        // 检查状态统计
        $statusCounts = [];
        foreach ($stats['by_status'] as $stat) {
            $statusCounts[$stat['qualificationStatus']] = $stat['count'];
        }
        $this->assertEquals(3, $statusCounts['有效']);
        $this->assertEquals(1, $statusCounts['暂停']);

        // 检查类型统计
        $typeCounts = [];
        foreach ($stats['by_type'] as $stat) {
            $typeCounts[$stat['qualificationType']] = $stat['count'];
        }
        $this->assertEquals(3, $typeCounts['安全培训资质']);
        $this->assertEquals(1, $typeCounts['办学许可证']);
    }

    /**
     * 测试检查证书编号是否已存在
     */
    public function test_isCertificateNumberExists_withExistingNumber_returnsTrue(): void
    {
        $this->createTestQualification('安全培训资质', '测试资质', 'CERT001');

        $result = $this->repository->isCertificateNumberExists('CERT001');

        $this->assertTrue($result);
    }

    /**
     * 测试检查证书编号是否已存在 - 不存在的编号
     */
    public function test_isCertificateNumberExists_withNonExistentNumber_returnsFalse(): void
    {
        $this->createTestQualification('安全培训资质', '测试资质', 'CERT001');

        $result = $this->repository->isCertificateNumberExists('NONEXISTENT');

        $this->assertFalse($result);
    }

    /**
     * 测试检查证书编号是否已存在 - 排除指定ID
     */
    public function test_isCertificateNumberExists_withExcludeId_returnsFalse(): void
    {
        $qualification = $this->createTestQualification('安全培训资质', '测试资质', 'CERT001');

        $result = $this->repository->isCertificateNumberExists('CERT001', $qualification->getId());

        $this->assertFalse($result);
    }

    /**
     * 测试根据有效期日期范围查找资质
     */
    public function test_findByValidDateRange_returnsQualificationsInRange(): void
    {
        // 2023年到期
        $qualification1 = InstitutionQualification::create(
            $this->testInstitution,
            '安全培训资质',
            '资质1',
            'CERT001',
            '应急管理部',
            new \DateTimeImmutable('2022-01-01'),
            new \DateTimeImmutable('2022-01-01'),
            new \DateTimeImmutable('2023-06-15'),
            ['特种作业培训'],
            '有效'
        );

        // 2024年到期
        $qualification2 = InstitutionQualification::create(
            $this->testInstitution,
            '安全培训资质',
            '资质2',
            'CERT002',
            '应急管理部',
            new \DateTimeImmutable('2022-01-01'),
            new \DateTimeImmutable('2022-01-01'),
            new \DateTimeImmutable('2024-12-31'),
            ['特种作业培训'],
            '有效'
        );

        // 2025年到期
        $qualification3 = InstitutionQualification::create(
            $this->testInstitution,
            '安全培训资质',
            '资质3',
            'CERT003',
            '应急管理部',
            new \DateTimeImmutable('2022-01-01'),
            new \DateTimeImmutable('2022-01-01'),
            new \DateTimeImmutable('2025-06-15'),
            ['特种作业培训'],
            '有效'
        );

        $this->entityManager->persist($qualification1);
        $this->entityManager->persist($qualification2);
        $this->entityManager->persist($qualification3);
        $this->entityManager->flush();

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');

        $results = $this->repository->findByValidDateRange($startDate, $endDate);

        $this->assertCount(1, $results);
        $this->assertEquals('CERT002', $results[0]->getCertificateNumber());
    }

    /**
     * 测试查找需要续期提醒的资质
     */
    public function test_findNeedingRenewalReminder_returnsQualificationsNeedingReminder(): void
    {
        // 45天后到期（需要提醒）
        $needsReminder = $this->createTestQualification(
            '安全培训资质',
            '需要提醒',
            'CERT001',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+45 days')
        );

        // 90天后到期（不需要提醒）
        $noReminder = $this->createTestQualification(
            '安全培训资质',
            '不需要提醒',
            'CERT002',
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable('+90 days')
        );

        // 已过期（不需要提醒）
        $expired = $this->createTestQualification(
            '安全培训资质',
            '已过期',
            'CERT003',
            new \DateTimeImmutable('-2 years'),
            new \DateTimeImmutable('-1 year')
        );

        $results = $this->repository->findNeedingRenewalReminder(60);

        $this->assertCount(1, $results);
        $this->assertEquals('CERT001', $results[0]->getCertificateNumber());
    }

    /**
     * 测试分页查询资质 - 无条件
     */
    public function test_findPaginated_withoutCriteria_returnsCorrectPagination(): void
    {
        // 创建5个资质
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestQualification('安全培训资质', "资质{$i}", "CERT00{$i}");
            usleep(1000);
        }

        $result = $this->repository->findPaginated(1, 3);

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(3, $result['limit']);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(3, $result['data']);
    }

    /**
     * 测试分页查询资质 - 按类型过滤
     */
    public function test_findPaginated_withTypeCriteria_returnsFilteredResults(): void
    {
        $this->createTestQualification('安全培训资质', '资质1', 'CERT001');
        $this->createTestQualification('安全培训资质', '资质2', 'CERT002');
        $this->createTestQualification('办学许可证', '资质3', 'CERT003');

        $result = $this->repository->findPaginated(1, 10, ['type' => '安全培训资质']);

        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['data']);
        foreach ($result['data'] as $qualification) {
            $this->assertEquals('安全培训资质', $qualification->getQualificationType());
        }
    }

    /**
     * 测试分页查询资质 - 按状态过滤
     */
    public function test_findPaginated_withStatusCriteria_returnsFilteredResults(): void
    {
        $this->createTestQualification('安全培训资质', '有效资质1', 'CERT001', null, null, '有效');
        $this->createTestQualification('安全培训资质', '有效资质2', 'CERT002', null, null, '有效');
        $this->createTestQualification('安全培训资质', '暂停资质', 'CERT003', null, null, '暂停');

        $result = $this->repository->findPaginated(1, 10, ['status' => '有效']);

        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['data']);
        foreach ($result['data'] as $qualification) {
            $this->assertEquals('有效', $qualification->getQualificationStatus());
        }
    }

    /**
     * 测试空结果的统计
     */
    public function test_getStatistics_withNoData_returnsZeroStatistics(): void
    {
        // 清理所有资质
        $this->entityManager->createQuery('DELETE FROM ' . InstitutionQualification::class)->execute();
        $this->entityManager->flush();

        $stats = $this->repository->getStatistics();

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['valid']);
        $this->assertEquals(0, $stats['expiring_soon']);
        $this->assertEmpty($stats['by_status']);
        $this->assertEmpty($stats['by_type']);
    }
} 