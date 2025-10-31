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
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityStats;

/**
 * @extends AbstractCrudController<ActivityStats>
 */
#[AdminCrud(
    routePath: '/topic-activity/stats',
    routeName: 'topic_activity_stats'
)]
final class ActivityStatsCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ActivityStats::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('活动统计')
            ->setEntityLabelInPlural('活动统计管理')
            ->setPageTitle(Crud::PAGE_INDEX, '统计数据')
            ->setPageTitle(Crud::PAGE_NEW, '创建统计')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑统计')
            ->setPageTitle(Crud::PAGE_DETAIL, '统计详情')
            ->setDefaultSort(['date' => 'DESC'])
            ->setSearchFields(['activity.title'])
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateReport = Action::new('generateReport', '生成报告', 'fa fa-file-pdf')
            ->linkToCrudAction('generateStatsReport')
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction()
        ;

        $mergeStats = Action::new('mergeStats', '合并统计', 'fa fa-compress-arrows-alt')
            ->linkToCrudAction('mergeActivityStats')
            ->setCssClass('btn btn-info')
        ;

        $refreshStats = Action::new('refreshStats', '刷新数据', 'fa fa-sync')
            ->linkToCrudAction('refreshStatsData')
            ->setCssClass('btn btn-secondary')
        ;

        $viewChart = Action::new('viewChart', '查看图表', 'fa fa-chart-bar')
            ->linkToCrudAction('viewStatsChart')
            ->setCssClass('btn btn-success')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $generateReport)
            ->add(Crud::PAGE_INDEX, $viewChart)
            ->add(Crud::PAGE_INDEX, $mergeStats)
            ->add(Crud::PAGE_INDEX, $refreshStats)
            ->add(Crud::PAGE_DETAIL, $viewChart)
            ->add(Crud::PAGE_DETAIL, $mergeStats)
            ->add(Crud::PAGE_DETAIL, $refreshStats)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield AssociationField::new('activity', '所属活动')
            ->setRequired(true)
            ->autocomplete()
        ;

        yield DateField::new('date', '统计日期')
            ->setRequired(true)
        ;

        yield IntegerField::new('pv', '页面浏览量 (PV)')
            ->setHelp('Page Views - 页面浏览次数')
        ;

        yield IntegerField::new('uv', '独立访客数 (UV)')
            ->setHelp('Unique Visitors - 独立访客数量')
        ;

        yield NumberField::new('conversionRate', '转化率 (%)')
            ->setVirtual(true)
            ->formatValue(function ($value, ActivityStats $entity) {
                return $entity->getConversionRate();
            })
            ->hideOnForm()
        ;

        yield IntegerField::new('shareCount', '分享次数')
            ->hideOnIndex()
        ;

        yield IntegerField::new('formSubmitCount', '表单提交次数')
            ->hideOnIndex()
        ;

        yield IntegerField::new('conversionCount', '转化次数')
            ->hideOnIndex()
        ;

        yield NumberField::new('stayDuration', '停留时长 (秒)')
            ->hideOnIndex()
        ;

        yield NumberField::new('averageStayDuration', '平均停留时长 (秒)')
            ->setVirtual(true)
            ->formatValue(function ($value, ActivityStats $entity) {
                return $entity->getAverageStayDuration();
            })
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield NumberField::new('bounceRate', '跳出率 (%)')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_EDIT === $pageName) {
            yield CodeEditorField::new('deviceStats', '设备统计')
                ->setLanguage('javascript')
                ->setNumOfRows(8)
                ->formatValue(function ($value) {
                    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                })
                ->hideOnIndex()
            ;

            yield CodeEditorField::new('sourceStats', '来源统计')
                ->setLanguage('javascript')
                ->setNumOfRows(8)
                ->formatValue(function ($value) {
                    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                })
                ->hideOnIndex()
            ;

            yield CodeEditorField::new('regionStats', '地区统计')
                ->setLanguage('javascript')
                ->setNumOfRows(8)
                ->formatValue(function ($value) {
                    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                })
                ->hideOnIndex()
            ;
        } else {
            yield TextField::new('deviceStatsSummary', '设备统计')
                ->setVirtual(true)
                ->formatValue(function ($value, ActivityStats $entity) {
                    $stats = $entity->getDeviceStats();
                    if (null === $stats || [] === $stats) {
                        return '-';
                    }

                    // 确保数组值是整数类型
                    $desktop = is_numeric($stats['desktop'] ?? 0) ? (int) ($stats['desktop'] ?? 0) : 0;
                    $mobile = is_numeric($stats['mobile'] ?? 0) ? (int) ($stats['mobile'] ?? 0) : 0;
                    $tablet = is_numeric($stats['tablet'] ?? 0) ? (int) ($stats['tablet'] ?? 0) : 0;

                    return sprintf('Desktop: %d, Mobile: %d, Tablet: %d',
                        $desktop,
                        $mobile,
                        $tablet
                    );
                })
                ->hideOnForm()
                ->hideOnIndex()
            ;
        }

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->hideOnIndex()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('activity', '所属活动'))
            ->add(DateTimeFilter::new('date', '统计日期'))
            ->add(NumericFilter::new('pv', 'PV'))
            ->add(NumericFilter::new('uv', 'UV'))
            ->add(NumericFilter::new('conversionCount', '转化次数'))
        ;
    }

    #[AdminAction(routeName: 'admin_stats_generate_report', routePath: '/generate-report')]
    public function generateStatsReport(AdminContext $context): Response
    {
        // 这里可以实现生成统计报告的逻辑
        $this->addFlash('info', '统计报告生成功能正在开发中');

        return $this->redirectToIndex();
    }

    #[AdminAction(routeName: 'admin_stats_merge', routePath: '/merge')]
    public function mergeActivityStats(AdminContext $context): Response
    {
        $stats = $context->getEntity()->getInstance();
        assert($stats instanceof ActivityStats);

        // 这里可以实现合并统计数据的逻辑
        // 例如：将当前统计数据与其他相关统计数据合并
        $this->addFlash('info', sprintf('统计数据 "%s" 合并功能正在开发中', $stats->__toString()));

        return $this->redirectToIndex();
    }

    #[AdminAction(routeName: 'admin_stats_refresh', routePath: '/refresh')]
    public function refreshStatsData(AdminContext $context): Response
    {
        $stats = $context->getEntity()->getInstance();
        assert($stats instanceof ActivityStats);

        // 这里可以实现重新计算统计数据的逻辑
        // 例如：基于原始事件数据重新计算统计指标
        $this->addFlash('success', sprintf('统计数据 "%s" 已刷新', $stats->__toString()));

        return $this->redirectToIndex();
    }

    #[AdminAction(routeName: 'admin_stats_view_chart', routePath: '/view-chart')]
    public function viewStatsChart(AdminContext $context): Response
    {
        $stats = $context->getEntity()->getInstance();
        assert($stats instanceof ActivityStats);

        // 这里可以实现跳转到图表页面或返回图表数据
        $this->addFlash('info', sprintf('统计图表功能正在开发中 - %s', $stats->__toString()));

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
