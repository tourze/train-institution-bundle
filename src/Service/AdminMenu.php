<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\TrainInstitutionBundle\Controller\Admin\InstitutionChangeRecordCrudController;
use Tourze\TrainInstitutionBundle\Controller\Admin\InstitutionCrudController;
use Tourze\TrainInstitutionBundle\Controller\Admin\InstitutionFacilityCrudController;
use Tourze\TrainInstitutionBundle\Controller\Admin\InstitutionQualificationCrudController;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        $trainInstitutionSection = $item->addChild('培训机构管理')
            ->setAttribute('icon', 'fas fa-school')
        ;

        $trainInstitutionSection->addChild('机构信息')
            ->setUri($this->linkGenerator->getCurdListPage(InstitutionCrudController::class))
            ->setAttribute('icon', 'fas fa-building')
        ;

        $trainInstitutionSection->addChild('机构资质')
            ->setUri($this->linkGenerator->getCurdListPage(InstitutionQualificationCrudController::class))
            ->setAttribute('icon', 'fas fa-certificate')
        ;

        $trainInstitutionSection->addChild('机构设施')
            ->setUri($this->linkGenerator->getCurdListPage(InstitutionFacilityCrudController::class))
            ->setAttribute('icon', 'fas fa-tools')
        ;

        $trainInstitutionSection->addChild('变更记录')
            ->setUri($this->linkGenerator->getCurdListPage(InstitutionChangeRecordCrudController::class))
            ->setAttribute('icon', 'fas fa-history')
        ;
    }
}
