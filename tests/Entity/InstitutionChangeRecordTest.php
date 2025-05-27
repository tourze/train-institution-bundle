<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;

/**
 * InstitutionChangeRecord 实体单元测试
 */
class InstitutionChangeRecordTest extends TestCase
{
    private Institution $institution;

    protected function setUp(): void
    {
        $this->institution = Institution::create(
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
     * 测试构造函数
     */
    public function test_constructor_setsDefaultValues(): void
    {
        $record = new InstitutionChangeRecord();

        $this->assertNotEmpty($record->getId());
        $this->assertEquals('待审批', $record->getApprovalStatus());
        $this->assertNull($record->getApprover());
        $this->assertNull($record->getApprovalDate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getChangeDate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getCreateTime());
    }

    /**
     * 测试create静态方法
     */
    public function test_create_withValidData(): void
    {
        $changeDetails = [
            'field' => 'institutionName',
            'oldValue' => '旧机构名称',
            'newValue' => '新机构名称'
        ];
        $beforeData = ['institutionName' => '旧机构名称'];
        $afterData = ['institutionName' => '新机构名称'];

        $record = InstitutionChangeRecord::create(
            $this->institution,
            '机构信息变更',
            $changeDetails,
            $beforeData,
            $afterData,
            '业务发展需要',
            '管理员',
            '待审批'
        );

        $this->assertSame($this->institution, $record->getInstitution());
        $this->assertEquals('机构信息变更', $record->getChangeType());
        $this->assertEquals($changeDetails, $record->getChangeDetails());
        $this->assertEquals($beforeData, $record->getBeforeData());
        $this->assertEquals($afterData, $record->getAfterData());
        $this->assertEquals('业务发展需要', $record->getChangeReason());
        $this->assertEquals('管理员', $record->getChangeOperator());
        $this->assertEquals('待审批', $record->getApprovalStatus());
    }

    /**
     * 测试create静态方法使用默认审批状态
     */
    public function test_create_withDefaultApprovalStatus(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '联系方式变更',
            ['field' => 'phone'],
            ['phone' => '13800138000'],
            ['phone' => '13900139000'],
            '联系方式更新',
            '系统管理员'
        );

        $this->assertEquals('待审批', $record->getApprovalStatus());
    }

    /**
     * 测试获取机构
     */
    public function test_getInstitution_returnsCorrectInstitution(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '测试变更',
            [],
            [],
            [],
            '测试原因',
            '测试操作员'
        );

        $this->assertSame($this->institution, $record->getInstitution());
    }

    /**
     * 测试获取变更类型
     */
    public function test_getChangeType_returnsCorrectType(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '资质变更',
            [],
            [],
            [],
            '资质到期续期',
            '资质管理员'
        );

