<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

/**
 * @extends AbstractCrudController<InstitutionFacility>
 */
#[AdminCrud(routePath: '/train-institution/facility', routeName: 'train_institution_facility')]
final class InstitutionFacilityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InstitutionFacility::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('机构设施')
            ->setEntityLabelInPlural('机构设施')
            ->setSearchFields(['facilityName', 'facilityLocation', 'facilityType'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setHelp('index', '管理培训机构的场地设施信息，包括教室、实训场地、办公区域等')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield AssociationField::new('institution', '所属机构')
            ->setRequired(true)
            ->autocomplete()
            ->formatValue(function ($value, $entity) {
                if ($value instanceof Institution) {
                    return $value->getInstitutionName();
                }

                return '';
            })
        ;

        yield ChoiceField::new('facilityType', '设施类型')
            ->setChoices([
                '理论教室' => '理论教室',
                '实训场地' => '实训场地',
                '办公区域' => '办公区域',
                '实验室' => '实验室',
                '多媒体教室' => '多媒体教室',
                '图书馆' => '图书馆',
                '宿舍' => '宿舍',
                '餐厅' => '餐厅',
                '其他' => '其他',
            ])
            ->setRequired(true)
        ;

        yield TextField::new('facilityName', '设施名称')
            ->setRequired(true)
            ->setHelp('设施的具体名称或编号')
        ;

        yield TextField::new('facilityLocation', '设施位置')
            ->setRequired(true)
            ->setHelp('设施的具体位置描述')
        ;

        yield NumberField::new('facilityArea', '设施面积')
            ->setRequired(true)
            ->setHelp('设施面积（平方米）')
            ->setNumDecimals(2)
        ;

        yield IntegerField::new('capacity', '容纳人数')
            ->setRequired(true)
            ->setHelp('该设施可容纳的最大人数')
        ;

        // equipmentList and safetyEquipment are JSON array fields, hide them from forms for now
        // TODO: Implement proper JSON array field handling for equipment lists if needed

        yield ChoiceField::new('facilityStatus', '设施状态')
            ->setChoices([
                '正常使用' => '正常使用',
                '维修中' => '维修中',
                '停用' => '停用',
                '改造中' => '改造中',
            ])
            ->setRequired(true)
        ;

        yield DateField::new('lastInspectionDate', '上次检查日期')
            ->hideOnIndex()
            ->setHelp('设施的最后一次安全检查日期')
        ;

        yield DateField::new('nextInspectionDate', '下次检查日期')
            ->hideOnIndex()
            ->setHelp('计划进行下次安全检查的日期')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(
                ChoiceFilter::new('facilityType', '设施类型')
                    ->setChoices([
                        '理论教室' => '理论教室',
                        '实训场地' => '实训场地',
                        '办公区域' => '办公区域',
                        '实验室' => '实验室',
                        '多媒体教室' => '多媒体教室',
                        '图书馆' => '图书馆',
                        '宿舍' => '宿舍',
                        '餐厅' => '餐厅',
                        '其他' => '其他',
                    ])
            )
            ->add(
                ChoiceFilter::new('facilityStatus', '设施状态')
                    ->setChoices([
                        '正常使用' => '正常使用',
                        '维修中' => '维修中',
                        '停用' => '停用',
                        '改造中' => '改造中',
                    ])
            )
            ->add(TextFilter::new('facilityName', '设施名称'))
            ->add(TextFilter::new('facilityLocation', '设施位置'))
            ->add(NumericFilter::new('facilityArea', '设施面积'))
            ->add(NumericFilter::new('capacity', '容纳人数'))
            ->add(DateTimeFilter::new('lastInspectionDate', '上次检查日期'))
            ->add(DateTimeFilter::new('nextInspectionDate', '下次检查日期'))
        ;
    }
}
