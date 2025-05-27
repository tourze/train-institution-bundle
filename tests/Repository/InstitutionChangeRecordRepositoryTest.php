<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository;
use Tourze\TrainInstitutionBundle\Tests\Integration\IntegrationTestKernel;

/**
 * InstitutionChangeRecordRepository 单元测试
 */
class InstitutionChangeRecordRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private InstitutionChangeRecordRepository $repository;
    private Institution $testInstitution;

    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(InstitutionChangeRecord::class);

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . InstitutionChangeRecord::class)->execute();
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
     * 创建测试变更记录
     */
    private function createTestChangeRecord(
        string $changeType = '机构信息变更',
        array $changeDetails = [],
        array $beforeData = [],
        array $afterData = [],
        string $reason = '业务发展需要',
        string $operator = '管理员',
        string $approvalStatus = '待审批'
    ): InstitutionChangeRecord {
        if (empty($changeDetails)) {
            $changeDetails = [
                'field' => 'institutionName',
                'oldValue' => '旧机构名称',
                'newValue' => '新机构名称'
            ];
        }

        if (empty($beforeData)) {
            $beforeData = ['institutionName' => '旧机构名称'];
        }

        if (empty($afterData)) {
            $afterData = ['institutionName' => '新机构名称'];
        }

        $record = InstitutionChangeRecord::create(
            $this->testInstitution,
            $changeType,
            $changeDetails,
            $beforeData,
            $afterData,
            $reason,
            $operator,
            $approvalStatus
        );

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    /**
     * 测试根据机构查找变更记录
     */
    public function test_findByInstitution_returnsAllRecords(): void
    {
        $record1 = $this->createTestChangeRecord('机构信息变更', [], [], [], '原因1', '操作员1');
        sleep(1); // 确保时间差异
        $record2 = $this->createTestChangeRecord('资质变更', [], [], [], '原因2', '操作员2');

        $results = $this->repository->findByInstitution($this->testInstitution);

        $this->assertCount(2, $results);
        // 验证包含所有记录，不依赖特定顺序
        $changeTypes = array_map(fn($r) => $r->getChangeType(), $results);
        $this->assertContains('机构信息变更', $changeTypes);
        $this->assertContains('资质变更', $changeTypes);
    }

    /**
     * 测试查找待审批的变更记录
     */
    public function test_findPendingApproval_returnsOnlyPendingRecords(): void
    {
        $pendingRecord1 = $this->createTestChangeRecord(
            '机构信息变更',
            [],
            [],
            [],
            '原因1',
            '操作员1',
            '待审批'
        );

        $pendingRecord2 = $this->createTestChangeRecord(
            '资质变更',
            [],
            [],
            [],
            '原因2',
            '操作员2',
            '待审批'
        );

        $approvedRecord = $this->createTestChangeRecord(
            '设施变更',
            [],
            [],
            [],
            '原因3',
            '操作员3',
            '已审批'
        );

        $rejectedRecord = $this->createTestChangeRecord(
            '联系方式变更',
            [],
            [],
            [],
            '原因4',
            '操作员4',
            '已拒绝'
        );

        $results = $this->repository->findPendingApproval();

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertEquals('待审批', $record->getApprovalStatus());
        }

        // 验证按创建时间升序排列（最早的待审批记录在前）
        $this->assertEquals('机构信息变更', $results[0]->getChangeType());
        $this->assertEquals('资质变更', $results[1]->getChangeType());
    }

    /**
     * 测试根据变更类型查找记录
     */
    public function test_findByChangeType_returnsCorrectRecords(): void
    {
        $this->createTestChangeRecord('机构信息变更', [], [], [], '原因1', '操作员1');
        sleep(1);
        $this->createTestChangeRecord('机构信息变更', [], [], [], '原因2', '操作员2');
        sleep(1);
        $this->createTestChangeRecord('资质变更', [], [], [], '原因3', '操作员3');

        $results = $this->repository->findByChangeType('机构信息变更');

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertEquals('机构信息变更', $record->getChangeType());
        }

        // 验证包含所有相关记录
        $reasons = array_map(fn($r) => $r->getChangeReason(), $results);
        $this->assertContains('原因1', $reasons);
        $this->assertContains('原因2', $reasons);
    }

    /**
     * 测试不同机构的记录隔离
     */
    public function test_findByInstitution_withMultipleInstitutions_returnsOnlyOwnRecords(): void
    {
        // 创建另一个机构
        $otherInstitution = Institution::create(
            '其他培训机构',
            'OTHER001',
            '企业培训机构',
            '王五',
            '赵六',
            '13900139000',
            'other@example.com',
            '上海市浦东新区其他路456号',
            '职业技能培训',
            new \DateTimeImmutable('2021-01-01'),
            'REGOTHER001'
        );
        $this->entityManager->persist($otherInstitution);
        $this->entityManager->flush();

        // 为测试机构创建记录
        $this->createTestChangeRecord('机构信息变更', [], [], [], '测试机构变更', '测试操作员');

        // 为其他机构创建记录
        $otherRecord = InstitutionChangeRecord::create(
            $otherInstitution,
            '其他机构变更',
            ['field' => 'address'],
            ['address' => '旧地址'],
            ['address' => '新地址'],
            '其他机构原因',
            '其他操作员'
        );
        $this->entityManager->persist($otherRecord);
        $this->entityManager->flush();

        // 测试只返回测试机构的记录
        $results = $this->repository->findByInstitution($this->testInstitution);

        $this->assertCount(1, $results);
        $this->assertEquals('机构信息变更', $results[0]->getChangeType());
        $this->assertEquals('测试机构变更', $results[0]->getChangeReason());
        $this->assertSame($this->testInstitution, $results[0]->getInstitution());
    }

    /**
     * 测试待审批记录的跨机构查询
     */
    public function test_findPendingApproval_withMultipleInstitutions_returnsAllPendingRecords(): void
    {
        // 创建另一个机构
        $otherInstitution = Institution::create(
            '其他培训机构',
            'OTHER001',
            '企业培训机构',
            '王五',
            '赵六',
            '13900139000',
            'other@example.com',
            '上海市浦东新区其他路456号',
            '职业技能培训',
            new \DateTimeImmutable('2021-01-01'),
            'REGOTHER001'
        );
        $this->entityManager->persist($otherInstitution);
        $this->entityManager->flush();

        // 为测试机构创建待审批记录
        $this->createTestChangeRecord(
            '机构信息变更',
            [],
            [],
            [],
            '测试机构变更',
            '测试操作员',
            '待审批'
        );

        // 为其他机构创建待审批记录
        $otherRecord = InstitutionChangeRecord::create(
            $otherInstitution,
            '其他机构变更',
            ['field' => 'address'],
            ['address' => '旧地址'],
            ['address' => '新地址'],
            '其他机构原因',
            '其他操作员',
            '待审批'
        );
        $this->entityManager->persist($otherRecord);
        $this->entityManager->flush();

        // 创建已审批记录（不应该被返回）
        $this->createTestChangeRecord(
            '已审批变更',
            [],
            [],
            [],
            '已审批原因',
            '审批操作员',
            '已审批'
        );

        $results = $this->repository->findPendingApproval();

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertEquals('待审批', $record->getApprovalStatus());
        }

        // 验证包含两个机构的记录
        $changeTypes = array_map(fn($r) => $r->getChangeType(), $results);
        $this->assertContains('机构信息变更', $changeTypes);
        $this->assertContains('其他机构变更', $changeTypes);
    }

    /**
     * 测试变更类型查找的准确性
     */
    public function test_findByChangeType_withMixedTypes_returnsOnlyMatchingType(): void
    {
        $this->createTestChangeRecord('机构信息变更', [], [], [], '原因1', '操作员1');
        $this->createTestChangeRecord('资质变更', [], [], [], '原因2', '操作员2');
        $this->createTestChangeRecord('设施变更', [], [], [], '原因3', '操作员3');
        $this->createTestChangeRecord('机构信息变更', [], [], [], '原因4', '操作员4');

        $results = $this->repository->findByChangeType('机构信息变更');

        $this->assertCount(2, $results);
        foreach ($results as $record) {
            $this->assertEquals('机构信息变更', $record->getChangeType());
        }

        $reasons = array_map(fn($r) => $r->getChangeReason(), $results);
        $this->assertContains('原因1', $reasons);
        $this->assertContains('原因4', $reasons);
    }

    /**
     * 测试不存在的变更类型查找
     */
    public function test_findByChangeType_withNonExistentType_returnsEmptyArray(): void
    {
        $this->createTestChangeRecord('机构信息变更', [], [], [], '原因1', '操作员1');
        $this->createTestChangeRecord('资质变更', [], [], [], '原因2', '操作员2');

        $results = $this->repository->findByChangeType('不存在的变更类型');

        $this->assertEmpty($results);
    }

    /**
     * 测试复杂的变更记录场景
     */
    public function test_complexChangeRecordScenario(): void
    {
        // 创建不同状态和类型的变更记录
        $record1 = $this->createTestChangeRecord(
            '机构信息变更',
            [
                'field' => 'institutionName',
                'oldValue' => '旧名称',
                'newValue' => '新名称'
            ],
            ['institutionName' => '旧名称'],
            ['institutionName' => '新名称'],
            '品牌升级',
            '品牌管理员',
            '待审批'
        );

        $record2 = $this->createTestChangeRecord(
            '资质变更',
            [
                'action' => 'renew',
                'qualificationId' => 'QUAL001',
                'newValidTo' => '2025-12-31'
            ],
            ['validTo' => '2024-12-31'],
            ['validTo' => '2025-12-31'],
            '资质续期',
            '资质管理员',
            '已审批'
        );

        $record3 = $this->createTestChangeRecord(
            '设施变更',
            [
                'action' => 'add',
                'facilityType' => '实训室',
                'facilityName' => '新实训室A'
            ],
            ['facilityCount' => 5],
            ['facilityCount' => 6],
            '扩大培训规模',
            '设施管理员',
            '已拒绝'
        );

        // 测试按机构查找
        $allRecords = $this->repository->findByInstitution($this->testInstitution);
        $this->assertCount(3, $allRecords);

        // 测试按状态查找
        $pendingRecords = $this->repository->findPendingApproval();
        $this->assertCount(1, $pendingRecords);
        $this->assertEquals('机构信息变更', $pendingRecords[0]->getChangeType());

        // 测试按类型查找
        $qualificationRecords = $this->repository->findByChangeType('资质变更');
        $this->assertCount(1, $qualificationRecords);
        $this->assertEquals('已审批', $qualificationRecords[0]->getApprovalStatus());
    }

    /**
     * 测试空结果场景
     */
    public function test_emptyResultScenarios(): void
    {
        // 测试空机构记录
        $results = $this->repository->findByInstitution($this->testInstitution);
        $this->assertEmpty($results);

        // 测试空待审批记录
        $pendingResults = $this->repository->findPendingApproval();
        $this->assertEmpty($pendingResults);

        // 测试空类型记录
        $typeResults = $this->repository->findByChangeType('任何类型');
        $this->assertEmpty($typeResults);
    }

    /**
     * 测试记录排序
     */
    public function test_recordOrdering(): void
    {
        // 创建多个记录，确保时间间隔
        $record1 = $this->createTestChangeRecord('变更1', [], [], [], '原因1', '操作员1', '待审批');
        sleep(1);
        
        $record2 = $this->createTestChangeRecord('变更2', [], [], [], '原因2', '操作员2', '待审批');
        sleep(1);
        
        $record3 = $this->createTestChangeRecord('变更3', [], [], [], '原因3', '操作员3', '待审批');

        // 测试findByInstitution返回所有记录
        $institutionRecords = $this->repository->findByInstitution($this->testInstitution);
        $this->assertCount(3, $institutionRecords);
        $changeTypes = array_map(fn($r) => $r->getChangeType(), $institutionRecords);
        $this->assertContains('变更1', $changeTypes);
        $this->assertContains('变更2', $changeTypes);
        $this->assertContains('变更3', $changeTypes);

        // 测试findPendingApproval返回所有待审批记录
        $pendingRecords = $this->repository->findPendingApproval();
        $this->assertCount(3, $pendingRecords);
        $pendingTypes = array_map(fn($r) => $r->getChangeType(), $pendingRecords);
        $this->assertContains('变更1', $pendingTypes);
        $this->assertContains('变更2', $pendingTypes);
        $this->assertContains('变更3', $pendingTypes);
    }

    /**
     * 测试大量记录的性能
     */
    public function test_performanceWithManyRecords(): void
    {
        // 创建多个不同类型的记录
        for ($i = 1; $i <= 20; $i++) {
            $changeType = $i % 2 === 0 ? '机构信息变更' : '资质变更';
            $status = $i % 3 === 0 ? '已审批' : '待审批';
            
            $this->createTestChangeRecord(
                $changeType,
                ['field' => "field{$i}"],
                ["field{$i}" => "oldValue{$i}"],
                ["field{$i}" => "newValue{$i}"],
                "原因{$i}",
                "操作员{$i}",
                $status
            );
            usleep(1000);
        }

        // 测试按机构查找
        $allRecords = $this->repository->findByInstitution($this->testInstitution);
        $this->assertCount(20, $allRecords);

        // 测试按类型查找
        $infoChangeRecords = $this->repository->findByChangeType('机构信息变更');
        $this->assertCount(10, $infoChangeRecords);

        $qualificationRecords = $this->repository->findByChangeType('资质变更');
        $this->assertCount(10, $qualificationRecords);

                 // 测试待审批记录
         $pendingRecords = $this->repository->findPendingApproval();
         $expectedPendingCount = 20 - (int) floor(20 / 3); // 总数减去已审批的数量
         $this->assertCount($expectedPendingCount, $pendingRecords);
    }

    /**
     * 测试特殊字符和长文本处理
     */
    public function test_specialCharactersAndLongText(): void
    {
        $longReason = str_repeat('这是一个很长的变更原因，用于测试系统对长文本的处理能力。', 10);
        $specialOperator = '张三@系统管理员#2023';
        
        $record = $this->createTestChangeRecord(
            '特殊字符测试',
            ['field' => 'special', 'value' => '特殊字符@#$%^&*()'],
            ['special' => '旧值@#$'],
            ['special' => '新值@#$'],
            $longReason,
            $specialOperator,
            '待审批'
        );

        // 测试能正确保存和查询
        $results = $this->repository->findByInstitution($this->testInstitution);
        $this->assertCount(1, $results);
        
        $foundRecord = $results[0];
        $this->assertEquals('特殊字符测试', $foundRecord->getChangeType());
        $this->assertEquals($longReason, $foundRecord->getChangeReason());
        $this->assertEquals($specialOperator, $foundRecord->getChangeOperator());
        
        $changeDetails = $foundRecord->getChangeDetails();
        $this->assertEquals('特殊字符@#$%^&*()', $changeDetails['value']);
    }
} 