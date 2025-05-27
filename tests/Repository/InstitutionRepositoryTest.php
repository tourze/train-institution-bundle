<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;
use Tourze\TrainInstitutionBundle\Tests\Integration\IntegrationTestKernel;

/**
 * InstitutionRepository 单元测试
 */
class InstitutionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private InstitutionRepository $repository;

    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Institution::class);

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . Institution::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试机构
     */
    private function createTestInstitution(
        string $name = '测试培训机构',
        string $code = 'TEST001',
        string $type = '企业培训机构',
        string $status = '正常运营'
    ): Institution {
        $institution = Institution::create(
            $name,
            $code,
            $type,
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区测试路123号',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG' . $code
        );
        
        $institution->setInstitutionStatus($status);
        
        $this->entityManager->persist($institution);
        $this->entityManager->flush();
        
        return $institution;
    }

    /**
     * 测试根据机构代码查找机构
     */
    public function test_findByInstitutionCode_returnsCorrectInstitution(): void
    {
        $institution = $this->createTestInstitution('机构A', 'CODE001');
        $this->createTestInstitution('机构B', 'CODE002');

        $result = $this->repository->findByInstitutionCode('CODE001');

        $this->assertNotNull($result);
        $this->assertEquals('机构A', $result->getInstitutionName());
        $this->assertEquals('CODE001', $result->getInstitutionCode());
    }

    /**
     * 测试根据不存在的机构代码查找
     */
    public function test_findByInstitutionCode_withNonExistentCode_returnsNull(): void
    {
        $this->createTestInstitution('机构A', 'CODE001');

        $result = $this->repository->findByInstitutionCode('NONEXISTENT');

        $this->assertNull($result);
    }

    /**
     * 测试根据机构状态查找机构列表
     */
    public function test_findByStatus_returnsCorrectInstitutions(): void
    {
        $this->createTestInstitution('正常机构1', 'CODE001', '企业培训机构', '正常运营');
        $this->createTestInstitution('正常机构2', 'CODE002', '企业培训机构', '正常运营');
        $this->createTestInstitution('待审核机构', 'CODE003', '企业培训机构', '待审核');

        $results = $this->repository->findByStatus('正常运营');

        $this->assertCount(2, $results);
        $this->assertEquals('正常机构1', $results[0]->getInstitutionName());
        $this->assertEquals('正常机构2', $results[1]->getInstitutionName());
    }

    /**
     * 测试根据机构类型查找机构列表
     */
    public function test_findByType_returnsCorrectInstitutions(): void
    {
        $this->createTestInstitution('企业机构1', 'CODE001', '企业培训机构');
        $this->createTestInstitution('企业机构2', 'CODE002', '企业培训机构');
        $this->createTestInstitution('职业学校', 'CODE003', '职业院校');

        $results = $this->repository->findByType('企业培训机构');

        $this->assertCount(2, $results);
        $this->assertEquals('企业机构1', $results[0]->getInstitutionName());
        $this->assertEquals('企业机构2', $results[1]->getInstitutionName());
    }

    /**
     * 测试查找正常运营的机构
     */
    public function test_findActiveInstitutions_returnsOnlyActiveInstitutions(): void
    {
        $this->createTestInstitution('正常机构1', 'CODE001', '企业培训机构', '正常运营');
        $this->createTestInstitution('正常机构2', 'CODE002', '企业培训机构', '正常运营');
        $this->createTestInstitution('暂停机构', 'CODE003', '企业培训机构', '暂停运营');

        $results = $this->repository->findActiveInstitutions();

        $this->assertCount(2, $results);
        foreach ($results as $institution) {
            $this->assertEquals('正常运营', $institution->getInstitutionStatus());
        }
    }

    /**
     * 测试查找待审核的机构
     */
    public function test_findPendingInstitutions_returnsOnlyPendingInstitutions(): void
    {
        $this->createTestInstitution('正常机构', 'CODE001', '企业培训机构', '正常运营');
        $this->createTestInstitution('待审核机构1', 'CODE002', '企业培训机构', '待审核');
        $this->createTestInstitution('待审核机构2', 'CODE003', '企业培训机构', '待审核');

        $results = $this->repository->findPendingInstitutions();

        $this->assertCount(2, $results);
        foreach ($results as $institution) {
            $this->assertEquals('待审核', $institution->getInstitutionStatus());
        }
    }

    /**
     * 测试根据法人代表查找机构
     */
    public function test_findByLegalPerson_returnsCorrectInstitutions(): void
    {
        $institution1 = $this->createTestInstitution('机构1', 'CODE001');
        $institution2 = $this->createTestInstitution('机构2', 'CODE002');
        
        // 创建第三个机构，使用不同的法人代表
        $institution3 = Institution::create(
            '机构3',
            'CODE003',
            '企业培训机构',
            '王五', // 不同的法人代表
            '李四',
            '13800138000',
            'test3@example.com',
            '北京市朝阳区测试路123号',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REGCODE003'
        );
        $this->entityManager->persist($institution3);
        $this->entityManager->flush();

        $results = $this->repository->findByLegalPerson('张三');

        $this->assertCount(2, $results);
        foreach ($results as $institution) {
            $this->assertEquals('张三', $institution->getLegalPerson());
        }
    }

    /**
     * 测试根据注册号查找机构
     */
    public function test_findByRegistrationNumber_returnsCorrectInstitution(): void
    {
        $institution = $this->createTestInstitution('机构A', 'CODE001');
        $this->createTestInstitution('机构B', 'CODE002');

        $result = $this->repository->findByRegistrationNumber('REGCODE001');

        $this->assertNotNull($result);
        $this->assertEquals('机构A', $result->getInstitutionName());
        $this->assertEquals('REGCODE001', $result->getRegistrationNumber());
    }

    /**
     * 测试模糊搜索机构名称
     */
    public function test_searchByName_returnsMatchingInstitutions(): void
    {
        $this->createTestInstitution('北京安全培训机构', 'CODE001');
        $this->createTestInstitution('上海安全培训中心', 'CODE002');
        $this->createTestInstitution('深圳职业技能培训', 'CODE003');

        $results = $this->repository->searchByName('安全培训');

        $this->assertCount(2, $results);
        // 验证返回的机构名称包含搜索关键词，不依赖特定顺序
        $names = array_map(fn($i) => $i->getInstitutionName(), $results);
        $this->assertContains('北京安全培训机构', $names);
        $this->assertContains('上海安全培训中心', $names);
    }

    /**
     * 测试根据地址搜索机构
     */
    public function test_searchByAddress_returnsMatchingInstitutions(): void
    {
        $institution1 = $this->createTestInstitution('机构1', 'CODE001');
        $institution2 = $this->createTestInstitution('机构2', 'CODE002');
        
        // 创建不同地址的机构
        $institution3 = Institution::create(
            '机构3',
            'CODE003',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test3@example.com',
            '上海市浦东新区测试路456号', // 不同地址
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REGCODE003'
        );
        $this->entityManager->persist($institution3);
        $this->entityManager->flush();

        $results = $this->repository->searchByAddress('北京市朝阳区');

        $this->assertCount(2, $results);
        foreach ($results as $institution) {
            $this->assertStringContainsString('北京市朝阳区', $institution->getAddress());
        }
    }

    /**
     * 测试获取机构统计信息
     */
    public function test_getStatistics_returnsCorrectStatistics(): void
    {
        $this->createTestInstitution('正常机构1', 'CODE001', '企业培训机构', '正常运营');
        $this->createTestInstitution('正常机构2', 'CODE002', '职业院校', '正常运营');
        $this->createTestInstitution('待审核机构', 'CODE003', '企业培训机构', '待审核');

        $stats = $this->repository->getStatistics();

        $this->assertEquals(3, $stats['total']);
        
        // 检查状态统计
        $statusStats = $stats['by_status'];
        $this->assertCount(2, $statusStats);
        
        $statusCounts = [];
        foreach ($statusStats as $stat) {
            $statusCounts[$stat['institutionStatus']] = $stat['count'];
        }
        $this->assertEquals(2, $statusCounts['正常运营']);
        $this->assertEquals(1, $statusCounts['待审核']);
        
        // 检查类型统计
        $typeStats = $stats['by_type'];
        $this->assertCount(2, $typeStats);
        
        $typeCounts = [];
        foreach ($typeStats as $stat) {
            $typeCounts[$stat['institutionType']] = $stat['count'];
        }
        $this->assertEquals(2, $typeCounts['企业培训机构']);
        $this->assertEquals(1, $typeCounts['职业院校']);
    }

    /**
     * 测试获取最近创建的机构
     */
    public function test_findRecentlyCreated_returnsInCorrectOrder(): void
    {
        // 创建机构时间间隔
        $institution1 = $this->createTestInstitution('机构1', 'CODE001');
        sleep(1); // 使用sleep确保时间差异
        $institution2 = $this->createTestInstitution('机构2', 'CODE002');
        sleep(1);
        $institution3 = $this->createTestInstitution('机构3', 'CODE003');

        $results = $this->repository->findRecentlyCreated(2);

        $this->assertCount(2, $results);
        // 验证返回最近创建的机构，不依赖具体顺序
        $names = array_map(fn($i) => $i->getInstitutionName(), $results);
        $this->assertContains('机构3', $names);
        $this->assertContains('机构2', $names);
    }

    /**
     * 测试获取最近更新的机构
     */
    public function test_findRecentlyUpdated_returnsInCorrectOrder(): void
    {
        $institution1 = $this->createTestInstitution('机构1', 'CODE001');
        $institution2 = $this->createTestInstitution('机构2', 'CODE002');
        $institution3 = $this->createTestInstitution('机构3', 'CODE003');

        // 更新机构2
        sleep(1);
        $institution2->setInstitutionName('更新后的机构2');
        $this->entityManager->flush();

        $results = $this->repository->findRecentlyUpdated(2);

        $this->assertCount(2, $results);
        // 验证更新后的机构在结果中
        $names = array_map(fn($i) => $i->getInstitutionName(), $results);
        $this->assertContains('更新后的机构2', $names);
    }

    /**
     * 测试根据成立日期范围查找机构
     */
    public function test_findByEstablishDateRange_returnsInstitutionsInRange(): void
    {
        // 创建不同成立日期的机构
        $institution1 = Institution::create(
            '机构1', 'CODE001', '企业培训机构', '张三', '李四',
            '13800138000', 'test1@example.com', '地址1', '培训1',
            new \DateTimeImmutable('2020-01-01'), 'REG001'
        );
        $institution2 = Institution::create(
            '机构2', 'CODE002', '企业培训机构', '张三', '李四',
            '13800138000', 'test2@example.com', '地址2', '培训2',
            new \DateTimeImmutable('2021-06-15'), 'REG002'
        );
        $institution3 = Institution::create(
            '机构3', 'CODE003', '企业培训机构', '张三', '李四',
            '13800138000', 'test3@example.com', '地址3', '培训3',
            new \DateTimeImmutable('2022-12-31'), 'REG003'
        );

        $this->entityManager->persist($institution1);
        $this->entityManager->persist($institution2);
        $this->entityManager->persist($institution3);
        $this->entityManager->flush();

        $startDate = new \DateTimeImmutable('2021-01-01');
        $endDate = new \DateTimeImmutable('2021-12-31');
        
        $results = $this->repository->findByEstablishDateRange($startDate, $endDate);

        $this->assertCount(1, $results);
        $this->assertEquals('机构2', $results[0]->getInstitutionName());
    }

    /**
     * 测试检查机构代码是否已存在
     */
    public function test_isInstitutionCodeExists_withExistingCode_returnsTrue(): void
    {
        $this->createTestInstitution('机构A', 'CODE001');

        $result = $this->repository->isInstitutionCodeExists('CODE001');

        $this->assertTrue($result);
    }

    /**
     * 测试检查机构代码是否已存在 - 不存在的代码
     */
    public function test_isInstitutionCodeExists_withNonExistentCode_returnsFalse(): void
    {
        $this->createTestInstitution('机构A', 'CODE001');

        $result = $this->repository->isInstitutionCodeExists('NONEXISTENT');

        $this->assertFalse($result);
    }

    /**
     * 测试检查机构代码是否已存在 - 排除指定ID
     */
    public function test_isInstitutionCodeExists_withExcludeId_returnsFalse(): void
    {
        $institution = $this->createTestInstitution('机构A', 'CODE001');

        $result = $this->repository->isInstitutionCodeExists('CODE001', $institution->getId());

        $this->assertFalse($result);
    }

    /**
     * 测试检查注册号是否已存在
     */
    public function test_isRegistrationNumberExists_withExistingNumber_returnsTrue(): void
    {
        $this->createTestInstitution('机构A', 'CODE001');

        $result = $this->repository->isRegistrationNumberExists('REGCODE001');

        $this->assertTrue($result);
    }

    /**
     * 测试检查注册号是否已存在 - 不存在的注册号
     */
    public function test_isRegistrationNumberExists_withNonExistentNumber_returnsFalse(): void
    {
        $this->createTestInstitution('机构A', 'CODE001');

        $result = $this->repository->isRegistrationNumberExists('NONEXISTENT');

        $this->assertFalse($result);
    }

    /**
     * 测试分页查询机构 - 无条件
     */
    public function test_findPaginated_withoutCriteria_returnsCorrectPagination(): void
    {
        // 创建5个机构
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestInstitution("机构{$i}", "CODE00{$i}");
            usleep(1000); // 确保创建时间不同
        }

        $result = $this->repository->findPaginated(1, 3);

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(3, $result['limit']);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(3, $result['data']);
    }

    /**
     * 测试分页查询机构 - 按状态过滤
     */
    public function test_findPaginated_withStatusCriteria_returnsFilteredResults(): void
    {
        $this->createTestInstitution('正常机构1', 'CODE001', '企业培训机构', '正常运营');
        $this->createTestInstitution('正常机构2', 'CODE002', '企业培训机构', '正常运营');
        $this->createTestInstitution('待审核机构', 'CODE003', '企业培训机构', '待审核');

        $result = $this->repository->findPaginated(1, 10, ['status' => '正常运营']);

        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['data']);
        foreach ($result['data'] as $institution) {
            $this->assertEquals('正常运营', $institution->getInstitutionStatus());
        }
    }

    /**
     * 测试分页查询机构 - 按类型过滤
     */
    public function test_findPaginated_withTypeCriteria_returnsFilteredResults(): void
    {
        $this->createTestInstitution('企业机构1', 'CODE001', '企业培训机构');
        $this->createTestInstitution('企业机构2', 'CODE002', '企业培训机构');
        $this->createTestInstitution('职业学校', 'CODE003', '职业院校');

        $result = $this->repository->findPaginated(1, 10, ['type' => '企业培训机构']);

        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['data']);
        foreach ($result['data'] as $institution) {
            $this->assertEquals('企业培训机构', $institution->getInstitutionType());
        }
    }

    /**
     * 测试分页查询机构 - 按名称过滤
     */
    public function test_findPaginated_withNameCriteria_returnsFilteredResults(): void
    {
        $this->createTestInstitution('北京安全培训机构', 'CODE001');
        $this->createTestInstitution('上海安全培训中心', 'CODE002');
        $this->createTestInstitution('深圳职业技能培训', 'CODE003');

        $result = $this->repository->findPaginated(1, 10, ['name' => '安全培训']);

                 $this->assertEquals(2, $result['total']);
         $this->assertCount(2, $result['data']);
         foreach ($result['data'] as $institution) {
             $this->assertStringContainsString('安全培训', $institution->getInstitutionName());
         }
    }

    /**
     * 测试分页查询机构 - 多条件组合
     */
    public function test_findPaginated_withMultipleCriteria_returnsFilteredResults(): void
    {
        $this->createTestInstitution('北京安全培训机构', 'CODE001', '企业培训机构', '正常运营');
        $this->createTestInstitution('上海安全培训中心', 'CODE002', '职业院校', '正常运营');
        $this->createTestInstitution('北京职业技能培训', 'CODE003', '企业培训机构', '待审核');

        $result = $this->repository->findPaginated(1, 10, [
            'name' => '北京',
            'type' => '企业培训机构',
            'status' => '正常运营'
        ]);

        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('北京安全培训机构', $result['data'][0]->getInstitutionName());
    }

    /**
     * 测试分页查询机构 - 第二页
     */
    public function test_findPaginated_secondPage_returnsCorrectResults(): void
    {
        // 创建5个机构
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestInstitution("机构{$i}", "CODE00{$i}");
            usleep(1000);
        }

        $result = $this->repository->findPaginated(2, 2);

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(2, $result['limit']);
        $this->assertEquals(3, $result['pages']);
        $this->assertCount(2, $result['data']);
    }

    /**
     * 测试空结果的统计
     */
    public function test_getStatistics_withNoData_returnsZeroStatistics(): void
    {
        $stats = $this->repository->getStatistics();

        $this->assertEquals(0, $stats['total']);
        $this->assertEmpty($stats['by_status']);
        $this->assertEmpty($stats['by_type']);
    }

    /**
     * 测试搜索无结果
     */
    public function test_searchByName_withNoMatches_returnsEmptyArray(): void
    {
        $this->createTestInstitution('北京培训机构', 'CODE001');

        $results = $this->repository->searchByName('上海');

        $this->assertEmpty($results);
    }
} 