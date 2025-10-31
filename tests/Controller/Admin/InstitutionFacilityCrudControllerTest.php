<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TrainInstitutionBundle\Controller\Admin\InstitutionFacilityCrudController;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * 培训机构设施管理控制器测试
 *
 * 测试覆盖：
 * - EasyAdmin配置验证
 * - 字段配置和Actions配置
 * - 过滤器配置
 * - 控制器基本功能验证
 *
 * @internal
 */
#[CoversClass(InstitutionFacilityCrudController::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionFacilityCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return InstitutionFacilityCrudController
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(InstitutionFacilityCrudController::class);
        self::assertInstanceOf(InstitutionFacilityCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '所属机构' => ['所属机构'];
        yield '设施类型' => ['设施类型'];
        yield '设施名称' => ['设施名称'];
        yield '设施位置' => ['设施位置'];
        yield '设施面积' => ['设施面积'];
        yield '容纳人数' => ['容纳人数'];
        yield '设施状态' => ['设施状态'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'institution' => ['institution'];
        yield 'facilityType' => ['facilityType'];
        yield 'facilityName' => ['facilityName'];
        yield 'facilityLocation' => ['facilityLocation'];
        yield 'facilityArea' => ['facilityArea'];
        yield 'capacity' => ['capacity'];
        yield 'facilityStatus' => ['facilityStatus'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'institution' => ['institution'];
        yield 'facilityType' => ['facilityType'];
        yield 'facilityName' => ['facilityName'];
        yield 'facilityLocation' => ['facilityLocation'];
        yield 'facilityArea' => ['facilityArea'];
        yield 'capacity' => ['capacity'];
        yield 'facilityStatus' => ['facilityStatus'];
        yield 'lastInspectionDate' => ['lastInspectionDate'];
        yield 'nextInspectionDate' => ['nextInspectionDate'];
    }

    public function testValidationErrors(): void
    {
        // Test that form validation would return 422 status code for empty required fields
        // This test verifies that required field validation is properly configured
        // Create empty entity to test validation constraints
        $facility = new InstitutionFacility();
        $violations = self::getService(ValidatorInterface::class)->validate($facility);

        // Verify validation errors exist for required fields
        $this->assertGreaterThan(0, count($violations), 'Empty InstitutionFacility should have validation errors');

        // Verify that validation messages contain expected content
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = $violation->getMessage();
        }

        // At least one validation message should indicate a required field
        $hasRequiredFieldError = false;
        foreach ($messages as $message) {
            $messageStr = (string) $message;
            if (str_contains($messageStr, 'should not be blank') || str_contains($messageStr, '不能为空') || str_contains($messageStr, 'This value should not be blank')) {
                $hasRequiredFieldError = true;
                break;
            }
        }

        $this->assertTrue($hasRequiredFieldError, 'Should have at least one required field validation error');
    }
}
