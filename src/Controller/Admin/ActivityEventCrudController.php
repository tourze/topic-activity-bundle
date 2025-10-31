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
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;

/**
 * @extends AbstractCrudController<ActivityEvent>
 */
#[AdminCrud(
    routePath: '/topic-activity/event',
    routeName: 'topic_activity_event'
)]
final class ActivityEventCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly ActivityEventRepository $activityEventRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ActivityEvent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('活动事件')
            ->setEntityLabelInPlural('活动事件管理')
            ->setPageTitle(Crud::PAGE_INDEX, '事件列表')
            ->setPageTitle(Crud::PAGE_NEW, '创建事件')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑事件')
            ->setPageTitle(Crud::PAGE_DETAIL, '事件详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['eventType', 'sessionId', 'clientIp'])
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(50)
            // 事件数据通常只读，限制新建和编辑
            ->setFormOptions(['disabled' => true], ['disabled' => true])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $analyzeEvents = Action::new('analyzeEvents', '事件分析', 'fa fa-chart-line')
            ->linkToCrudAction('analyzeActivityEvents')
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction()
        ;

        $exportEvents = Action::new('exportEvents', '导出数据', 'fa fa-download')
            ->linkToCrudAction('exportActivityEvents')
            ->setCssClass('btn btn-info')
            ->createAsGlobalAction()
        ;

        $cleanupOldEvents = Action::new('cleanupOldEvents', '清理旧数据', 'fa fa-trash')
            ->linkToCrudAction('cleanupOldEvents')
            ->setCssClass('btn btn-warning')
            ->createAsGlobalAction()
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $analyzeEvents)
            ->add(Crud::PAGE_INDEX, $exportEvents)
            ->add(Crud::PAGE_INDEX, $cleanupOldEvents)
            // 禁用新建和编辑，事件数据通常由系统自动生成
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $eventTypeChoices = [
            '页面浏览' => ActivityEvent::EVENT_VIEW,
            '点击事件' => ActivityEvent::EVENT_CLICK,
            '分享事件' => ActivityEvent::EVENT_SHARE,
            '表单提交' => ActivityEvent::EVENT_FORM_SUBMIT,
            '转化事件' => ActivityEvent::EVENT_CONVERSION,
            '组件交互' => ActivityEvent::EVENT_COMPONENT_INTERACT,
        ];

        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield IntegerField::new('activityId', '活动ID')
            ->setHelp('关联的活动ID')
        ;

        yield TextField::new('sessionId', '会话ID')
            ->hideOnIndex()
        ;

        yield IntegerField::new('userId', '用户ID')
            ->hideOnIndex()
        ;

        yield ChoiceField::new('eventType', '事件类型')
            ->setChoices($eventTypeChoices)
            ->renderAsBadges([
                ActivityEvent::EVENT_VIEW => 'primary',
                ActivityEvent::EVENT_CLICK => 'success',
                ActivityEvent::EVENT_SHARE => 'info',
                ActivityEvent::EVENT_FORM_SUBMIT => 'warning',
                ActivityEvent::EVENT_CONVERSION => 'danger',
                ActivityEvent::EVENT_COMPONENT_INTERACT => 'secondary',
            ])
        ;

        if (Crud::PAGE_DETAIL === $pageName) {
            yield CodeEditorField::new('eventData', '事件数据')
                ->setLanguage('javascript')
                ->setNumOfRows(10)
                ->formatValue(function ($value) {
                    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                })
            ;
        } else {
            yield TextField::new('eventDataSummary', '事件数据')
                ->setVirtual(true)
                ->formatValue(function ($value, ActivityEvent $entity) {
                    $data = $entity->getEventData();
                    if (null === $data || [] === $data) {
                        return '-';
                    }
                    $summary = array_slice($data, 0, 3, true);

                    return json_encode($summary, JSON_UNESCAPED_UNICODE);
                })
                ->hideOnIndex()
            ;
        }

        yield TextField::new('clientIp', '客户端IP')
            ->hideOnIndex()
        ;

        yield TextField::new('deviceType', '设备类型')
            ->setVirtual(true)
            ->formatValue(function ($value, ActivityEvent $entity) {
                return $entity->getDeviceType();
            })
            ->hideOnForm()
        ;

        yield TextField::new('source', '来源')
            ->setVirtual(true)
            ->formatValue(function ($value, ActivityEvent $entity) {
                return $entity->getSource();
            })
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('userAgent', '用户代理')
            ->onlyOnDetail()
        ;

        yield TextField::new('referer', '来源页面')
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $eventTypeChoices = [
            '页面浏览' => ActivityEvent::EVENT_VIEW,
            '点击事件' => ActivityEvent::EVENT_CLICK,
            '分享事件' => ActivityEvent::EVENT_SHARE,
            '表单提交' => ActivityEvent::EVENT_FORM_SUBMIT,
            '转化事件' => ActivityEvent::EVENT_CONVERSION,
            '组件交互' => ActivityEvent::EVENT_COMPONENT_INTERACT,
        ];

        return $filters
            ->add(NumericFilter::new('activityId', '活动ID'))
            ->add(ChoiceFilter::new('eventType', '事件类型')->setChoices($eventTypeChoices))
            ->add(TextFilter::new('sessionId', '会话ID'))
            ->add(NumericFilter::new('userId', '用户ID'))
            ->add(TextFilter::new('clientIp', '客户端IP'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    #[AdminAction(routeName: 'admin_event_analyze', routePath: '/analyze')]
    public function analyzeActivityEvents(AdminContext $context): Response
    {
        // 这里可以实现事件分析逻辑，比如跳转到专门的分析页面
        $this->addFlash('info', '事件分析功能正在开发中');

        return $this->redirectToIndex();
    }

    #[AdminAction(routeName: 'admin_event_export', routePath: '/export')]
    public function exportActivityEvents(AdminContext $context): Response
    {
        // 这里可以实现事件数据导出逻辑
        $this->addFlash('info', '事件数据导出功能正在开发中');

        return $this->redirectToIndex();
    }

    #[AdminAction(routeName: 'admin_event_cleanup', routePath: '/cleanup')]
    public function cleanupOldEvents(AdminContext $context): Response
    {
        // 这里可以实现清理旧事件数据的逻辑
        // 示例：删除30天前的事件数据
        $cutoffDate = new \DateTime('-30 days');
        $deletedCount = $this->activityEventRepository->createQueryBuilder('e')
            ->delete()
            ->where('e.createTime < :cutoff')
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute()
        ;

        // 确保 $deletedCount 是整数类型
        $count = is_int($deletedCount) ? $deletedCount : 0;
        $this->addFlash('success', sprintf('已清理 %d 条旧事件数据', $count));

        return $this->redirectToIndex();
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
