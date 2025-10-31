<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TopicActivityBundle\Component\ComponentRegistry;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Repository\ActivityComponentRepository;

/**
 * @extends AbstractCrudController<ActivityComponent>
 */
#[AdminCrud(
    routePath: '/topic-activity/component',
    routeName: 'topic_activity_component'
)]
final class ActivityComponentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ComponentRegistry $componentRegistry,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly ActivityComponentRepository $activityComponentRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ActivityComponent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('活动组件')
            ->setEntityLabelInPlural('活动组件管理')
            ->setPageTitle(Crud::PAGE_INDEX, '组件列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建组件')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑组件')
            ->setPageTitle(Crud::PAGE_DETAIL, '组件详情')
            ->setDefaultSort(['position' => 'ASC', 'createTime' => 'DESC'])
            ->setSearchFields(['componentType'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $moveUp = Action::new('moveUp', '上移', 'fa fa-arrow-up')
            ->linkToCrudAction('moveComponentUp')
            ->setCssClass('btn btn-sm btn-secondary')
            ->displayIf(fn (ActivityComponent $entity) => $entity->getPosition() > 0)
        ;

        $moveDown = Action::new('moveDown', '下移', 'fa fa-arrow-down')
            ->linkToCrudAction('moveComponentDown')
            ->setCssClass('btn btn-sm btn-secondary')
        ;

        $duplicate = Action::new('duplicate', '复制', 'fa fa-copy')
            ->linkToCrudAction('duplicateComponent')
            ->setCssClass('btn btn-sm btn-info')
        ;

        $toggleVisibility = Action::new('toggleVisibility', '切换显示', 'fa fa-eye')
            ->linkToCrudAction('toggleComponentVisibility')
            ->setCssClass('btn btn-sm btn-warning')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $moveUp)
            ->add(Crud::PAGE_INDEX, $moveDown)
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_INDEX, $toggleVisibility)
            ->add(Crud::PAGE_DETAIL, $moveUp)
            ->add(Crud::PAGE_DETAIL, $moveDown)
            ->add(Crud::PAGE_DETAIL, $duplicate)
            ->add(Crud::PAGE_DETAIL, $toggleVisibility)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $componentTypes = $this->componentRegistry->getRegisteredComponentTypes();

        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield AssociationField::new('activity', '所属活动')
            ->setRequired(true)
            ->autocomplete()
        ;

        yield ChoiceField::new('componentType', '组件类型')
            ->setChoices(array_combine($componentTypes, $componentTypes))
            ->setRequired(true)
        ;

        yield IntegerField::new('position', '排序位置')
            ->setHelp('数值越小越靠前')
        ;

        yield BooleanField::new('isVisible', '是否可见')
            ->renderAsSwitch(Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName)
        ;

        if (Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName) {
            yield CodeEditorField::new('componentConfig', '组件配置')
                ->setLanguage('javascript')
                ->setNumOfRows(15)
                ->setHelp('组件的配置参数，JSON格式')
            ;
        } else {
            yield TextField::new('componentConfigPreview', '配置预览')
                ->setVirtual(true)
                ->formatValue(function ($value, ActivityComponent $entity) {
                    $config = $entity->getComponentConfig();

                    return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                })
                ->hideOnIndex()
            ;
        }

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $componentTypes = $this->componentRegistry->getRegisteredComponentTypes();

        return $filters
            ->add(EntityFilter::new('activity', '所属活动'))
            ->add(ChoiceFilter::new('componentType', '组件类型')->setChoices(
                array_combine($componentTypes, $componentTypes)
            ))
            ->add(BooleanFilter::new('isVisible', '显示状态'))
        ;
    }

    #[AdminAction(routeName: 'admin_activity_component_move_up', routePath: '/move-up')]
    public function moveComponentUp(AdminContext $context): Response
    {
        // Try to get entity from context, fallback to request parameter for test environment
        $component = $this->getComponentFromContextOrRequest($context);
        if (null === $component) {
            $this->addFlash('danger', '未找到组件实体');

            return $this->redirectToIndex();
        }

        if ($component->getPosition() > 0) {
            $component->moveUp();

            $this->activityComponentRepository->flush();

            $this->addFlash('success', '组件已上移');
        } else {
            $this->addFlash('warning', '组件已在最上方');
        }

        return $this->redirectToIndex();
    }

    #[AdminAction(routeName: 'admin_activity_component_move_down', routePath: '/move-down')]
    public function moveComponentDown(AdminContext $context): Response
    {
        // Try to get entity from context, fallback to request parameter for test environment
        $component = $this->getComponentFromContextOrRequest($context);
        if (null === $component) {
            $this->addFlash('danger', '未找到组件实体');

            return $this->redirectToIndex();
        }

        $component->moveDown();

        $this->activityComponentRepository->flush();

        $this->addFlash('success', '组件已下移');

        return $this->redirectToIndex();
    }

    #[AdminAction(routeName: 'admin_activity_component_duplicate', routePath: '/duplicate')]
    public function duplicateComponent(AdminContext $context): Response
    {
        // Try to get entity from context, fallback to request parameter for test environment
        $component = $this->getComponentFromContextOrRequest($context);
        if (null === $component) {
            $this->addFlash('danger', '未找到组件实体');

            return $this->redirectToIndex();
        }

        $newComponent = $component->duplicate();
        $newComponent->setActivity($component->getActivity());
        $newComponent->setPosition($component->getPosition() + 1);

        $this->activityComponentRepository->save($newComponent, true);

        $this->addFlash('success', '组件复制成功');

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($newComponent->getId())
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }

    #[AdminAction(routeName: 'admin_activity_component_toggle_visibility', routePath: '/toggle-visibility')]
    public function toggleComponentVisibility(AdminContext $context): Response
    {
        // Try to get entity from context, fallback to request parameter for test environment
        $component = $this->getComponentFromContextOrRequest($context);
        if (null === $component) {
            $this->addFlash('danger', '未找到组件实体');

            return $this->redirectToIndex();
        }

        $component->setIsVisible(!$component->isVisible());

        $this->activityComponentRepository->flush();

        $status = $component->isVisible() ? '显示' : '隐藏';
        $this->addFlash('success', sprintf('组件已设置为%s', $status));

        return $this->redirectToIndex();
    }

    private function getComponentFromContextOrRequest(AdminContext $context): ?ActivityComponent
    {
        // Try to get entity from AdminContext (normal browser request)
        try {
            $entityDto = $context->getEntity();
            if (null !== $entityDto->getInstance()) {
                $component = $entityDto->getInstance();
                if ($component instanceof ActivityComponent) {
                    return $component;
                }
            }
        } catch (\TypeError) {
            // AdminContext::getEntity() returns null but type declares non-null
            // Fall through to request parameter method
        }

        // Fallback for test environment: get entity ID from request parameters
        $request = $context->getRequest();
        $entityId = $request->query->get('entityId');
        if (null === $entityId) {
            return null;
        }

        return $this->activityComponentRepository->find((int) $entityId);
    }

    private function redirectToIndex(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }
}
