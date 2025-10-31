<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;
use Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository;

/**
 * InstitutionChangeRecordRepository 单元测试
 *
 * @internal
 */
#[CoversClass(InstitutionChangeRecordRepository::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionChangeRecordRepositoryTest extends AbstractRepositoryTestCase
{
    public function testRepositoryCanBeInjected(): void
    {
        $repository = self::getContainer()->get(InstitutionChangeRecordRepository::class);
        self::assertInstanceOf(InstitutionChangeRecordRepository::class, $repository);
    }

    public function testCanSaveAndFindEntity(): void
    {
        $entityManager = self::getEntityManager();

        $institution = Institution::create(
            '测试机构',
            'TEST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456'
        );

        $entityManager->persist($institution);
        $entityManager->flush();

        $changeRecord = InstitutionChangeRecord::create(
            $institution,
            '测试变更',
            ['field' => 'test'],
            ['test' => 'before'],
            ['test' => 'after'],
            '测试原因',
            '测试操作员',
            '待审批'
        );

        $entityManager->persist($changeRecord);
        $entityManager->flush();

        /** @var InstitutionChangeRecordRepository $repository */
        $repository = self::getContainer()->get(InstitutionChangeRecordRepository::class);
        $foundRecord = $repository->find($changeRecord->getId());

        self::assertInstanceOf(InstitutionChangeRecord::class, $foundRecord);
        self::assertSame('测试变更', $foundRecord->getChangeType());
        self::assertSame('测试原因', $foundRecord->getChangeReason());
    }

    protected function onSetUp(): void
    {
        // 集成测试的初始化逻辑
    }

    protected function createNewEntity(): object
    {
        $institution = Institution::create(
            '测试机构 ' . uniqid(),
            'TEST' . uniqid(),
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456'
        );

        self::getEntityManager()->persist($institution);
        self::getEntityManager()->flush();

        return InstitutionChangeRecord::create(
            $institution,
            '测试变更',
            ['field' => 'test'],
            ['test' => 'before'],
            ['test' => 'after'],
            '测试原因',
            '测试操作员',
            '待审批'
        );
    }

    protected function getRepository(): InstitutionChangeRecordRepository
    {
        $repository = self::getContainer()->get(InstitutionChangeRecordRepository::class);
        self::assertInstanceOf(InstitutionChangeRecordRepository::class, $repository);

        return $repository;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    private function clearExistingData($entityManager): void
    {
        // 清理变更记录
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord')->execute();
        // 清理机构
        $entityManager->createQuery('DELETE FROM Tourze\TrainInstitutionBundle\Entity\Institution')->execute();
        $entityManager->flush();
    }

    public function testFindByChangeType(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建测试机构
        $institution = Institution::create(
            '测试机构',
            'TEST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456'
        );
        $entityManager->persist($institution);

        // 创建不同类型的变更记录
        $changeRecord1 = InstitutionChangeRecord::create(
            $institution,
            '名称变更',
            ['name' => 'changed'],
            ['name' => '旧名称'],
            ['name' => '新名称'],
            '更新机构名称',
            '操作员1',
            '已审批'
        );
        $changeRecord2 = InstitutionChangeRecord::create(
            $institution,
            '地址变更',
            ['address' => 'changed'],
            ['address' => '旧地址'],
            ['address' => '新地址'],
            '更新机构地址',
            '操作员2',
            '待审批'
        );
        $changeRecord3 = InstitutionChangeRecord::create(
            $institution,
            '名称变更',
            ['name' => 'changed_again'],
            ['name' => '另一个旧名称'],
            ['name' => '另一个新名称'],
            '再次更新机构名称',
            '操作员3',
            '已审批'
        );

        $entityManager->persist($changeRecord1);
        $entityManager->persist($changeRecord2);
        $entityManager->persist($changeRecord3);
        $entityManager->flush();

        // 测试按变更类型查找
        $nameChangeRecords = $repository->findByChangeType('名称变更');
        self::assertCount(2, $nameChangeRecords);
        self::assertSame('名称变更', $nameChangeRecords[0]->getChangeType());
        self::assertSame('名称变更', $nameChangeRecords[1]->getChangeType());

        $addressChangeRecords = $repository->findByChangeType('地址变更');
        self::assertCount(1, $addressChangeRecords);
        self::assertSame('地址变更', $addressChangeRecords[0]->getChangeType());

        // 测试不存在的变更类型
        $nonExistentRecords = $repository->findByChangeType('不存在的类型');
        self::assertEmpty($nonExistentRecords);
    }

    public function testFindByInstitution(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建两个测试机构
        $institution1 = Institution::create(
            '测试机构1',
            'TEST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test1@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456'
        );
        $institution2 = Institution::create(
            '测试机构2',
            'TEST002',
            '企业培训机构',
            '王五',
            '赵六',
            '13900139000',
            'test2@example.com',
            '上海市浦东区',
            '职业技能培训',
            new \DateTimeImmutable('2020-02-01'),
            'REG234567'
        );
        $entityManager->persist($institution1);
        $entityManager->persist($institution2);

        // 为第一个机构创建变更记录
        $changeRecord1 = InstitutionChangeRecord::create(
            $institution1,
            '名称变更',
            ['name' => 'changed'],
            ['name' => '旧名称'],
            ['name' => '新名称'],
            '更新机构名称',
            '操作员1',
            '已审批'
        );
        $changeRecord2 = InstitutionChangeRecord::create(
            $institution1,
            '地址变更',
            ['address' => 'changed'],
            ['address' => '旧地址'],
            ['address' => '新地址'],
            '更新机构地址',
            '操作员1',
            '待审批'
        );

        // 为第二个机构创建变更记录
        $changeRecord3 = InstitutionChangeRecord::create(
            $institution2,
            '联系人变更',
            ['contact' => 'changed'],
            ['contact' => '旧联系人'],
            ['contact' => '新联系人'],
            '更新联系人',
            '操作员2',
            '已审批'
        );

        $entityManager->persist($changeRecord1);
        $entityManager->persist($changeRecord2);
        $entityManager->persist($changeRecord3);
        $entityManager->flush();

        // 测试按机构查找变更记录
        $institution1Records = $repository->findByInstitution($institution1);
        self::assertCount(2, $institution1Records);
        foreach ($institution1Records as $record) {
            self::assertSame($institution1->getId(), $record->getInstitution()->getId());
        }

        $institution2Records = $repository->findByInstitution($institution2);
        self::assertCount(1, $institution2Records);
        self::assertSame($institution2->getId(), $institution2Records[0]->getInstitution()->getId());
    }

    public function testFindPendingApproval(): void
    {
        $entityManager = self::getEntityManager();
        $repository = $this->getRepository();

        // 清理现有数据
        $this->clearExistingData($entityManager);

        // 创建测试机构
        $institution = Institution::create(
            '测试机构',
            'TEST001',
            '企业培训机构',
            '张三',
            '李四',
            '13800138000',
            'test@example.com',
            '北京市朝阳区',
            '安全生产培训',
            new \DateTimeImmutable('2020-01-01'),
            'REG123456'
        );
        $entityManager->persist($institution);

        // 创建不同状态的变更记录
        $pendingRecord1 = InstitutionChangeRecord::create(
            $institution,
            '名称变更',
            ['name' => 'changed'],
            ['name' => '旧名称1'],
            ['name' => '新名称1'],
            '更新机构名称1',
            '操作员1',
            '待审批'
        );
        $approvedRecord = InstitutionChangeRecord::create(
            $institution,
            '地址变更',
            ['address' => 'changed'],
            ['address' => '旧地址'],
            ['address' => '新地址'],
            '更新机构地址',
            '操作员2',
            '已审批'
        );
        $pendingRecord2 = InstitutionChangeRecord::create(
            $institution,
            '联系人变更',
            ['contact' => 'changed'],
            ['contact' => '旧联系人'],
            ['contact' => '新联系人'],
            '更新联系人',
            '操作员3',
            '待审批'
        );
        $rejectedRecord = InstitutionChangeRecord::create(
            $institution,
            '资质变更',
            ['qualification' => 'changed'],
            ['qualification' => '旧资质'],
            ['qualification' => '新资质'],
            '更新资质信息',
            '操作员4',
            '已拒绝'
        );

        $entityManager->persist($pendingRecord1);
        $entityManager->persist($approvedRecord);
        $entityManager->persist($pendingRecord2);
        $entityManager->persist($rejectedRecord);
        $entityManager->flush();

        // 测试查找待审批的记录
        $pendingRecords = $repository->findPendingApproval();
        self::assertCount(2, $pendingRecords);
        foreach ($pendingRecords as $record) {
            self::assertSame('待审批', $record->getApprovalStatus());
        }

        // 验证返回结果按创建时间升序排列
        self::assertLessThanOrEqual(
            $pendingRecords[1]->getCreateTime(),
            $pendingRecords[0]->getCreateTime()
        );
    }
}
