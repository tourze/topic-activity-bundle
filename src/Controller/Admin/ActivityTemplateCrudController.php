<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Controller\Admin;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;
use Tourze\TopicActivityBundle\Service\TemplateManager;

/**
 * @extends AbstractCrudController<ActivityTemplate>
 */
#[AdminCrud(
    routePath: '/topic-activity/template',
    routeName: 'topic_activity_template'
)]
final class ActivityTemplateCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TemplateManager $templateManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ActivityTemplate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('活动模板')
            ->setEntityLabelInPlural('活动模板管理')
            ->setPageTitle(Crud::PAGE_INDEX, '模板列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建模板')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑模板')
            ->setPageTitle(Crud::PAGE_DETAIL, '模板详情')
            ->setDefaultSort(['usageCount' => 'DESC', 'createTime' => 'DESC'])
            ->setSearchFields(['name', 'code', 'description', 'tags'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $createActivity = Action::new('createActivity', '创建活动', 'fa fa-plus-circle')
            ->linkToCrudAction('createActivityFromTemplate')
            ->setCssClass('btn btn-success')
        ;

        $preview = Action::new('preview', '预览', 'fa fa-eye')
            ->linkToCrudAction('previewTemplate')
            ->setHtmlAttributes(['target' => '_blank'])
        ;

        $duplicate = Action::new('duplicate', '复制', 'fa fa-copy')
            ->linkToCrudAction('duplicateTemplate')
            ->setCssClass('btn btn-secondary')
        ;

        $actions = $actions
            ->add(Crud::PAGE_INDEX, $createActivity)
            ->add(Crud::PAGE_INDEX, $preview)
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_DETAIL, $createActivity)
            ->add(Crud::PAGE_DETAIL, $preview)
            ->add(Crud::PAGE_DETAIL, $duplicate)
        ;

        // 配置删除操作 - 只对非系统模板显示
        // EasyAdmin 默认已在列表页提供删除操作，这里仅更新其显示条件，避免重复添加
        // 列表页删除：若默认不存在则先添加再更新；若存在直接更新
        try {
            $actions->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->displayIf(fn (ActivityTemplate $entity) => !$entity->isSystem());
            });
        } catch (\InvalidArgumentException) {
            $actions
                ->set(Crud::PAGE_INDEX, Action::DELETE)
                ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                    return $action->displayIf(fn (ActivityTemplate $entity) => !$entity->isSystem());
                })
            ;
        }

        // 详情页删除同理
        try {
            $actions->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action->displayIf(fn (ActivityTemplate $entity) => !$entity->isSystem());
            });
        } catch (\InvalidArgumentException) {
            $actions
                ->set(Crud::PAGE_DETAIL, Action::DELETE)
                ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                    return $action->displayIf(fn (ActivityTemplate $entity) => !$entity->isSystem());
                })
            ;
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        try {
            $categoryChoices = $this->templateManager->getTemplateCategories();
        } catch (\Throwable) {
            // Fallback for testing or when TemplateManager fails
            $categoryChoices = [
                'promotion' => '通用促销',
                'new_product' => '新品发布',
                'holiday' => '节日专题',
                'brand' => '品牌故事',
                'event' => '活动报名',
                'custom' => '自定义',
            ];
        }

        yield IdField::new('id', 'ID')->onlyOnIndex();
        yield TextField::new('name', '名称');
        yield TextField::new('code', '代码')
            ->setHelp('唯一标识符，用于程序调用')
        ;
        yield ChoiceField::new('category', '分类')
            ->setChoices($categoryChoices)
        ;
        yield TextareaField::new('description', '描述')
            ->hideOnIndex()
        ;
        yield ImageField::new('thumbnail', '缩略图')
            ->setBasePath('uploads/templates')
            ->setUploadDir('public/uploads/templates')
            ->hideOnIndex()
        ;
        yield ArrayField::new('tags', '标签')
            ->hideOnIndex()
        ;
        yield BooleanField::new('isActive', '启用')
            ->renderAsSwitch(Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName)
        ;
        yield BooleanField::new('isSystem', '系统模板')
            ->hideOnForm()
        ;
        yield IntegerField::new('usageCount', '使用次数')
            ->hideOnForm()
        ;

        if (Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName) {
            yield CodeEditorField::new('layoutConfig', '布局配置')
                ->setLanguage('javascript')
                ->setNumOfRows(20)
                ->setHelp('定义模板的组件布局')
            ;
            yield CodeEditorField::new('defaultData', '默认数据')
                ->setLanguage('javascript')
                ->setNumOfRows(10)
                ->setHelp('模板的默认数据配置')
            ;
        }

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;
        yield DateTimeField::new('updateTime', '更新时间')
            ->onlyOnDetail()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        try {
            $categoryChoices = $this->templateManager->getTemplateCategories();
        } catch (\Throwable) {
            // Fallback for testing or when TemplateManager fails
            $categoryChoices = [
                'promotion' => '通用促销',
                'new_product' => '新品发布',
                'holiday' => '节日专题',
                'brand' => '品牌故事',
                'event' => '活动报名',
                'custom' => '自定义',
            ];
        }

        return $filters
            ->add(ChoiceFilter::new('category', '分类')->setChoices($categoryChoices))
            ->add(BooleanFilter::new('isActive', '启用状态'))
            ->add(BooleanFilter::new('isSystem', '系统模板'))
        ;
    }

    #[AdminAction(routeName: 'admin_activity_template_create_activity', routePath: '/create-activity')]
    public function createActivityFromTemplate(AdminContext $context): Response
    {
        $template = $context->getEntity()->getInstance();
        assert($template instanceof ActivityTemplate);

        $activity = $this->templateManager->createActivityFromTemplate($template);

        $this->addFlash('success', sprintf('从模板 "%s" 创建活动成功', $template->getName()));

        $url = $this->adminUrlGenerator
            ->setController(ActivityCrudController::class)
            ->setAction(Action::EDIT)
            ->setEntityId($activity->getId())
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }

    #[AdminAction(routeName: 'admin_activity_template_preview', routePath: '/preview')]
    public function previewTemplate(AdminContext $context): Response
    {
        $template = $context->getEntity()->getInstance();
        assert($template instanceof ActivityTemplate);

        return $this->render('@TopicActivity/admin/template_preview.html.twig', [
            'template' => $template,
        ]);
    }

    #[AdminAction(routeName: 'admin_activity_template_duplicate', routePath: '/duplicate')]
    public function duplicateTemplate(AdminContext $context): Response
    {
        $template = $context->getEntity()->getInstance();
        assert($template instanceof ActivityTemplate);

        $newTemplate = clone $template;
        $newTemplate->setName($template->getName() . ' (复制)');
        $newTemplate->setCode($template->getCode() . '_copy_' . time());
        $newTemplate->setIsSystem(false);

        $doctrine = $this->container->get('doctrine');
        if (!$doctrine instanceof Registry) {
            throw new \LogicException('Doctrine service not found');
        }

        $entityManager = $doctrine->getManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('Entity manager not found');
        }

        $entityManager->persist($newTemplate);
        $entityManager->flush();

        $this->addFlash('success', sprintf('模板 "%s" 复制成功', $template->getName()));

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($newTemplate->getId())
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }
}
