<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TrainInstitutionBundle\Controller\Admin\InstitutionCrudController;
use Tourze\TrainInstitutionBundle\Entity\Institution;

/**
 * 培训机构管理控制器测试
 *
 * 测试覆盖：
 * - EasyAdmin配置验证
 * - 字段配置和Actions配置
 * - 过滤器配置
 * - 控制器基本功能验证
 *
 * @internal
 */
#[CoversClass(InstitutionCrudController::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return InstitutionCrudController
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(InstitutionCrudController::class);
        self::assertInstanceOf(InstitutionCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '机构名称' => ['机构名称'];
        yield '机构代码' => ['机构代码'];
        yield '机构类型' => ['机构类型'];
        yield '法人代表' => ['法人代表'];
        yield '联系人' => ['联系人'];
        yield '联系电话' => ['联系电话'];
        yield '联系邮箱' => ['联系邮箱'];
        yield '机构状态' => ['机构状态'];
        yield '资质证书' => ['资质证书'];
        yield '设施设备' => ['设施设备'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'institutionName' => ['institutionName'];
        yield 'institutionCode' => ['institutionCode'];
        yield 'institutionType' => ['institutionType'];
        yield 'legalPerson' => ['legalPerson'];
        yield 'contactPerson' => ['contactPerson'];
        yield 'contactPhone' => ['contactPhone'];
        yield 'contactEmail' => ['contactEmail'];
        yield 'address' => ['address'];
        yield 'businessScope' => ['businessScope'];
        yield 'establishDate' => ['establishDate'];
        yield 'registrationNumber' => ['registrationNumber'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'institutionName' => ['institutionName'];
        yield 'institutionCode' => ['institutionCode'];
        yield 'institutionType' => ['institutionType'];
        yield 'legalPerson' => ['legalPerson'];
        yield 'contactPerson' => ['contactPerson'];
        yield 'contactPhone' => ['contactPhone'];
        yield 'contactEmail' => ['contactEmail'];
        yield 'address' => ['address'];
        yield 'businessScope' => ['businessScope'];
        yield 'establishDate' => ['establishDate'];
        yield 'registrationNumber' => ['registrationNumber'];
    }

    public function testValidationErrors(): void
    {
        // Test that form validation would return 422 status code for empty required fields
        // This test verifies that required field validation is properly configured
        // Create empty entity to test validation constraints
        $institution = new Institution();
        $violations = self::getService(ValidatorInterface::class)->validate($institution);

        // Verify validation errors exist for required fields
        $this->assertGreaterThan(0, count($violations), 'Empty Institution should have validation errors');

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
