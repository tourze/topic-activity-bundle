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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Service\ActivityManager;
use Tourze\TopicActivityBundle\Service\TemplateManager;

/**
 * @extends AbstractCrudController<Activity>
 */
#[AdminCrud(
    routePath: '/topic-activity/activity',
    routeName: 'topic_activity_activity'
)]
final class ActivityCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ActivityManager $activityManager,
        private readonly TemplateManager $templateManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Activity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('专题活动')
            ->setEntityLabelInPlural('专题活动管理')
            ->setPageTitle(Crud::PAGE_INDEX, '活动列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建活动')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑活动')
            ->setPageTitle(Crud::PAGE_DETAIL, '活动详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['title', 'slug', 'description'])
            ->showEntityActionsInlined()
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $fromTemplate = Action::new('fromTemplate', '从模板创建', 'fa fa-file-import')
            ->linkToCrudAction('fromTemplate')
            ->setCssClass('btn btn-info')
        ;

        $saveAsTemplate = Action::new('saveActivityAsTemplate', '保存为模板', 'fa fa-save')
            ->linkToCrudAction('saveActivityAsTemplate')
            ->setCssClass('btn btn-secondary')
        ;

        $visualEditor = Action::new('visualEditor', '可视化编辑', 'fa fa-paint-brush')
            ->linkToCrudAction('visualEditor')
            ->setCssClass('btn btn-primary')
        ;

        $preview = Action::new('preview', '预览', 'fa fa-eye')
            ->linkToCrudAction('preview')
            ->setHtmlAttributes(['target' => '_blank'])
        ;

        $stats = Action::new('stats', '数据分析', 'fa fa-chart-line')
            ->linkToCrudAction('stats')
        ;

        $duplicate = Action::new('duplicateActivity', '复制', 'fa fa-copy')
            ->linkToCrudAction('duplicateActivity')
            ->setCssClass('btn btn-secondary')
        ;

        $publish = Action::new('publishActivity', '发布', 'fa fa-upload')
            ->linkToCrudAction('publishActivity')
            ->setCssClass('btn btn-success')
            ->displayIf(fn (Activity $entity) => ActivityStatus::DRAFT === $entity->getStatus())
        ;

        $archive = Action::new('archiveActivity', '下架', 'fa fa-archive')
            ->linkToCrudAction('archiveActivity')
            ->setCssClass('btn btn-warning')
            ->displayIf(fn (Activity $entity) => ActivityStatus::PUBLISHED === $entity->getStatus())
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $fromTemplate)
            ->add(Crud::PAGE_INDEX, $visualEditor)
            ->add(Crud::PAGE_INDEX, $preview)
            ->add(Crud::PAGE_INDEX, $stats)
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_INDEX, $saveAsTemplate)
            ->add(Crud::PAGE_INDEX, $publish)
            ->add(Crud::PAGE_INDEX, $archive)
            ->add(Crud::PAGE_EDIT, $visualEditor)
            ->add(Crud::PAGE_EDIT, $preview)
            ->add(Crud::PAGE_EDIT, $saveAsTemplate)
            ->add(Crud::PAGE_DETAIL, $visualEditor)
            ->add(Crud::PAGE_DETAIL, $preview)
            ->add(Crud::PAGE_DETAIL, $stats)
            ->add(Crud::PAGE_DETAIL, $duplicate)
            ->add(Crud::PAGE_DETAIL, $saveAsTemplate)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $statusField = ChoiceField::new('status', '状态')
            ->setChoices(array_combine(
                array_map(fn ($s) => $s->getLabel(), ActivityStatus::cases()),
                ActivityStatus::cases()
            ))
            ->renderAsBadges([
                ActivityStatus::DRAFT->value => 'secondary',
                ActivityStatus::SCHEDULED->value => 'warning',
                ActivityStatus::PUBLISHED->value => 'success',
                ActivityStatus::ARCHIVED->value => 'info',
                ActivityStatus::DELETED->value => 'danger',
            ])
        ;

        yield IdField::new('id', 'ID')->onlyOnIndex();
        yield TextField::new('title', '标题');
        yield SlugField::new('slug', 'URL Slug')
            ->setTargetFieldName('title')
            ->hideOnIndex()
        ;
        yield TextareaField::new('description', '描述')
            ->hideOnIndex()
        ;
        yield ImageField::new('coverImage', '封面图')
            ->setBasePath('uploads/activities')
            ->setUploadDir('public/uploads/activities')
            ->hideOnIndex()
        ;
        yield $statusField;
        yield DateTimeField::new('startTime', '开始时间');
        yield DateTimeField::new('endTime', '结束时间');

        if (Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName) {
            yield CodeEditorField::new('layoutConfig', '布局配置')
                ->setLanguage('javascript')
                ->setNumOfRows(20)
                ->hideOnIndex()
            ;
            yield CodeEditorField::new('seoConfig', 'SEO配置')
                ->setLanguage('javascript')
                ->hideOnIndex()
            ;
            yield CodeEditorField::new('shareConfig', '分享配置')
                ->setLanguage('javascript')
                ->hideOnIndex()
            ;
            yield CodeEditorField::new('accessConfig', '访问配置')
                ->setLanguage('javascript')
                ->hideOnIndex()
            ;
        }

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;
        yield DateTimeField::new('updateTime', '更新时间')
            ->onlyOnDetail()
        ;
        yield DateTimeField::new('publishTime', '发布时间')
            ->onlyOnDetail()
        ;
        yield DateTimeField::new('archiveTime', '下架时间')
            ->onlyOnDetail()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', '状态')->setChoices(
                array_combine(
                    array_map(fn ($s) => $s->getLabel(), ActivityStatus::cases()),
                    array_map(fn ($s) => $s->value, ActivityStatus::cases())
                )
            ))
            ->add(DateTimeFilter::new('startTime', '开始时间'))
            ->add(DateTimeFilter::new('endTime', '结束时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    #[AdminAction(routeName: 'admin_activity_duplicate', routePath: '/duplicate')]
    public function duplicateActivity(AdminContext $context): Response
    {
        $activity = $context->getEntity()->getInstance();
        assert($activity instanceof Activity);

        $newActivity = $this->activityManager->duplicateActivity($activity);

        $this->addFlash('success', sprintf('活动 "%s" 已复制成功', $newActivity->getTitle()));

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($newActivity->getId())
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }

    #[AdminAction(routeName: 'admin_activity_publish', routePath: '/publish')]
    public function publishActivity(AdminContext $context): Response
    {
        $activity = $context->getEntity()->getInstance();
        assert($activity instanceof Activity);

        try {
            $this->activityManager->publishActivity($activity);
            $this->addFlash('success', sprintf('活动 "%s" 已发布', $activity->getTitle()));
        } catch (\Exception $e) {
            $this->addFlash('danger', '发布失败: ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }

    #[AdminAction(routeName: 'admin_activity_archive', routePath: '/archive')]
    public function archiveActivity(AdminContext $context): Response
    {
        $activity = $context->getEntity()->getInstance();
        assert($activity instanceof Activity);

        try {
            $this->activityManager->archiveActivity($activity);
            $this->addFlash('success', sprintf('活动 "%s" 已下架', $activity->getTitle()));
        } catch (\Exception $e) {
            $this->addFlash('danger', '下架失败: ' . $e->getMessage());
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }

    #[AdminAction(routeName: 'admin_activity_save_as_template', routePath: '/save-as-template')]
    public function saveActivityAsTemplate(AdminContext $context): Response
    {
        $activity = $context->getEntity()->getInstance();
        assert($activity instanceof Activity);

        $templateName = $activity->getTitle() . ' 模板';
        $templateCode = 'template_' . $activity->getId() . '_' . time();

        try {
            $template = $this->templateManager->createTemplateFromActivity($activity, $templateName, $templateCode);
            $this->addFlash('success', sprintf('活动已保存为模板 "%s"', $template->getName()));

            $url = $this->adminUrlGenerator
                ->setController(ActivityTemplateCrudController::class)
                ->setAction(Action::EDIT)
                ->setEntityId($template->getId())
                ->generateUrl()
            ;

            return new RedirectResponse($url);
        } catch (\Exception $e) {
            $this->addFlash('danger', '保存模板失败: ' . $e->getMessage());

            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
            ;

            return new RedirectResponse($url);
        }
    }

    #[AdminAction(routeName: 'admin_activity_from_template', routePath: '/from-template')]
    public function fromTemplate(AdminContext $context): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(ActivityTemplateCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return new RedirectResponse($url);
    }

    #[AdminAction(routeName: 'admin_activity_visual_editor', routePath: '/visual-editor')]
    public function visualEditor(AdminContext $context): Response
    {
        $activity = $context->getEntity()->getInstance();
        assert($activity instanceof Activity);

        $url = $this->generateUrl('topic_activity_editor', [
            'id' => $activity->getId(),
        ]);

        return new RedirectResponse($url);
    }

    #[AdminAction(routeName: 'admin_activity_preview', routePath: '/preview')]
    public function preview(AdminContext $context): Response
    {
        $activity = $context->getEntity()->getInstance();
        assert($activity instanceof Activity);

        $url = $this->generateUrl('topic_activity_preview', [
            'slug' => $activity->getSlug() ?? $activity->getId(),
        ]);

        return new RedirectResponse($url);
    }

    #[AdminAction(routeName: 'admin_activity_stats', routePath: '/stats')]
    public function stats(AdminContext $context): Response
    {
        $activity = $context->getEntity()->getInstance();
        assert($activity instanceof Activity);

        $url = $this->generateUrl('topic_activity_stats', [
            'id' => $activity->getId(),
        ]);

        return new RedirectResponse($url);
    }
}
