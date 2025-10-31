<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\TrainInstitutionBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // Setup for AdminMenu tests
    }

    public function testImplementsMenuProviderInterface(): void
    {
        $adminMenu = static::getService(AdminMenu::class);

        self::assertInstanceOf(MenuProviderInterface::class, $adminMenu);
    }

    public function testInvokeAddsMenuItems(): void
    {
        $rootItem = $this->createMock(ItemInterface::class);
        $sectionItem = $this->createMock(ItemInterface::class);
        $menuItem = $this->createMock(ItemInterface::class);

        $rootItem->expects($this->once())
            ->method('addChild')
            ->with('培训机构管理')
            ->willReturn($sectionItem)
        ;

        $sectionItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-school')
            ->willReturn($sectionItem)
        ;

        $sectionItem->expects($this->exactly(4))
            ->method('addChild')
            ->willReturn($menuItem)
        ;

        $menuItem->expects($this->exactly(4))
            ->method('setUri')
            ->willReturn($menuItem)
        ;

        $menuItem->expects($this->exactly(4))
            ->method('setAttribute')
            ->willReturn($menuItem)
        ;

        $adminMenu = static::getService(AdminMenu::class);
        $adminMenu($rootItem);
    }

    public function testAdminMenuCanBeInstantiated(): void
    {
        $adminMenu = static::getService(AdminMenu::class);

        self::assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testAdminMenuImplementsCorrectInterface(): void
    {
        $adminMenu = static::getService(AdminMenu::class);

        self::assertInstanceOf(MenuProviderInterface::class, $adminMenu);
        // __invoke 方法由 MenuProviderInterface 接口保证存在
    }

    public function testInvokeMethodAcceptsItemInterface(): void
    {
        $adminMenu = static::getService(AdminMenu::class);
        $item = $this->createMock(ItemInterface::class);

        $sectionItem = $this->createMock(ItemInterface::class);
        $menuItem = $this->createMock(ItemInterface::class);

        $item->expects($this->once())
            ->method('addChild')
            ->with('培训机构管理')
            ->willReturn($sectionItem)
        ;

        $sectionItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-school')
            ->willReturn($sectionItem)
        ;

        $sectionItem->expects($this->exactly(4))
            ->method('addChild')
            ->willReturn($menuItem)
        ;

        $menuItem->expects($this->exactly(4))
            ->method('setUri')
            ->willReturn($menuItem)
        ;

        $menuItem->expects($this->exactly(4))
            ->method('setAttribute')
            ->willReturn($menuItem)
        ;

        // This should not throw any exception
        $adminMenu($item);
    }
}
