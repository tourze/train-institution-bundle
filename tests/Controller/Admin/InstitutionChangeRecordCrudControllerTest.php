<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\TrainInstitutionBundle\Controller\Admin\InstitutionChangeRecordCrudController;

/**
 * 培训机构变更记录管理控制器测试
 *
 * 测试覆盖：
 * - EasyAdmin配置验证
 * - 字段配置和Actions配置
 * - 过滤器配置
 * - 控制器基本功能验证
 *
 * @internal
 */
#[CoversClass(InstitutionChangeRecordCrudController::class)]
#[RunTestsInSeparateProcesses]
final class InstitutionChangeRecordCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return InstitutionChangeRecordCrudController
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(InstitutionChangeRecordCrudController::class);
        self::assertInstanceOf(InstitutionChangeRecordCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '所属机构' => ['所属机构'];
        yield '变更类型' => ['变更类型'];
        yield '变更时间' => ['变更时间'];
        yield '变更操作员' => ['变更操作员'];
        yield '审批状态' => ['审批状态'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'institution' => ['institution'];
        yield 'changeType' => ['changeType'];
        yield 'changeDetailsFormatted' => ['changeDetailsFormatted'];
        yield 'changeReason' => ['changeReason'];
        yield 'changeOperator' => ['changeOperator'];
        yield 'approvalStatus' => ['approvalStatus'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'institution' => ['institution'];
        yield 'changeType' => ['changeType'];
        yield 'changeDetailsFormatted' => ['changeDetailsFormatted'];
        yield 'beforeDataFormatted' => ['beforeDataFormatted'];
        yield 'afterDataFormatted' => ['afterDataFormatted'];
        yield 'changeReason' => ['changeReason'];
        yield 'changeOperator' => ['changeOperator'];
        yield 'approvalStatus' => ['approvalStatus'];
        yield 'approver' => ['approver'];
    }
}
