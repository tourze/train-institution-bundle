<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\TrainInstitutionBundle\Entity\Institution;
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;

/**
 * @extends AbstractCrudController<InstitutionChangeRecord>
 */
#[AdminCrud(routePath: '/train-institution/change-record', routeName: 'train_institution_change_record')]
final class InstitutionChangeRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InstitutionChangeRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('机构变更记录')
            ->setEntityLabelInPlural('机构变更记录')
            ->setSearchFields(['changeType', 'changeOperator', 'changeReason'])
            ->setDefaultSort(['changeDate' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setHelp('index', '管理培训机构的变更记录，包括信息修改、状态变更等操作记录')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield from $this->createBasicFields();

        if ($this->isFormPage($pageName)) {
            yield from $this->createFormSpecificFields();
        }

        yield from $this->createStatusAndTimingFields();
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function createBasicFields(): iterable
    {
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

        yield ChoiceField::new('changeType', '变更类型')
            ->setChoices($this->getChangeTypeChoices())
            ->setRequired(true)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function createFormSpecificFields(): iterable
    {
        // 使用 TextareaField 替代 TextField，并设置正确的格式化方法
        yield TextareaField::new('changeDetailsFormatted', '变更内容')
            ->setRequired(true)
            ->hideOnIndex()
            ->hideOnDetail()
            ->setFormTypeOption('mapped', false)
            ->setHelp('详细描述本次变更的具体内容（JSON格式）')
        ;

        yield TextareaField::new('beforeDataFormatted', '变更前数据')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setFormTypeOption('mapped', false)
            ->setHelp('变更前的原始数据（JSON格式）')
        ;

        yield TextareaField::new('afterDataFormatted', '变更后数据')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setFormTypeOption('mapped', false)
            ->setHelp('变更后的新数据（JSON格式）')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function createStatusAndTimingFields(): iterable
    {
        yield TextareaField::new('changeReason', '变更原因')
            ->setRequired(true)
            ->hideOnIndex()
            ->setHelp('说明进行此次变更的原因')
        ;

        yield DateTimeField::new('changeDate', '变更时间')
            ->hideOnForm()
            ->setHelp('变更操作发生的时间')
        ;

        yield TextField::new('changeOperator', '变更操作员')
            ->setRequired(true)
            ->setHelp('执行此次变更操作的用户')
        ;

        yield ChoiceField::new('approvalStatus', '审批状态')
            ->setChoices($this->getApprovalStatusChoices())
            ->setRequired(true)
        ;

        yield TextField::new('approver', '审批人')
            ->hideOnIndex()
            ->setHelp('审批此次变更的用户')
        ;

        yield DateTimeField::new('approvalDate', '审批时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->setHelp('审批操作完成的时间')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;
    }

    private function isFormPage(string $pageName): bool
    {
        return in_array($pageName, ['new', 'edit'], true);
    }

    /**
     * @return array<string, string>
     */
    private function getChangeTypeChoices(): array
    {
        return [
            '基本信息变更' => '基本信息变更',
            '联系方式变更' => '联系方式变更',
            '法人变更' => '法人变更',
            '地址变更' => '地址变更',
            '状态变更' => '状态变更',
            '资质变更' => '资质变更',
            '设施变更' => '设施变更',
            '其他变更' => '其他变更',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getApprovalStatusChoices(): array
    {
        return [
            '待审批' => '待审批',
            '已通过' => '已通过',
            '已拒绝' => '已拒绝',
            '已撤回' => '已撤回',
        ];
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
                ChoiceFilter::new('changeType', '变更类型')
                    ->setChoices($this->getChangeTypeChoices())
            )
            ->add(
                ChoiceFilter::new('approvalStatus', '审批状态')
                    ->setChoices($this->getApprovalStatusChoices())
            )
            ->add(TextFilter::new('changeOperator', '变更操作员'))
            ->add(TextFilter::new('approver', '审批人'))
            ->add(DateTimeFilter::new('changeDate', '变更时间'))
            ->add(DateTimeFilter::new('approvalDate', '审批时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
