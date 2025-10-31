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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\TrainInstitutionBundle\Entity\Institution;

/**
 * @extends AbstractCrudController<Institution>
 */
#[AdminCrud(routePath: '/train-institution/institution', routeName: 'train_institution_institution')]
final class InstitutionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Institution::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('培训机构')
            ->setEntityLabelInPlural('培训机构')
            ->setSearchFields(['institutionName', 'institutionCode', 'legalPerson', 'contactPerson'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setHelp('index', '管理培训机构的基本信息，包括机构名称、联系方式、资质等信息')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield TextField::new('institutionName', '机构名称')
            ->setRequired(true)
            ->setHelp('培训机构的完整名称')
        ;

        yield TextField::new('institutionCode', '机构代码')
            ->setRequired(true)
            ->setHelp('机构的唯一标识代码')
        ;

        yield ChoiceField::new('institutionType', '机构类型')
            ->setChoices([
                '企业培训机构' => '企业培训机构',
                '社会培训机构' => '社会培训机构',
                '政府培训机构' => '政府培训机构',
                '职业院校' => '职业院校',
                '其他' => '其他',
            ])
            ->setRequired(true)
        ;

        yield TextField::new('legalPerson', '法人代表')
            ->setRequired(true)
        ;

        yield TextField::new('contactPerson', '联系人')
            ->setRequired(true)
        ;

        yield TelephoneField::new('contactPhone', '联系电话')
            ->setRequired(true)
        ;

        yield EmailField::new('contactEmail', '联系邮箱')
            ->setRequired(true)
        ;

        yield TextareaField::new('address', '机构地址')
            ->setRequired(true)
            ->hideOnIndex()
        ;

        yield TextareaField::new('businessScope', '业务范围')
            ->setRequired(true)
            ->hideOnIndex()
            ->setHelp('描述机构的培训业务范围')
        ;

        yield TextField::new('registrationNumber', '注册号')
            ->setRequired(true)
            ->hideOnIndex()
        ;

        yield ChoiceField::new('institutionStatus', '机构状态')
            ->setChoices([
                '正常运营' => '正常运营',
                '暂停运营' => '暂停运营',
                '停止运营' => '停止运营',
                '筹建中' => '筹建中',
            ])
            ->setRequired(true)
        ;

        yield DateTimeField::new('establishDate', '成立日期')
            ->setRequired(true)
            ->hideOnIndex()
        ;

        // organizationStructure is JSON field, hide it from forms for now
        // TODO: Implement proper JSON field handling if needed

        yield AssociationField::new('qualifications', '资质证书')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                if ($value instanceof \Countable) {
                    return $value->count() . ' 项资质';
                }

                return '无资质';
            })
        ;

        yield AssociationField::new('facilities', '设施设备')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                if ($value instanceof \Countable) {
                    return $value->count() . ' 项设施';
                }

                return '无设施';
            })
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
            ->add(TextFilter::new('institutionName', '机构名称'))
            ->add(TextFilter::new('institutionCode', '机构代码'))
            ->add(
                ChoiceFilter::new('institutionType', '机构类型')
                    ->setChoices([
                        '企业培训机构' => '企业培训机构',
                        '社会培训机构' => '社会培训机构',
                        '政府培训机构' => '政府培训机构',
                        '职业院校' => '职业院校',
                        '其他' => '其他',
                    ])
            )
            ->add(
                ChoiceFilter::new('institutionStatus', '机构状态')
                    ->setChoices([
                        '正常运营' => '正常运营',
                        '暂停运营' => '暂停运营',
                        '停止运营' => '停止运营',
                        '筹建中' => '筹建中',
                    ])
            )
            ->add(TextFilter::new('legalPerson', '法人代表'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
