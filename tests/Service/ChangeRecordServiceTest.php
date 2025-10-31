<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Service\ChangeRecordService;

/**
 * ChangeRecordService 简化集成测试
 *
 * @internal
 */
#[CoversClass(ChangeRecordService::class)]
#[RunTestsInSeparateProcesses]
final class ChangeRecordServiceTest extends AbstractIntegrationTestCase
{
    private ChangeRecordService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(ChangeRecordService::class);
    }

    public function testServiceExists(): void
    {
        self::assertSame(ChangeRecordService::class, $this->service::class);
    }

    public function testRecordChangeWithValidData(): void
    {
        // 创建测试机构
        $institution = new Institution();
        $institution->setInstitutionName('测试机构');
        $institution->setInstitutionCode('TEST001');
        $institution->setInstitutionType('培训机构');
        $institution->setLegalPerson('测试法人');
        $institution->setContactPerson('测试联系人');
        $institution->setContactPhone('13800138000');
        $institution->setContactEmail('test@example.com');
        $institution->setAddress('测试地址');
        $institution->setBusinessScope('培训业务');
        $institution->setRegistrationNumber('REG001');
        $institution->setInstitutionStatus('active');
        $institution->setEstablishDate(new \DateTimeImmutable());

        $em = self::getEntityManager();
        $em->persist($institution);
        $em->flush();

        $changeData = [
            'changeType' => 'update',
            'changeDetails' => ['name' => '新机构名称'],
            'beforeData' => ['name' => '原机构名称'],
            'afterData' => ['name' => '新机构名称'],
            'changeReason' => '测试变更',
            'changeOperator' => '测试操作员',
        ];

        $result = $this->service->recordChange($institution->getId(), $changeData);

        self::assertSame(InstitutionChangeRecord::class, $result::class);
        self::assertSame('update', $result->getChangeType());
        self::assertSame('测试变更', $result->getChangeReason());
        self::assertSame('测试操作员', $result->getChangeOperator());
    }

    public function testValidateChangeDataWithValidData(): void
    {
        $validData = [
            'changeType' => 'update',
            'changeDetails' => ['name' => '新名称'],
            'beforeData' => ['name' => '原名称'],
            'afterData' => ['name' => '新名称'],
            'changeReason' => '测试原因',
            'changeOperator' => '操作员',
        ];

        // 测试验证方法不抛出异常
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateChangeData');
        $method->setAccessible(true);

        // 如果验证通过，不会抛出异常
        $this->expectNotToPerformAssertions();
        $method->invoke($this->service, $validData);
    }

    public function testApproveChange(): void
    {
        // 创建测试机构
        $institution = $this->createTestInstitution();

        $changeData = [
            'changeType' => 'update',
            'changeDetails' => ['name' => '新机构名称'],
            'beforeData' => ['name' => '原机构名称'],
            'afterData' => ['name' => '新机构名称'],
            'changeReason' => '测试变更',
            'changeOperator' => '测试操作员',
        ];

        // 创建变更记录
        $changeRecord = $this->service->recordChange($institution->getId(), $changeData);

        // 审批变更
        $approvedRecord = $this->service->approveChange($changeRecord->getId(), '审批员');

        self::assertSame('已审批', $approvedRecord->getApprovalStatus());
        self::assertSame('审批员', $approvedRecord->getApprover());
        self::assertNotNull($approvedRecord->getApprovalDate());
    }

    public function testRejectChange(): void
    {
        // 创建测试机构
        $institution = $this->createTestInstitution();

        $changeData = [
            'changeType' => 'update',
            'changeDetails' => ['name' => '新机构名称'],
            'beforeData' => ['name' => '原机构名称'],
            'afterData' => ['name' => '新机构名称'],
            'changeReason' => '测试变更',
            'changeOperator' => '测试操作员',
        ];

        // 创建变更记录
        $changeRecord = $this->service->recordChange($institution->getId(), $changeData);

        // 拒绝变更
        $rejectedRecord = $this->service->rejectChange($changeRecord->getId(), '审批员', '拒绝原因');

        self::assertSame('已拒绝', $rejectedRecord->getApprovalStatus());
        self::assertSame('审批员', $rejectedRecord->getApprover());
        self::assertNotNull($rejectedRecord->getApprovalDate());
    }

    public function testBatchApproveChanges(): void
    {
        // 创建测试机构
        $institution = $this->createTestInstitution();

        $changeData = [
            'changeType' => 'update',
            'changeDetails' => ['name' => '新机构名称'],
            'beforeData' => ['name' => '原机构名称'],
            'afterData' => ['name' => '新机构名称'],
            'changeReason' => '测试变更',
            'changeOperator' => '测试操作员',
        ];

        // 创建多个变更记录
        $record1 = $this->service->recordChange($institution->getId(), $changeData);
        $record2 = $this->service->recordChange($institution->getId(), $changeData);

        // 批量审批
        $results = $this->service->batchApproveChanges([$record1->getId(), $record2->getId()], '批量审批员');

        self::assertCount(2, $results);
        self::assertTrue($results[0]['success']);
        self::assertTrue($results[1]['success']);
        self::assertSame($record1->getId(), $results[0]['record_id']);
        self::assertSame($record2->getId(), $results[1]['record_id']);
    }

    public function testBatchRejectChanges(): void
    {
        // 创建测试机构
        $institution = $this->createTestInstitution();

        $changeData = [
            'changeType' => 'update',
            'changeDetails' => ['name' => '新机构名称'],
            'beforeData' => ['name' => '原机构名称'],
            'afterData' => ['name' => '新机构名称'],
            'changeReason' => '测试变更',
            'changeOperator' => '测试操作员',
        ];

        // 创建多个变更记录
        $record1 = $this->service->recordChange($institution->getId(), $changeData);
        $record2 = $this->service->recordChange($institution->getId(), $changeData);

        // 批量拒绝
        $results = $this->service->batchRejectChanges([$record1->getId(), $record2->getId()], '批量审批员', '批量拒绝原因');

        self::assertCount(2, $results);
        self::assertTrue($results[0]['success']);
        self::assertTrue($results[1]['success']);
        self::assertSame($record1->getId(), $results[0]['record_id']);
        self::assertSame($record2->getId(), $results[1]['record_id']);
    }

    public function testGenerateChangeReport(): void
    {
        // 创建测试机构
        $institution = $this->createTestInstitution();

        $changeData = [
            'changeType' => 'update',
            'changeDetails' => ['name' => '新机构名称'],
            'beforeData' => ['name' => '原机构名称'],
            'afterData' => ['name' => '新机构名称'],
            'changeReason' => '测试变更',
            'changeOperator' => '测试操作员',
        ];

        // 创建变更记录
        $this->service->recordChange($institution->getId(), $changeData);

        // 生成报告
        $report = $this->service->generateChangeReport($institution->getId());

        self::assertArrayHasKey('institution', $report);
        self::assertArrayHasKey('summary', $report);
        self::assertArrayHasKey('statistics', $report);
        self::assertArrayHasKey('recent_changes', $report);
        self::assertArrayHasKey('pending_changes', $report);
        self::assertArrayHasKey('generated_at', $report);

        self::assertIsArray($report['institution']);
        self::assertIsArray($report['summary']);

        /** @var array<string, mixed> $institutionData */
        $institutionData = $report['institution'];
        /** @var array<string, mixed> $summaryData */
        $summaryData = $report['summary'];

        self::assertArrayHasKey('id', $institutionData);
        self::assertArrayHasKey('name', $institutionData);
        self::assertArrayHasKey('total_changes', $summaryData);

        self::assertSame($institution->getId(), $institutionData['id']);
        self::assertSame($institution->getInstitutionName(), $institutionData['name']);
        self::assertGreaterThan(0, $summaryData['total_changes']);
    }

    private function createTestInstitution(): Institution
    {
        $institution = new Institution();
        $institution->setInstitutionName('测试机构');
        $institution->setInstitutionCode('TEST001');
        $institution->setInstitutionType('培训机构');
        $institution->setLegalPerson('测试法人');
        $institution->setContactPerson('测试联系人');
        $institution->setContactPhone('13800138000');
        $institution->setContactEmail('test@example.com');
        $institution->setAddress('测试地址');
        $institution->setBusinessScope('培训业务');
        $institution->setRegistrationNumber('REG001');
        $institution->setInstitutionStatus('active');
        $institution->setEstablishDate(new \DateTimeImmutable());

        $em = self::getEntityManager();
        $em->persist($institution);
        $em->flush();

        return $institution;
    }
}