        $this->assertEquals('资质变更', $record->getChangeType());
    }

    /**
     * 测试获取变更详情
     */
    public function test_getChangeDetails_returnsCorrectDetails(): void
    {
        $changeDetails = [
            'qualificationId' => 'QUAL001',
            'action' => 'renew',
            'newValidTo' => '2025-12-31'
        ];

        $record = InstitutionChangeRecord::create(
            $this->institution,
            '资质续期',
            $changeDetails,
            [],
            [],
            '资质即将到期',
            '资质管理员'
        );

        $this->assertEquals($changeDetails, $record->getChangeDetails());
    }

    /**
     * 测试获取变更前数据
     */
    public function test_getBeforeData_returnsCorrectData(): void
    {
        $beforeData = [
            'institutionName' => '原机构名称',
            'legalRepresentative' => '原法人代表'
        ];

        $record = InstitutionChangeRecord::create(
            $this->institution,
            '基本信息变更',
            [],
            $beforeData,
            [],
            '信息更新',
            '管理员'
        );

        $this->assertEquals($beforeData, $record->getBeforeData());
    }

    /**
     * 测试获取变更后数据
     */
    public function test_getAfterData_returnsCorrectData(): void
    {
        $afterData = [
            'institutionName' => '新机构名称',
            'legalRepresentative' => '新法人代表'
        ];

        $record = InstitutionChangeRecord::create(
            $this->institution,
            '基本信息变更',
            [],
            [],
            $afterData,
            '信息更新',
            '管理员'
        );

        $this->assertEquals($afterData, $record->getAfterData());
    }

    /**
     * 测试获取变更原因
     */
    public function test_getChangeReason_returnsCorrectReason(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '地址变更',
            [],
            [],
            [],
            '办公地点搬迁',
            '行政管理员'
        );

        $this->assertEquals('办公地点搬迁', $record->getChangeReason());
    }

    /**
     * 测试获取变更日期
     */
    public function test_getChangeDate_returnsDateTimeImmutable(): void
    {
        $record = new InstitutionChangeRecord();

        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getChangeDate());
    }

    /**
     * 测试获取变更操作员
     */
    public function test_getChangeOperator_returnsCorrectOperator(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '设施变更',
            [],
            [],
            [],
            '设施升级',
            '设施管理员'
        );

        $this->assertEquals('设施管理员', $record->getChangeOperator());
    }

    /**
     * 测试获取审批状态
     */
    public function test_getApprovalStatus_returnsCorrectStatus(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '测试变更',
            [],
            [],
            [],
            '测试原因',
            '测试操作员',
            '已审批'
        );

        $this->assertEquals('已审批', $record->getApprovalStatus());
    }

    /**
     * 测试获取审批人 - 初始为null
     */
    public function test_getApprover_initiallyNull(): void
    {
        $record = new InstitutionChangeRecord();

        $this->assertNull($record->getApprover());
    }

    /**
     * 测试获取审批日期 - 初始为null
     */
    public function test_getApprovalDate_initiallyNull(): void
    {
        $record = new InstitutionChangeRecord();

        $this->assertNull($record->getApprovalDate());
    }

    /**
     * 测试获取创建时间
     */
    public function test_getCreateTime_returnsDateTimeImmutable(): void
    {
        $record = new InstitutionChangeRecord();

        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getCreateTime());
    }

    /**
     * 测试审批通过
     */
    public function test_approve_setsApprovalData(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '测试变更',
            [],
            [],
            [],
            '测试原因',
            '测试操作员'
        );

        $result = $record->approve('审批主管');

        $this->assertSame($record, $result);
        $this->assertEquals('已审批', $record->getApprovalStatus());
        $this->assertEquals('审批主管', $record->getApprover());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getApprovalDate());
    }

    /**
     * 测试审批拒绝
     */
    public function test_reject_setsRejectionData(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '测试变更',
            [],
            [],
            [],
            '测试原因',
            '测试操作员'
        );

        $result = $record->reject('审批主管');

        $this->assertSame($record, $result);
        $this->assertEquals('已拒绝', $record->getApprovalStatus());
        $this->assertEquals('审批主管', $record->getApprover());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getApprovalDate());
    }

    /**
     * 测试审批时间的准确性
     */
    public function test_approve_setsCurrentTime(): void
    {
        $record = new InstitutionChangeRecord();
        $beforeApproval = new \DateTimeImmutable();
        
        $record->approve('审批人');
        
        $afterApproval = new \DateTimeImmutable();
        $approvalDate = $record->getApprovalDate();

        $this->assertGreaterThanOrEqual($beforeApproval, $approvalDate);
        $this->assertLessThanOrEqual($afterApproval, $approvalDate);
    }

    /**
     * 测试拒绝时间的准确性
     */
    public function test_reject_setsCurrentTime(): void
    {
        $record = new InstitutionChangeRecord();
        $beforeRejection = new \DateTimeImmutable();
        
        $record->reject('审批人');
        
        $afterRejection = new \DateTimeImmutable();
        $approvalDate = $record->getApprovalDate();

        $this->assertGreaterThanOrEqual($beforeRejection, $approvalDate);
        $this->assertLessThanOrEqual($afterRejection, $approvalDate);
    }

    /**
     * 测试复杂变更详情
     */
    public function test_create_withComplexChangeDetails(): void
    {
        $changeDetails = [
            'type' => 'facility_addition',
            'facility' => [
                'name' => '新实训室',
                'type' => '实训场地',
                'area' => 120.5,
                'capacity' => 30
            ],
            'equipment' => [
                ['name' => '电工实训台', 'quantity' => 10],
                ['name' => '万用表', 'quantity' => 30]
            ]
        ];

        $record = InstitutionChangeRecord::create(
            $this->institution,
            '设施新增',
            $changeDetails,
            ['facilityCount' => 5],
            ['facilityCount' => 6],
            '扩大培训规模',
            '设施管理员'
        );

        $this->assertEquals($changeDetails, $record->getChangeDetails());
        $this->assertEquals(['facilityCount' => 5], $record->getBeforeData());
        $this->assertEquals(['facilityCount' => 6], $record->getAfterData());
    }

    /**
     * 测试空数组的变更数据
     */
    public function test_create_withEmptyArrays(): void
    {
        $record = InstitutionChangeRecord::create(
            $this->institution,
            '状态变更',
            [],
            [],
            [],
            '系统维护',
            '系统管理员'
        );

        $this->assertEquals([], $record->getChangeDetails());
        $this->assertEquals([], $record->getBeforeData());
        $this->assertEquals([], $record->getAfterData());
    }

    /**
     * 测试长文本变更原因
     */
    public function test_create_withLongChangeReason(): void
    {
        $longReason = str_repeat('这是一个很长的变更原因，用于测试系统对长文本的处理能力。', 10);

        $record = InstitutionChangeRecord::create(
            $this->institution,
            '重大变更',
            [],
            [],
            [],
            $longReason,
            '高级管理员'
        );

        $this->assertEquals($longReason, $record->getChangeReason());
    }

    /**
     * 测试特殊字符的变更操作员
     */
    public function test_create_withSpecialCharacterOperator(): void
    {
        $specialOperator = '张三@系统管理员#2023';

        $record = InstitutionChangeRecord::create(
            $this->institution,
            '测试变更',
            [],
            [],
            [],
            '测试特殊字符',
            $specialOperator
        );

        $this->assertEquals($specialOperator, $record->getChangeOperator());
    }

    /**
     * 测试审批状态的各种值
     */
    public function test_create_withDifferentApprovalStatuses(): void
    {
        $statuses = ['待审批', '已审批', '已拒绝', '审批中'];

        foreach ($statuses as $status) {
            $record = InstitutionChangeRecord::create(
                $this->institution,
                '测试变更',
                [],
                [],
                [],
                '测试不同状态',
                '测试操作员',
                $status
            );

            $this->assertEquals($status, $record->getApprovalStatus());
        }
    }

    /**
     * 测试多次审批操作
     */
    public function test_multipleApprovalOperations(): void
    {
        $record = new InstitutionChangeRecord();

        // 第一次审批
        $record->approve('审批人A');
        $firstApprover = $record->getApprover();
        $firstApprovalDate = $record->getApprovalDate();

        usleep(1000);

        // 第二次审批（覆盖前一次）
        $record->approve('审批人B');
        $secondApprover = $record->getApprover();
        $secondApprovalDate = $record->getApprovalDate();

        $this->assertEquals('审批人B', $secondApprover);
        $this->assertNotEquals($firstApprover, $secondApprover);
        $this->assertGreaterThan($firstApprovalDate, $secondApprovalDate);
    }

    /**
     * 测试审批后再拒绝
     */
    public function test_approveAndThenReject(): void
    {
        $record = new InstitutionChangeRecord();

        $record->approve('审批人A');
        $this->assertEquals('已审批', $record->getApprovalStatus());

        $record->reject('审批人B');
        $this->assertEquals('已拒绝', $record->getApprovalStatus());
        $this->assertEquals('审批人B', $record->getApprover());
    }
} 