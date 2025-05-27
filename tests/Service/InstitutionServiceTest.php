<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Repository\InstitutionChangeRecordRepository;
use Tourze\TrainInstitutionBundle\Repository\InstitutionRepository;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

/**
 * InstitutionService 单元测试
 */
class InstitutionServiceTest extends TestCase
{
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&InstitutionRepository $institutionRepository;
    private MockObject&InstitutionChangeRecordRepository $changeRecordRepository;
    private InstitutionService $institutionService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->institutionRepository = $this->createMock(InstitutionRepository::class);
        $this->changeRecordRepository = $this->createMock(InstitutionChangeRecordRepository::class);

        $this->institutionService = new InstitutionService(
            $this->entityManager,
            $this->institutionRepository,
            $this->changeRecordRepository
        );
    }

    /**
     * 测试创建机构
     */
    public function testCreateInstitution(): void
    {
        $institutionData = [
            'institutionName' => '测试培训机构',
            'institutionCode' => 'TEST001',
            'institutionType' => '企业培训机构',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '北京市朝阳区测试路123号',
            'businessScope' => '安全生产培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
            'registrationNumber' => 'REG123456789',
        ];

        // 模拟检查机构代码唯一性
        $this->institutionRepository
            ->expects($this->once())
            ->method('isInstitutionCodeExists')
            ->with('TEST001')
            ->willReturn(false);

        // 模拟检查注册号唯一性
        $this->institutionRepository
            ->expects($this->once())
            ->method('isRegistrationNumberExists')
            ->with('REG123456789')
            ->willReturn(false);

        // 模拟保存操作
        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $institution = $this->institutionService->createInstitution($institutionData);

        $this->assertInstanceOf(Institution::class, $institution);
        $this->assertEquals('测试培训机构', $institution->getInstitutionName());
        $this->assertEquals('TEST001', $institution->getInstitutionCode());
        $this->assertEquals('待审核', $institution->getInstitutionStatus());
    }

    /**
     * 测试创建机构时代码重复
     */
    public function testCreateInstitutionWithDuplicateCode(): void
    {
        $institutionData = [
            'institutionName' => '测试培训机构',
            'institutionCode' => 'TEST001',
            'institutionType' => '企业培训机构',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '北京市朝阳区测试路123号',
            'businessScope' => '安全生产培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
            'registrationNumber' => 'REG123456789',
        ];

        // 模拟机构代码已存在
        $this->institutionRepository
            ->expects($this->once())
            ->method('isInstitutionCodeExists')
            ->with('TEST001')
            ->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('机构代码已存在');

        $this->institutionService->createInstitution($institutionData);
    }

    /**
     * 测试数据验证
     */
    public function testValidateInstitutionData(): void
    {
        // 测试有效数据
        $validData = [
            'institutionName' => '测试培训机构',
            'institutionCode' => 'TEST001',
            'institutionType' => '企业培训机构',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '北京市朝阳区测试路123号',
            'businessScope' => '安全生产培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
            'registrationNumber' => 'REG123456789',
        ];

        $errors = $this->institutionService->validateInstitutionData($validData);
        $this->assertEmpty($errors);

        // 测试无效数据
        $invalidData = [
            'institutionName' => '', // 空名称
            'institutionCode' => '', // 空代码
            'contactPhone' => '123', // 无效电话
            'contactEmail' => 'invalid-email', // 无效邮箱
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/机构名称不能为空/');
        
        $this->institutionService->validateInstitutionData($invalidData);
    }

    /**
     * 测试获取机构
     */
    public function testGetInstitutionById(): void
    {
        $institutionId = 'test-id';
        $institution = $this->createMock(Institution::class);

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $result = $this->institutionService->getInstitutionById($institutionId);
        $this->assertSame($institution, $result);
    }

    /**
     * 测试按状态获取机构
     */
    public function testGetInstitutionsByStatus(): void
    {
        $status = '正常运营';
        $institutions = [
            $this->createMock(Institution::class),
            $this->createMock(Institution::class),
        ];

        $this->institutionRepository
            ->expects($this->once())
            ->method('findByStatus')
            ->with($status)
            ->willReturn($institutions);

        $result = $this->institutionService->getInstitutionsByStatus($status);
        $this->assertSame($institutions, $result);
    }

    /**
     * 测试更新机构状态
     */
    public function testChangeInstitutionStatus(): void
    {
        $institutionId = 'test-id';
        $newStatus = '暂停运营';
        $reason = '违规操作';

        $institution = $this->createMock(Institution::class);
        $institution->expects($this->once())
            ->method('setInstitutionStatus')
            ->with($newStatus);

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn($institution);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->institutionService->changeInstitutionStatus($institutionId, $newStatus, $reason);
        $this->assertSame($institution, $result);
    }

    /**
     * 测试机构不存在的情况
     */
    public function testGetNonExistentInstitution(): void
    {
        $institutionId = 'non-existent-id';

        $this->institutionRepository
            ->expects($this->once())
            ->method('find')
            ->with($institutionId)
            ->willReturn(null);

        $result = $this->institutionService->getInstitutionById($institutionId);
        $this->assertNull($result);
    }
} 