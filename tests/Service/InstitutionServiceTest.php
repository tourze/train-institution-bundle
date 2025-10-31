<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainInstitutionBundle\Exception\DuplicateInstitutionCodeException;
use Tourze\TrainInstitutionBundle\Exception\DuplicateRegistrationNumberException;
use Tourze\TrainInstitutionBundle\Exception\InstitutionNotFoundException;
use Tourze\TrainInstitutionBundle\Exception\InvalidInstitutionDataException;
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

/**
 * InstitutionService 集成测试
 *
 * @internal
 */
#[CoversClass(InstitutionService::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionServiceTest extends AbstractIntegrationTestCase
{
    private InstitutionService $service;

    public function testServiceExists(): void
    {
        self::assertSame(InstitutionService::class, $this->service::class);
    }

    public function testCreateInstitution(): void
    {
        $institutionData = $this->getUniqueInstitutionData('CREATE');

        $result = $this->service->createInstitution($institutionData);

        self::assertSame($institutionData['institutionName'], $result->getInstitutionName());
        self::assertSame($institutionData['institutionCode'], $result->getInstitutionCode());
    }

    public function testCreateInstitutionWithDuplicateCode(): void
    {
        $institutionData = $this->getUniqueInstitutionData('DUP_CODE');

        // 先创建一个机构
        $this->service->createInstitution($institutionData);

        // 尝试创建相同机构代码的机构
        $this->expectException(DuplicateInstitutionCodeException::class);
        $this->service->createInstitution($institutionData);
    }

    public function testCreateInstitutionWithDuplicateRegistrationNumber(): void
    {
        $institutionData = $this->getUniqueInstitutionData('DUP_REG');

        // 先创建一个机构
        $this->service->createInstitution($institutionData);

        // 修改机构代码但保持相同注册号
        $duplicateData = $institutionData;
        $duplicateData['institutionCode'] = 'DUP_REG_2';

        $this->expectException(DuplicateRegistrationNumberException::class);
        $this->service->createInstitution($duplicateData);
    }

    /**
     * @param array<string, mixed> $institutionData
     */
    #[DataProvider('invalidInstitutionDataProvider')]
    public function testCreateInstitutionWithInvalidData(array $institutionData): void
    {
        $this->expectException(InvalidInstitutionDataException::class);

        $this->service->createInstitution($institutionData);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function invalidInstitutionDataProvider(): array
    {
        return [
            'missing institutionName' => [
                [
                    'institutionCode' => 'INST001',
                    'institutionType' => '职业培训机构',
                    'legalPerson' => '张三',
                    'contactPerson' => '李四',
                    'contactPhone' => '13800138000',
                    'contactEmail' => 'test@example.com',
                    'address' => '北京市',
                    'businessScope' => '职业培训',
                    'establishDate' => new \DateTimeImmutable(),
                    'registrationNumber' => '123456789',
                ],
            ],
            'invalid email' => [
                [
                    'institutionName' => '测试机构',
                    'institutionCode' => 'INST001',
                    'institutionType' => '职业培训机构',
                    'legalPerson' => '张三',
                    'contactPerson' => '李四',
                    'contactPhone' => '13800138000',
                    'contactEmail' => 'invalid-email',
                    'address' => '北京市',
                    'businessScope' => '职业培训',
                    'establishDate' => new \DateTimeImmutable(),
                    'registrationNumber' => '123456789',
                ],
            ],
            'invalid phone' => [
                [
                    'institutionName' => '测试机构',
                    'institutionCode' => 'INST001',
                    'institutionType' => '职业培训机构',
                    'legalPerson' => '张三',
                    'contactPerson' => '李四',
                    'contactPhone' => '123456', // 无效电话格式
                    'contactEmail' => 'test@example.com',
                    'address' => '北京市',
                    'businessScope' => '职业培训',
                    'establishDate' => new \DateTimeImmutable(),
                    'registrationNumber' => '123456789',
                ],
            ],
        ];
    }

    public function testUpdateInstitution(): void
    {
        // 先创建一个机构
        $institutionData = $this->getUniqueInstitutionData('UPDATE');
        $institution = $this->service->createInstitution($institutionData);
        $institutionId = $institution->getId();

        $updateData = ['institutionName' => '更新后的机构名称'];

        $result = $this->service->updateInstitution($institutionId, $updateData);

        self::assertSame($institution, $result);
        self::assertSame('更新后的机构名称', $result->getInstitutionName());
    }

    public function testUpdateInstitutionNotFound(): void
    {
        $institutionId = 'invalid-institution-id';
        $updateData = ['institutionName' => '新名称'];

        $this->expectException(InstitutionNotFoundException::class);

        $this->service->updateInstitution($institutionId, $updateData);
    }

    public function testValidateInstitutionDataWithValidData(): void
    {
        $validData = $this->getUniqueInstitutionData('VALIDATE');

        // 当数据完全有效时，方法应该返回空数组且不抛出异常
        $result = $this->service->validateInstitutionData($validData);

        self::assertEmpty($result);
    }

    public function testGetInstitutionById(): void
    {
        // 先创建一个机构
        $institutionData = $this->getUniqueInstitutionData('GET_BY_ID');
        $institution = $this->service->createInstitution($institutionData);
        $institutionId = $institution->getId();

        $result = $this->service->getInstitutionById($institutionId);

        self::assertNotNull($result);
        self::assertSame($institution->getId(), $result->getId());
        self::assertSame($institution->getInstitutionName(), $result->getInstitutionName());
    }

    public function testGetInstitutionByIdNotFound(): void
    {
        $result = $this->service->getInstitutionById('00000000-0000-0000-0000-000000000000');

        self::assertNull($result);
    }

    public function testSearchInstitutionsByName(): void
    {
        // 先创建几个机构
        $institutionData1 = $this->getUniqueInstitutionData('SEARCH1');
        $institutionData1['institutionName'] = 'Search Test Institution 1';

        $institutionData2 = $this->getUniqueInstitutionData('SEARCH2');
        $institutionData2['institutionName'] = 'Search Test Institution 2';

        $this->service->createInstitution($institutionData1);
        $this->service->createInstitution($institutionData2);

        $criteria = ['name' => 'Search Test Institution'];
        $result = $this->service->searchInstitutions($criteria);

        self::assertCount(2, $result);
    }

    public function testSearchInstitutionsWithEmptyCriteria(): void
    {
        $criteria = [];

        $result = $this->service->searchInstitutions($criteria);

        self::assertSame([], $result);
    }

    public function testChangeInstitutionStatus(): void
    {
        // 先创建一个机构
        $institutionData = $this->getUniqueInstitutionData('CHANGE_STATUS');
        $institution = $this->service->createInstitution($institutionData);
        $institutionId = $institution->getId();

        $result = $this->service->changeInstitutionStatus($institutionId, '已注销', '用户申请注销');

        self::assertSame('已注销', $result->getInstitutionStatus());
    }

    public function testGetInstitutionsByStatus(): void
    {
        // 创建不同状态的机构
        $institutionData1 = $this->getUniqueInstitutionData('BY_STATUS');
        $institutionData1['institutionStatus'] = '正常';

        $institution1 = $this->service->createInstitution($institutionData1);

        $result = $this->service->getInstitutionsByStatus('正常');

        self::assertGreaterThanOrEqual(1, count($result));

        // 确保返回的机构包含我们创建的那个
        $ids = array_map(fn ($inst) => $inst->getId(), $result);
        self::assertContains($institution1->getId(), $ids);
    }

    public function testCheckInstitutionCompliance(): void
    {
        // 先创建一个机构
        $institutionData = $this->getUniqueInstitutionData('COMPLIANCE');
        $institution = $this->service->createInstitution($institutionData);
        $institutionId = $institution->getId();

        $result = $this->service->checkInstitutionCompliance($institutionId);

        // 新创建的机构应该有一些合规问题（因为缺少设施和资质）
        self::assertNotEmpty($result);
    }

    public function testCheckInstitutionComplianceWithNotFoundInstitution(): void
    {
        $institutionId = 'non-existent-institution-id';

        $this->expectException(InstitutionNotFoundException::class);

        $this->service->checkInstitutionCompliance($institutionId);
    }

    public function testGetInstitutionsPaginated(): void
    {
        // 先创建一些机构
        for ($i = 1; $i <= 3; ++$i) {
            $institutionData = $this->getUniqueInstitutionData('PAGE' . $i);
            $this->service->createInstitution($institutionData);
        }

        $result = $this->service->getInstitutionsPaginated(1, 2);

        self::assertIsArray($result);
        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('total', $result);
        self::assertArrayHasKey('page', $result);
        self::assertArrayHasKey('limit', $result);

        $data = $result['data'];
        self::assertIsArray($data);
        self::assertLessThanOrEqual(2, count($data));
        self::assertGreaterThanOrEqual(3, $result['total']);
    }

    public function testGetInstitutionStatistics(): void
    {
        // 先创建一些机构
        $institutionData = $this->getUniqueInstitutionData('STATS');
        $this->service->createInstitution($institutionData);

        $result = $this->service->getInstitutionStatistics();

        self::assertArrayHasKey('total', $result);
        self::assertGreaterThanOrEqual(1, $result['total']);
    }

    protected function onSetUp(): void
    {
        $this->service = self::getService(InstitutionService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidInstitutionData(): array
    {
        return [
            'institutionName' => '测试培训机构',
            'institutionCode' => 'INST001',
            'institutionType' => '职业培训机构',
            'legalPerson' => '张三',
            'contactPerson' => '李四',
            'contactPhone' => '13800138000',
            'contactEmail' => 'test@example.com',
            'address' => '北京市朝阳区测试大街123号',
            'businessScope' => '职业技能培训、安全生产培训',
            'establishDate' => new \DateTimeImmutable('2020-01-01'),
            'registrationNumber' => '12345678901234567890',
            'institutionStatus' => '正常',
            'organizationStructure' => [
                'departments' => ['教务部', '行政部', '财务部'],
                'leadership' => ['校长', '副校长', '教务主任'],
            ],
        ];
    }

    /**
     * 获取唯一的机构数据（用于避免数据库中的重复问题）
     *
     * @return array<string, mixed>
     */
    private function getUniqueInstitutionData(string $suffix): array
    {
        $baseData = $this->getValidInstitutionData();

        // 使用后缀创建唯一的数据
        $baseData['institutionName'] = "测试培训机构_{$suffix}";
        $baseData['institutionCode'] = $suffix;
        $baseData['registrationNumber'] = str_pad(substr(md5($suffix), 0, 18), 20, '0');
        $baseData['contactEmail'] = "test_{$suffix}@example.com";

        return $baseData;
    }
}
