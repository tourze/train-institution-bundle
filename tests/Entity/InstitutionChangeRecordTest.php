<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;

/**
 * InstitutionChangeRecord 实体单元测试
 *
 * @internal
 */
#[CoversClass(InstitutionChangeRecord::class)]
final class InstitutionChangeRecordTest extends AbstractEntityTestCase
{
    private Institution $institution;

    protected function createEntity(): object
    {
        return new InstitutionChangeRecord();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'changeType' => ['changeType', '测试变更类型'],
            'changeDetails' => ['changeDetails', ['key' => 'value']],
            'beforeData' => ['beforeData', ['key' => 'before_value']],
            'afterData' => ['afterData', ['key' => 'after_value']],
            'changeReason' => ['changeReason', '测试变更原因'],
            'changeOperator' => ['changeOperator', '测试操作员'],
            'approvalStatus' => ['approvalStatus', '已审批'],
            'approver' => ['approver', '测试审批人'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
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
    public function testConstructorSetsDefaultValues(): void
    {
        $record = new InstitutionChangeRecord();

        self::assertNotEmpty($record->getId());
        self::assertEquals('待审批', $record->getApprovalStatus());
        self::assertNull($record->getApprover());
        self::assertNull($record->getApprovalDate());
        // 类型系统保证返回非null DateTimeImmutable，验证时间合理性
        self::assertLessThanOrEqual(new \DateTimeImmutable(), $record->getChangeDate());
        self::assertLessThanOrEqual(new \DateTimeImmutable(), $record->getCreateTime());
        self::assertEquals($record->getChangeDate()->format('Y-m-d'), $record->getCreateTime()->format('Y-m-d'));
    }

    /**
     * 测试create静态方法
     */
    public function testCreateWithValidData(): void
    {
        $changeDetails = [
            'field' => 'institutionName',
            'oldValue' => '旧机构名称',
            'newValue' => '新机构名称',
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

        self::assertSame($this->institution, $record->getInstitution());
        self::assertEquals('机构信息变更', $record->getChangeType());
        self::assertEquals($changeDetails, $record->getChangeDetails());
        self::assertEquals($beforeData, $record->getBeforeData());
        self::assertEquals($afterData, $record->getAfterData());
        self::assertEquals('业务发展需要', $record->getChangeReason());
        self::assertEquals('管理员', $record->getChangeOperator());
        self::assertEquals('待审批', $record->getApprovalStatus());
    }

    /**
     * 测试create静态方法使用默认审批状态
     */
    public function testCreateWithDefaultApprovalStatus(): void
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

        self::assertEquals('待审批', $record->getApprovalStatus());
    }

    /**
     * 测试获取机构
     */
    public function testGetInstitutionReturnsCorrectInstitution(): void
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

        self::assertSame($this->institution, $record->getInstitution());
    }

    /**
     * 测试获取变更类型
     */
    public function testGetChangeTypeReturnsCorrectType(): void
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

        self::assertEquals('资质变更', $record->getChangeType());
    }

    /**
     * 测试获取变更详情
     */
    public function testGetChangeDetailsReturnsCorrectDetails(): void
    {
        $changeDetails = [
            'qualificationId' => 'QUAL001',
            'action' => 'renew',
            'newValidTo' => '2025-12-31',
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

        self::assertEquals($changeDetails, $record->getChangeDetails());
    }

    /**
     * 测试获取变更前数据
     */
    public function testGetBeforeDataReturnsCorrectData(): void
    {
        $beforeData = [
            'institutionName' => '原机构名称',
            'legalRepresentative' => '原法人代表',
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

        self::assertEquals($beforeData, $record->getBeforeData());
    }

    /**
     * 测试获取变更后数据
     */
    public function testGetAfterDataReturnsCorrectData(): void
    {
        $afterData = [
            'institutionName' => '新机构名称',
            'legalRepresentative' => '新法人代表',
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

        self::assertEquals($afterData, $record->getAfterData());
    }

    /**
     * 测试获取变更原因
     */
    public function testGetChangeReasonReturnsCorrectReason(): void
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

        self::assertEquals('办公地点搬迁', $record->getChangeReason());
    }

    /**
     * 测试获取变更日期
     */
    public function testGetChangeDateReturnsDateTimeImmutable(): void
    {
        $record = new InstitutionChangeRecord();

        // 类型系统保证返回DateTimeImmutable，验证默认值为当前时间
        $now = new \DateTimeImmutable();
        self::assertEquals($now->format('Y-m-d'), $record->getChangeDate()->format('Y-m-d'));
        self::assertLessThanOrEqual($now, $record->getChangeDate());
    }

    /**
     * 测试获取变更操作员
     */
    public function testGetChangeOperatorReturnsCorrectOperator(): void
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

        self::assertEquals('设施管理员', $record->getChangeOperator());
    }

    /**
     * 测试获取审批状态
     */
    public function testGetApprovalStatusReturnsCorrectStatus(): void
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

        self::assertEquals('已审批', $record->getApprovalStatus());
    }

