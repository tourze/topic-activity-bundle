<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Entity\ActivityStats;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityStatsRepository;

#[WithMonologChannel(channel: 'topic_activity')]
class StatsCollector
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityStatsRepository $statsRepository,
        private readonly ActivityEventRepository $eventRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 记录页面访问
     */
    public function recordPageView(Activity $activity, Request $request): void
    {
        $stats = $this->getTodayStats($activity);
        $stats->incrementPv();

        $visitorId = $this->recordUniqueVisitor($activity, $request, $stats);
        $this->recordViewMetadata($request, $stats);

        $this->entityManager->flush();

        $this->logger->info('Page view recorded', [
            'activity_id' => $activity->getId(),
            'visitor_id' => $visitorId,
        ]);
    }

    private function recordUniqueVisitor(Activity $activity, Request $request, ActivityStats $stats): string
    {
        $visitorId = $this->getVisitorId($request);
        if (!$this->hasVisitedToday($activity, $visitorId)) {
            $stats->incrementUv();
            $this->recordVisitor($activity, $visitorId);
        }

        return $visitorId;
    }

    private function recordViewMetadata(Request $request, ActivityStats $stats): void
    {
        $deviceType = $this->detectDeviceType($request);
        $this->updateDeviceStats($stats, $deviceType);

        $source = $this->detectSource($request);
        $this->updateSourceStats($stats, $source);

        $region = $this->detectRegion($request);
        $this->updateRegionStats($stats, $region);
    }

    private function getTodayStats(Activity $activity): ActivityStats
    {
        $today = new \DateTimeImmutable('today');
        $stats = $this->statsRepository->findByActivityAndDate($activity, $today);

        if (null === $stats) {
            $stats = new ActivityStats();
            $stats->setActivity($activity);
            // 构造函数已经设置了date为today，不需要重复设置
            $this->entityManager->persist($stats);
        }

        return $stats;
    }

    private function getVisitorId(Request $request): string
    {
        $session = $request->getSession();
        $visitorId = $session->get('visitor_id');

        if (null === $visitorId || !is_string($visitorId)) {
            $visitorId = uniqid('visitor_', true);
            $session->set('visitor_id', $visitorId);
        }

        return $visitorId;
    }

    private function hasVisitedToday(Activity $activity, string $visitorId): bool
    {
        $activityId = $activity->getId();
        if (null === $activityId) {
            return false;
        }

        $event = $this->eventRepository->findVisitorEvent($activityId, $visitorId, 'visitor');

        return null !== $event;
    }

    private function recordVisitor(Activity $activity, string $visitorId): void
    {
        $activityId = $activity->getId();
        if (null === $activityId) {
            return;
        }

        $event = ActivityEvent::create($activityId);
        $event->setEventType('visitor');
        $event->setSessionId($visitorId);
        $event->setEventData(['visitor_id' => $visitorId]);

        $this->entityManager->persist($event);
    }

    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->headers->get('User-Agent', '') ?? '';

        // 先检查tablet，因为iPad的User-Agent也包含Mobile字符串
        if (1 === preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        if (1 === preg_match('/mobile|android|iphone/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function updateDeviceStats(ActivityStats $stats, string $deviceType): void
    {
        $deviceStats = $this->incrementStatsCounter($stats->getDeviceStats(), $deviceType);
        $stats->setDeviceStats($deviceStats);
    }

    private function detectSource(Request $request): string
    {
        $referer = $request->headers->get('Referer', '') ?? '';
        $utm = $request->query->get('utm_source');

        if (null !== $utm && '' !== $utm) {
            return (string) $utm;
        }

        return $this->getSourceFromReferer($referer);
    }

    private function getSourceFromReferer(string $referer): string
    {
        if ('' === $referer) {
            return 'direct';
        }

        $sources = [
            'baidu.com' => 'baidu',
            'google.com' => 'google',
            'weixin.qq.com' => 'wechat',
            'weibo.com' => 'weibo',
        ];

        foreach ($sources as $domain => $source) {
            if (str_contains($referer, $domain)) {
                return $source;
            }
        }

        return 'referral';
    }

    private function updateSourceStats(ActivityStats $stats, string $source): void
    {
        $sourceStats = $this->incrementStatsCounter($stats->getSourceStats(), $source);
        $stats->setSourceStats($sourceStats);
    }

    private function detectRegion(Request $request): string
    {
        // 简化实现，实际应使用IP地址库
        $ip = $request->getClientIp();

        if (null !== $ip && (str_starts_with($ip, '10.') || str_starts_with($ip, '192.168.'))) {
            return 'local';
        }

        // 这里应该使用真实的IP地址库，如GeoIP2
        return 'unknown';
    }

    private function updateRegionStats(ActivityStats $stats, string $region): void
    {
        $regionStats = $this->incrementStatsCounter($stats->getRegionStats(), $region);
        $stats->setRegionStats($regionStats);
    }

    /**
     * @param mixed $statsData
     * @return array<string, int>
     */
    private function incrementStatsCounter(mixed $statsData, string $key): array
    {
        /** @var array<string, int> $validStats */
        $validStats = [];

        if (is_array($statsData)) {
            foreach ($statsData as $k => $v) {
                if (is_string($k) && is_int($v)) {
                    $validStats[$k] = $v;
                }
            }
        }

        if (!isset($validStats[$key])) {
            $validStats[$key] = 0;
        }

        $validStats[$key] = $validStats[$key] + 1;

        return $validStats;
    }

    /**
     * 记录表单提交
     *
     * @param array<string, mixed> $formData
     */
    public function recordFormSubmit(Activity $activity, array $formData): void
    {
        $stats = $this->getTodayStats($activity);
        $stats->incrementFormSubmitCount();

        $activityId = $activity->getId();
        if (null === $activityId) {
            return;
        }

        $event = ActivityEvent::create($activityId);
        $event->setEventType('form_submit');
        $event->setEventData($formData);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $this->logger->info('Form submit recorded', [
            'activity_id' => $activityId,
            'form_data' => $formData,
        ]);
    }

    /**
     * 记录分享事件
     */
    public function recordShare(Activity $activity, string $platform): void
    {
        $stats = $this->getTodayStats($activity);
        $stats->incrementShareCount();

        $activityId = $activity->getId();
        if (null === $activityId) {
            return;
        }

        $event = ActivityEvent::create($activityId);
        $event->setEventType('share');
        $event->setEventData(['platform' => $platform]);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $this->logger->info('Share recorded', [
            'activity_id' => $activityId,
            'platform' => $platform,
        ]);
    }

    /**
     * 记录转化事件
     *
     * @param array<string, mixed> $conversionData
     */
    public function recordConversion(Activity $activity, array $conversionData): void
    {
        $activityId = $activity->getId();
        if (null === $activityId) {
            return;
        }

        $stats = $this->getTodayStats($activity);
        $stats->incrementConversionCount();

        $event = ActivityEvent::create($activityId);
        $event->setEventType('conversion');
        $event->setEventData($conversionData);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $this->logger->info('Conversion recorded', [
            'activity_id' => $activityId,
            'conversion_data' => $conversionData,
        ]);
    }

    /**
     * 记录页面停留时间
     */
    public function recordStayDuration(Activity $activity, float $duration): void
    {
        $stats = $this->getTodayStats($activity);
        $stats->addStayDuration($duration);

        $this->entityManager->flush();

        $activityId = $activity->getId();
        $this->logger->info('Stay duration recorded', [
            'activity_id' => $activityId,
            'duration' => $duration,
        ]);
    }

    /**
     * 计算跳出率
     */
    public function calculateBounceRate(Activity $activity): void
    {
        $stats = $this->getTodayStats($activity);
        $events = $this->eventRepository->findTodayEvents($activity);

        $sessions = $this->groupEventsBySession($events);
        $bounces = $this->countSinglePageSessions($sessions);

        $this->updateBounceRate($stats, $bounces, \count($sessions));
    }

    /**
     * @param ActivityEvent[] $events
     * @return array<string, int>
     */
    private function groupEventsBySession(array $events): array
    {
        $sessions = [];

        foreach ($events as $event) {
            $sessionId = $event->getSessionId();
            if (!isset($sessions[$sessionId])) {
                $sessions[$sessionId] = 0;
            }
            ++$sessions[$sessionId];
        }

        return $sessions;
    }

    /**
     * @param array<string, int> $sessions
     */
    private function countSinglePageSessions(array $sessions): int
    {
        $bounces = 0;
        foreach ($sessions as $pageViews) {
            if (1 === $pageViews) {
                ++$bounces;
            }
        }

        return $bounces;
    }

    private function updateBounceRate(ActivityStats $stats, int $bounces, int $totalSessions): void
    {
        if (0 === $totalSessions) {
            return;
        }

        $bounceRate = ($bounces / $totalSessions) * 100;
        $stats->setBounceRate($bounceRate);
        $this->entityManager->flush();
    }

    /**
     * 获取活动统计摘要
     *
     * @return array{pv: int, uv: int, shareCount: int, formSubmitCount: int, conversionCount: int, conversionRate: float, bounceRate: float, avgStayDuration: float}
     */
    public function getActivitySummary(Activity $activity, ?\DateTimeImmutable $startDate = null, ?\DateTimeImmutable $endDate = null): array
    {
        $stats = $this->statsRepository->findByActivityAndDateRange($activity, $startDate, $endDate);

        $summary = $this->aggregateBasicStats($stats);
        $summary = $this->calculateConversionRate($summary);

        return $this->calculateAverageMetrics($summary, $stats);
    }

    /**
     * @param ActivityStats[] $stats
     * @return array{pv: int, uv: int, shareCount: int, formSubmitCount: int, conversionCount: int, conversionRate: float, bounceRate: float, avgStayDuration: float}
     */
    private function aggregateBasicStats(array $stats): array
    {
        $summary = [
            'pv' => 0,
            'uv' => 0,
            'shareCount' => 0,
            'formSubmitCount' => 0,
            'conversionCount' => 0,
            'conversionRate' => 0.0,
            'bounceRate' => 0.0,
            'avgStayDuration' => 0.0,
        ];

        foreach ($stats as $stat) {
            $summary['pv'] += $stat->getPv();
            $summary['uv'] += $stat->getUv();
            $summary['shareCount'] += $stat->getShareCount();
            $summary['formSubmitCount'] += $stat->getFormSubmitCount();
            $summary['conversionCount'] += $stat->getConversionCount();
        }

        return $summary;
    }

    /**
     * @param array{pv: int, uv: int, shareCount: int, formSubmitCount: int, conversionCount: int, conversionRate: float, bounceRate: float, avgStayDuration: float} $summary
     * @return array{pv: int, uv: int, shareCount: int, formSubmitCount: int, conversionCount: int, conversionRate: float, bounceRate: float, avgStayDuration: float}
     */
    private function calculateConversionRate(array $summary): array
    {
        if ($summary['uv'] > 0) {
            $summary['conversionRate'] = round(($summary['conversionCount'] / $summary['uv']) * 100, 2);
        }

        return $summary;
    }

    /**
     * @param array{pv: int, uv: int, shareCount: int, formSubmitCount: int, conversionCount: int, conversionRate: float, bounceRate: float, avgStayDuration: float} $summary
     * @param ActivityStats[] $stats
     * @return array{pv: int, uv: int, shareCount: int, formSubmitCount: int, conversionCount: int, conversionRate: float, bounceRate: float, avgStayDuration: float}
     */
    private function calculateAverageMetrics(array $summary, array $stats): array
    {
        if (0 === \count($stats)) {
            return $summary;
        }

        $totalBounceRate = array_sum(array_map(fn ($s) => $s->getBounceRate(), $stats));
        $summary['bounceRate'] = round($totalBounceRate / \count($stats), 2);

        $totalDuration = array_sum(array_map(fn ($s) => $s->getStayDuration(), $stats));
        $totalPv = array_sum(array_map(fn ($s) => $s->getPv(), $stats));
        if ($totalPv > 0) {
            $summary['avgStayDuration'] = round($totalDuration / $totalPv, 2);
        }

        return $summary;
    }

    /**
     * 获取趋势数据
     *
     * @return array<array{date: string, pv: int, uv: int, conversionRate: float}>
     */
    public function getTrendData(Activity $activity, int $days = 7): array
    {
        $endDate = new \DateTimeImmutable('today');
        $startDate = $endDate->modify(sprintf('-%d days', $days - 1));
        if (false === $startDate) {
            throw new \RuntimeException('Invalid date modification');
        }

        $stats = $this->statsRepository->findByActivityAndDateRange($activity, $startDate, $endDate);

        $trend = [];
        foreach ($stats as $stat) {
            $trend[] = [
                'date' => $stat->getDate()->format('Y-m-d'),
                'pv' => $stat->getPv(),
                'uv' => $stat->getUv(),
                'conversionRate' => $stat->getConversionRate(),
            ];
        }

        return $trend;
    }

    /**
     * 获取设备分布
     *
     * @return array<string, int>
     */
    public function getDeviceDistribution(Activity $activity): array
    {
        $stats = $this->statsRepository->findByActivity($activity);

        return $this->aggregateStatsDistribution($stats, fn ($stat) => $stat->getDeviceStats());
    }

    /**
     * 获取来源分布
     *
     * @return array<string, int>
     */
    public function getSourceDistribution(Activity $activity): array
    {
        $stats = $this->statsRepository->findByActivity($activity);

        return $this->aggregateStatsDistribution($stats, fn ($stat) => $stat->getSourceStats());
    }

    /**
     * @param ActivityStats[] $stats
     * @param callable(ActivityStats): mixed $getter
     * @return array<string, int>
     */
    private function aggregateStatsDistribution(array $stats, callable $getter): array
    {
        $distribution = [];

        foreach ($stats as $stat) {
            $statsData = $getter($stat) ?? [];
            if (!is_array($statsData)) {
                continue;
            }

            foreach ($statsData as $key => $count) {
                if (!is_string($key) || !is_int($count)) {
                    continue;
                }

                if (!isset($distribution[$key])) {
                    $distribution[$key] = 0;
                }
                $distribution[$key] += $count;
            }
        }

        return $distribution;
    }
}
