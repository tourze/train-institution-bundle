<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;
use Tourze\TrainInstitutionBundle\Service\ChangeRecordService;

/**
 * ChangeRecordService 单元测试
 */
class ChangeRecordServiceTest extends TestCase
{
    private ChangeRecordService $service;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&InstitutionRepository $institutionRepository;
    private MockObject&InstitutionChangeRecordRepository $changeRecordRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->institutionRepository = $this->createMock(InstitutionRepository::class);
        $this->changeRecordRepository = $this->createMock(InstitutionChangeRecordRepository::class);

        $this->service = new ChangeRecordService(
            $this->entityManager,
            $this->institutionRepository,
            $this->changeRecordRepository
        );
    }

    /**
     * 创建测试机构
     */
    private function createTestInstitution(): Institution
    {
        return Institution::create(
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
    }

    /**
     * 创建测试变更数据
     */
    private function createTestChangeData(): array
    {
        return [
            'changeType' => '机构信息变更',
            'changeDetails' => [
                'field' => 'institutionName',
                'oldValue' => '旧机构名称',
                'newValue' => '新机构名称'
            ],
            'beforeData' => ['institutionName' => '旧机构名称'],
            'afterData' => ['institutionName' => '新机构名称'],
            'changeReason' => '业务发展需要',
            'changeOperator' => '管理员',
            'approvalStatus' => '待审批'
        ];
    }

    /**
     * 测试记录变更 - 成功场景
     */
    public function test_recordChange_withValidData_returnsChangeRecord(): void
    {
        $institution = $this->createTestInstitution();
        $changeData = $this->createTestChangeData();
        $institutionId = 'test-institution-id';

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(InstitutionChangeRecord::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->recordChange($institutionId, $changeData);

        $this->assertInstanceOf(InstitutionChangeRecord::class, $result);
        $this->assertEquals('机构信息变更', $result->getChangeType());
        $this->assertEquals('业务发展需要', $result->getChangeReason());
        $this->assertEquals('管理员', $result->getChangeOperator());
        $this->assertEquals('待审批', $result->getApprovalStatus());
    }

    /**
     * 测试记录变更 - 机构不存在
     */
    public function test_recordChange_withNonExistentInstitution_throwsException(): void
    {
        $changeData = $this->createTestChangeData();
        $institutionId = 'non-existent-id';

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('机构不存在');

        $this->service->recordChange($institutionId, $changeData);
    }

    /**
     * 测试记录变更 - 无效数据
     */
    public function test_recordChange_withInvalidData_throwsException(): void
    {
        $invalidChangeData = [
            'changeType' => '', // 空的变更类型
            'changeDetails' => [],
            'beforeData' => [],
            'afterData' => [],
            'changeReason' => '',
            'changeOperator' => ''
        ];
        $institutionId = 'test-institution-id';

        // 数据验证在机构查找之前，所以不会调用find方法
        $this->institutionRepository
            ->expects($this->never())
            ->method('find');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('变更类型不能为空');

        $this->service->recordChange($institutionId, $invalidChangeData);
    }

    /**
     * 测试审批变更 - 成功场景
     */
    public function test_approveChange_withValidRecord_returnsApprovedRecord(): void
    {
        $institution = $this->createTestInstitution();
        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '机构信息变更',
            ['field' => 'name'],
            ['name' => 'old'],
            ['name' => 'new'],
            '测试原因',
            '操作员',
            '待审批'
        );
        $recordId = 'test-record-id';
        $approver = '审批员';

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('find')
            ->with($recordId)
            ->willReturn($changeRecord);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->approveChange($recordId, $approver);

        $this->assertEquals('已审批', $result->getApprovalStatus());
        $this->assertEquals($approver, $result->getApprover());
        $this->assertNotNull($result->getApprovalDate());
    }

    /**
     * 测试审批变更 - 记录不存在
     */
    public function test_approveChange_withNonExistentRecord_throwsException(): void
    {
        $recordId = 'non-existent-id';
        $approver = '审批员';

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('find')
            ->with($recordId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('变更记录不存在');

        $this->service->approveChange($recordId, $approver);
    }

    /**
     * 测试审批变更 - 已处理的记录
     */
    public function test_approveChange_withAlreadyProcessedRecord_throwsException(): void
    {
        $institution = $this->createTestInstitution();
        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '机构信息变更',
            ['field' => 'name'],
            ['name' => 'old'],
            ['name' => 'new'],
            '测试原因',
            '操作员',
            '已审批' // 已经审批过
        );
        $recordId = 'test-record-id';
        $approver = '审批员';

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('find')
            ->with($recordId)
            ->willReturn($changeRecord);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('该变更记录已处理，无法重复审批');

        $this->service->approveChange($recordId, $approver);
    }

    /**
     * 测试拒绝变更 - 成功场景
     */
    public function test_rejectChange_withValidRecord_returnsRejectedRecord(): void
    {
        $institution = $this->createTestInstitution();
        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '机构信息变更',
            ['field' => 'name'],
            ['name' => 'old'],
            ['name' => 'new'],
            '测试原因',
            '操作员',
            '待审批'
        );
        $recordId = 'test-record-id';
        $approver = '审批员';
        $reason = '不符合要求';

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('find')
            ->with($recordId)
            ->willReturn($changeRecord);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->rejectChange($recordId, $approver, $reason);

        $this->assertEquals('已拒绝', $result->getApprovalStatus());
        $this->assertEquals($approver, $result->getApprover());
        $this->assertNotNull($result->getApprovalDate());
    }

    /**
     * 测试拒绝变更 - 记录不存在
     */
    public function test_rejectChange_withNonExistentRecord_throwsException(): void
    {
        $recordId = 'non-existent-id';
        $approver = '审批员';

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('find')
            ->with($recordId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('变更记录不存在');

        $this->service->rejectChange($recordId, $approver);
    }

    /**
     * 测试获取变更历史
     */
    public function test_getChangeHistory_withValidInstitution_returnsChangeRecords(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';
        $changeRecords = [
            InstitutionChangeRecord::create($institution, '变更1', [], [], [], '原因1', '操作员1'),
            InstitutionChangeRecord::create($institution, '变更2', [], [], [], '原因2', '操作员2'),
        ];

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('findByInstitution')
            ->with($institution)
            ->willReturn($changeRecords);

        $result = $this->service->getChangeHistory($institutionId);

        $this->assertCount(2, $result);
        $this->assertEquals($changeRecords, $result);
    }

    /**
     * 测试获取变更历史 - 机构不存在
     */
    public function test_getChangeHistory_withNonExistentInstitution_throwsException(): void
    {
        $institutionId = 'non-existent-id';

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('机构不存在');

        $this->service->getChangeHistory($institutionId);
    }

    /**
     * 测试获取待审批变更
     */
    public function test_getPendingChanges_returnsPendingRecords(): void
    {
        $institution = $this->createTestInstitution();
        $pendingRecords = [
            InstitutionChangeRecord::create($institution, '变更1', [], [], [], '原因1', '操作员1', '待审批'),
            InstitutionChangeRecord::create($institution, '变更2', [], [], [], '原因2', '操作员2', '待审批'),
        ];

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('findPendingApproval')
            ->willReturn($pendingRecords);

        $result = $this->service->getPendingChanges();

        $this->assertCount(2, $result);
        $this->assertEquals($pendingRecords, $result);
    }

    /**
     * 测试根据类型获取变更记录
     */
    public function test_getChangesByType_returnsRecordsOfSpecificType(): void
    {
        $institution = $this->createTestInstitution();
        $changeType = '机构信息变更';
        $typeRecords = [
            InstitutionChangeRecord::create($institution, $changeType, [], [], [], '原因1', '操作员1'),
            InstitutionChangeRecord::create($institution, $changeType, [], [], [], '原因2', '操作员2'),
        ];

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('findByChangeType')
            ->with($changeType)
            ->willReturn($typeRecords);

        $result = $this->service->getChangesByType($changeType);

        $this->assertCount(2, $result);
        $this->assertEquals($typeRecords, $result);
    }

    /**
     * 测试生成变更报告
     */
    public function test_generateChangeReport_withValidInstitution_returnsReport(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';
        
        $changeRecords = [
            InstitutionChangeRecord::create($institution, '机构信息变更', [], [], [], '原因1', '操作员1', '已审批'),
            InstitutionChangeRecord::create($institution, '资质变更', [], [], [], '原因2', '操作员2', '待审批'),
            InstitutionChangeRecord::create($institution, '机构信息变更', [], [], [], '原因3', '操作员1', '已拒绝'),
        ];

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('findByInstitution')
            ->with($institution)
            ->willReturn($changeRecords);

        $result = $this->service->generateChangeReport($institutionId);

        $this->assertArrayHasKey('institution', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('recent_changes', $result);
        $this->assertArrayHasKey('pending_changes', $result);
        $this->assertArrayHasKey('generated_at', $result);

        // 验证汇总信息
        $this->assertEquals(3, $result['summary']['total_changes']);
        $this->assertEquals(1, $result['summary']['pending_approval']);
        $this->assertEquals(1, $result['summary']['approved_changes']);
        $this->assertEquals(1, $result['summary']['rejected_changes']);

        // 验证统计信息
        $this->assertEquals(2, $result['statistics']['by_type']['机构信息变更']);
        $this->assertEquals(1, $result['statistics']['by_type']['资质变更']);
        $this->assertEquals(2, $result['statistics']['by_operator']['操作员1']);
        $this->assertEquals(1, $result['statistics']['by_operator']['操作员2']);
    }

    /**
     * 测试批量审批变更
     */
    public function test_batchApproveChanges_withMixedResults_returnsResults(): void
    {
        $institution = $this->createTestInstitution();
        $validRecord = InstitutionChangeRecord::create($institution, '变更1', [], [], [], '原因1', '操作员1', '待审批');
        $invalidRecord = InstitutionChangeRecord::create($institution, '变更2', [], [], [], '原因2', '操作员2', '已审批');
        
        $recordIds = ['valid-id', 'invalid-id', 'non-existent-id'];
        $approver = '审批员';

        $this->changeRecordRepository
            ->expects($this->exactly(3))
            ->method('find')
            ->willReturnCallback(function ($id) use ($validRecord, $invalidRecord) {
                switch ($id) {
                    case 'valid-id':
                        return $validRecord;
                    case 'invalid-id':
                        return $invalidRecord;
                    case 'non-existent-id':
                        return null;
                    default:
                        return null;
                }
            });

        // 只有成功的记录会触发flush
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->batchApproveChanges($recordIds, $approver);

        $this->assertCount(3, $result);
        $this->assertTrue($result[0]['success']);
        $this->assertFalse($result[1]['success']);
        $this->assertFalse($result[2]['success']);
        $this->assertEquals('变更1', $result[0]['change_type']);
        $this->assertStringContainsString('重复审批', $result[1]['error']);
        $this->assertStringContainsString('不存在', $result[2]['error']);
    }

    /**
     * 测试批量拒绝变更
     */
    public function test_batchRejectChanges_withMixedResults_returnsResults(): void
    {
        $institution = $this->createTestInstitution();
        $validRecord = InstitutionChangeRecord::create($institution, '变更1', [], [], [], '原因1', '操作员1', '待审批');
        
        $recordIds = ['valid-id', 'non-existent-id'];
        $approver = '审批员';
        $reason = '批量拒绝';

        $this->changeRecordRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(function ($id) use ($validRecord) {
                switch ($id) {
                    case 'valid-id':
                        return $validRecord;
                    case 'non-existent-id':
                        return null;
                    default:
                        return null;
                }
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->batchRejectChanges($recordIds, $approver, $reason);

        $this->assertCount(2, $result);
        $this->assertTrue($result[0]['success']);
        $this->assertFalse($result[1]['success']);
        $this->assertEquals('变更1', $result[0]['change_type']);
        $this->assertStringContainsString('不存在', $result[1]['error']);
    }

    /**
     * 测试获取变更详情
     */
    public function test_getChangeDetail_withValidRecord_returnsDetail(): void
    {
        $institution = $this->createTestInstitution();
        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '机构信息变更',
            ['field' => 'name', 'oldValue' => 'old', 'newValue' => 'new'],
            ['name' => 'old'],
            ['name' => 'new'],
            '测试原因',
            '操作员',
            '已审批'
        );
        $changeRecord->approve('审批员');
        $recordId = 'test-record-id';

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('find')
            ->with($recordId)
            ->willReturn($changeRecord);

        $result = $this->service->getChangeDetail($recordId);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('institution', $result);
        $this->assertArrayHasKey('change_type', $result);
        $this->assertArrayHasKey('change_details', $result);
        $this->assertArrayHasKey('before_data', $result);
        $this->assertArrayHasKey('after_data', $result);
        $this->assertArrayHasKey('approval_status', $result);
        $this->assertArrayHasKey('approver', $result);

        $this->assertEquals('机构信息变更', $result['change_type']);
        $this->assertEquals('已审批', $result['approval_status']);
        $this->assertEquals('审批员', $result['approver']);
    }

    /**
     * 测试获取变更详情 - 记录不存在
     */
    public function test_getChangeDetail_withNonExistentRecord_throwsException(): void
    {
        $recordId = 'non-existent-id';

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('find')
            ->with($recordId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('变更记录不存在');

        $this->service->getChangeDetail($recordId);
    }

    /**
     * 测试根据日期范围获取变更记录
     */
    public function test_getChangesByDateRange_withValidRange_returnsFilteredRecords(): void
    {
        $institution = $this->createTestInstitution();
        $institutionId = 'test-institution-id';
        
        // 创建模拟的变更记录，使用Mock对象来控制日期
        $record1 = $this->createMock(InstitutionChangeRecord::class);
        $record1->method('getChangeDate')->willReturn(new \DateTimeImmutable('2023-01-15'));
        
        $record2 = $this->createMock(InstitutionChangeRecord::class);
        $record2->method('getChangeDate')->willReturn(new \DateTimeImmutable('2023-02-15'));
        
        $record3 = $this->createMock(InstitutionChangeRecord::class);
        $record3->method('getChangeDate')->willReturn(new \DateTimeImmutable('2023-03-15'));

        $allRecords = [$record1, $record2, $record3];
        $startDate = new \DateTimeImmutable('2023-02-01');
        $endDate = new \DateTimeImmutable('2023-02-28');

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('findByInstitution')
            ->with($institution)
            ->willReturn($allRecords);

        $result = $this->service->getChangesByDateRange($institutionId, $startDate, $endDate);

        $this->assertCount(1, $result);
        $resultArray = array_values($result); // 重新索引数组
        $this->assertSame($record2, $resultArray[0]);
    }

    /**
     * 测试获取变更统计信息
     */
    public function test_getChangeStatistics_returnsStatistics(): void
    {
        $institution = $this->createTestInstitution();
        $allRecords = [
            InstitutionChangeRecord::create($institution, '机构信息变更', [], [], [], '原因1', '操作员1', '已审批'),
            InstitutionChangeRecord::create($institution, '资质变更', [], [], [], '原因2', '操作员2', '待审批'),
            InstitutionChangeRecord::create($institution, '机构信息变更', [], [], [], '原因3', '操作员3', '已拒绝'),
            InstitutionChangeRecord::create($institution, '设施变更', [], [], [], '原因4', '操作员4', '已审批'),
        ];

        $this->changeRecordRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($allRecords);

        $result = $this->service->getChangeStatistics();

        $this->assertEquals(4, $result['total']);
        $this->assertEquals(1, $result['pending']);
        $this->assertEquals(2, $result['approved']);
        $this->assertEquals(1, $result['rejected']);
        $this->assertEquals(50.0, $result['approval_rate']);
        $this->assertEquals(2, $result['by_type']['机构信息变更']);
        $this->assertEquals(1, $result['by_type']['资质变更']);
        $this->assertEquals(1, $result['by_type']['设施变更']);
    }

    /**
     * 测试获取变更统计信息 - 空数据
     */
    public function test_getChangeStatistics_withNoData_returnsZeroStatistics(): void
    {
        $this->changeRecordRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->service->getChangeStatistics();

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['pending']);
        $this->assertEquals(0, $result['approved']);
        $this->assertEquals(0, $result['rejected']);
        $this->assertEquals(0, $result['approval_rate']);
        $this->assertEmpty($result['by_type']);
    }

    /**
     * 测试数据验证 - 非数组的变更详情
     */
    public function test_recordChange_withNonArrayChangeDetails_throwsException(): void
    {
        $invalidChangeData = [
            'changeType' => '机构信息变更',
            'changeDetails' => 'invalid string', // 应该是数组
            'beforeData' => [],
            'afterData' => [],
            'changeReason' => '测试原因',
            'changeOperator' => '操作员'
        ];
        $institutionId = 'test-institution-id';

        // 数据验证在机构查找之前
        $this->institutionRepository
            ->expects($this->never())
            ->method('find');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('变更详情必须是数组格式');

        $this->service->recordChange($institutionId, $invalidChangeData);
    }

    /**
     * 测试数据验证 - 非数组的变更前数据
     */
    public function test_recordChange_withNonArrayBeforeData_throwsException(): void
    {
        $invalidChangeData = [
            'changeType' => '机构信息变更',
            'changeDetails' => [],
            'beforeData' => 'invalid string', // 应该是数组
            'afterData' => [],
            'changeReason' => '测试原因',
            'changeOperator' => '操作员'
        ];
        $institutionId = 'test-institution-id';

        // 数据验证在机构查找之前
        $this->institutionRepository
            ->expects($this->never())
            ->method('find');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('变更前数据必须是数组格式');

        $this->service->recordChange($institutionId, $invalidChangeData);
    }

    /**
     * 测试数据验证 - 非数组的变更后数据
     */
    public function test_recordChange_withNonArrayAfterData_throwsException(): void
    {
        $invalidChangeData = [
            'changeType' => '机构信息变更',
            'changeDetails' => [],
            'beforeData' => [],
            'afterData' => 'invalid string', // 应该是数组
            'changeReason' => '测试原因',
            'changeOperator' => '操作员'
        ];
        $institutionId = 'test-institution-id';

        // 数据验证在机构查找之前
        $this->institutionRepository
            ->expects($this->never())
            ->method('find');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('变更后数据必须是数组格式');

        $this->service->recordChange($institutionId, $invalidChangeData);
    }
} 