    /**
     * 测试获取审批人 - 初始为null
     */
    public function testGetApproverInitiallyNull(): void
    {
        $record = new InstitutionChangeRecord();

        self::assertNull($record->getApprover());
    }

    /**
     * 测试获取审批日期 - 初始为null
     */
    public function testGetApprovalDateInitiallyNull(): void
    {
        $record = new InstitutionChangeRecord();

        self::assertNull($record->getApprovalDate());
    }

    /**
     * 测试获取创建时间
     */
    public function testGetCreateTimeReturnsDateTimeImmutable(): void
    {
        $record = new InstitutionChangeRecord();

        // 类型系统保证返回DateTimeImmutable，验证默认值为当前时间
        $now = new \DateTimeImmutable();
        self::assertEquals($now->format('Y-m-d'), $record->getCreateTime()->format('Y-m-d'));
        self::assertLessThanOrEqual($now, $record->getCreateTime());
    }

    /**
     * 测试审批通过
     */
    public function testApproveSetsApprovalData(): void
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

        self::assertSame($record, $result);
        self::assertEquals('已审批', $record->getApprovalStatus());
        self::assertEquals('审批主管', $record->getApprover());
        self::assertInstanceOf(\DateTimeImmutable::class, $record->getApprovalDate());
    }

    /**
     * 测试审批拒绝
     */
    public function testRejectSetsRejectionData(): void
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

        self::assertSame($record, $result);
        self::assertEquals('已拒绝', $record->getApprovalStatus());
        self::assertEquals('审批主管', $record->getApprover());
        self::assertInstanceOf(\DateTimeImmutable::class, $record->getApprovalDate());
    }

    /**
     * 测试审批时间的准确性
     */
    public function testApproveSetsCurrentTime(): void
    {
        $record = new InstitutionChangeRecord();
        $beforeApproval = new \DateTimeImmutable();

        $record->approve('审批人');

        $afterApproval = new \DateTimeImmutable();
        $approvalDate = $record->getApprovalDate();

        self::assertGreaterThanOrEqual($beforeApproval, $approvalDate);
        self::assertLessThanOrEqual($afterApproval, $approvalDate);
    }

    /**
     * 测试拒绝时间的准确性
     */
    public function testRejectSetsCurrentTime(): void
    {
        $record = new InstitutionChangeRecord();
        $beforeRejection = new \DateTimeImmutable();

        $record->reject('审批人');

        $afterRejection = new \DateTimeImmutable();
        $approvalDate = $record->getApprovalDate();

        self::assertGreaterThanOrEqual($beforeRejection, $approvalDate);
        self::assertLessThanOrEqual($afterRejection, $approvalDate);
    }

    /**
     * 测试复杂变更详情
     */
    public function testCreateWithComplexChangeDetails(): void
    {
        $changeDetails = [
            'type' => 'facility_addition',
            'facility' => [
                'name' => '新实训室',
                'type' => '实训场地',
                'area' => 120.5,
                'capacity' => 30,
            ],
            'equipment' => [
                ['name' => '电工实训台', 'quantity' => 10],
                ['name' => '万用表', 'quantity' => 30],
            ],
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

        self::assertEquals($changeDetails, $record->getChangeDetails());
        self::assertEquals(['facilityCount' => 5], $record->getBeforeData());
        self::assertEquals(['facilityCount' => 6], $record->getAfterData());
    }

    /**
     * 测试空数组的变更数据
     */
    public function testCreateWithEmptyArrays(): void
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

        self::assertEquals([], $record->getChangeDetails());
        self::assertEquals([], $record->getBeforeData());
        self::assertEquals([], $record->getAfterData());
    }

    /**
     * 测试长文本变更原因
     */
    public function testCreateWithLongChangeReason(): void
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

        self::assertEquals($longReason, $record->getChangeReason());
    }

    /**
     * 测试特殊字符的变更操作员
     */
    public function testCreateWithSpecialCharacterOperator(): void
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

        self::assertEquals($specialOperator, $record->getChangeOperator());
    }

    /**
     * 测试审批状态的各种值
     */
    public function testCreateWithDifferentApprovalStatuses(): void
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

            self::assertEquals($status, $record->getApprovalStatus());
        }
    }

    /**
     * 测试多次审批操作
     */
    public function testMultipleApprovalOperations(): void
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

        self::assertEquals('审批人B', $secondApprover);
        self::assertNotEquals($firstApprover, $secondApprover);
        self::assertGreaterThan($firstApprovalDate, $secondApprovalDate);
    }

    /**
     * 测试审批后再拒绝
     */
    public function testApproveAndThenReject(): void
    {
        $record = new InstitutionChangeRecord();

        $record->approve('审批人A');
        self::assertEquals('已审批', $record->getApprovalStatus());

        $record->reject('审批人B');
        self::assertEquals('已拒绝', $record->getApprovalStatus());
        self::assertEquals('审批人B', $record->getApprover());
    }
}
