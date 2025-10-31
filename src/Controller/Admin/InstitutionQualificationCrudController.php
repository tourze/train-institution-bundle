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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

/**
 * @extends AbstractCrudController<InstitutionQualification>
 */
#[AdminCrud(routePath: '/train-institution/qualification', routeName: 'train_institution_qualification')]
final class InstitutionQualificationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InstitutionQualification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('机构资质')
            ->setEntityLabelInPlural('机构资质')
            ->setSearchFields(['qualificationName', 'certificateNumber', 'issuingAuthority'])
            ->setDefaultSort(['issueDate' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setHelp('index', '管理培训机构的各类资质证书，包括办学许可证、安全培训资质等')
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

        yield ChoiceField::new('qualificationType', '资质类型')
            ->setChoices([
                '办学许可证' => '办学许可证',
                '安全培训资质' => '安全培训资质',
                '职业技能培训资质' => '职业技能培训资质',
                '特种作业培训资质' => '特种作业培训资质',
                '其他资质' => '其他资质',
            ])
            ->setRequired(true)
        ;

        yield TextField::new('qualificationName', '资质名称')
            ->setRequired(true)
            ->setHelp('资质证书的完整名称')
        ;

        yield TextField::new('certificateNumber', '证书编号')
            ->setRequired(true)
            ->setHelp('资质证书的唯一编号')
        ;

        yield TextField::new('issuingAuthority', '发证机关')
            ->setRequired(true)
            ->setHelp('颁发此资质证书的机关或组织')
        ;

        yield DateField::new('issueDate', '发证日期')
            ->setRequired(true)
        ;

        yield DateField::new('validFrom', '有效期开始')
            ->setRequired(true)
        ;

        yield DateField::new('validTo', '有效期结束')
            ->setRequired(true)
            ->setHelp('资质证书的有效期截止日期')
        ;

        // qualificationScope is JSON array field, hide it from forms for now
        // TODO: Implement proper JSON array field handling if needed

        yield ChoiceField::new('qualificationStatus', '资质状态')
            ->setChoices([
                '有效' => '有效',
                '即将过期' => '即将过期',
                '已过期' => '已过期',
                '已撤销' => '已撤销',
                '暂停' => '暂停',
            ])
            ->setRequired(true)
        ;

        // attachments is JSON array field, hide it from forms for now
        // TODO: Implement proper JSON array field handling for attachments if needed

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
                ChoiceFilter::new('qualificationType', '资质类型')
                    ->setChoices([
                        '办学许可证' => '办学许可证',
                        '安全培训资质' => '安全培训资质',
                        '职业技能培训资质' => '职业技能培训资质',
                        '特种作业培训资质' => '特种作业培训资质',
                        '其他资质' => '其他资质',
                    ])
            )
            ->add(
                ChoiceFilter::new('qualificationStatus', '资质状态')
                    ->setChoices([
                        '有效' => '有效',
                        '即将过期' => '即将过期',
                        '已过期' => '已过期',
                        '已撤销' => '已撤销',
                        '暂停' => '暂停',
                    ])
            )
            ->add(TextFilter::new('qualificationName', '资质名称'))
            ->add(TextFilter::new('certificateNumber', '证书编号'))
            ->add(TextFilter::new('issuingAuthority', '发证机关'))
            ->add(DateTimeFilter::new('issueDate', '发证日期'))
            ->add(DateTimeFilter::new('validTo', '有效期结束'))
        ;
    }
}
