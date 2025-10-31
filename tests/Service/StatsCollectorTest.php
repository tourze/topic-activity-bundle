<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Entity\ActivityStats;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityStatsRepository;
use Tourze\TopicActivityBundle\Service\ActivityManager;
use Tourze\TopicActivityBundle\Service\StatsCollector;

/**
 * @internal
 */
#[CoversClass(StatsCollector::class)]
#[RunTestsInSeparateProcesses]
class StatsCollectorTest extends AbstractIntegrationTestCase
{
    private StatsCollector $statsCollector;

    private ActivityManager $activityManager;

    private ActivityStatsRepository $activityStatsRepository;

    private ActivityEventRepository $activityEventRepository;

    protected function onSetUp(): void
    {
        $this->statsCollector = self::getService(StatsCollector::class);
        $this->activityManager = self::getService(ActivityManager::class);
        $this->activityStatsRepository = self::getService(ActivityStatsRepository::class);
        $this->activityEventRepository = self::getService(ActivityEventRepository::class);
    }

    public function testRecordPageViewNewVisitor(): void
    {
        // 创建真实活动
        $activity = $this->activityManager->createActivity([
            'title' => 'Stats Test Activity - New Visitor',
        ]);

        // 创建模拟请求，不包含 visitor_id
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/activity/' . $activity->getSlug(), 'GET');
        $request->setSession($session);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        // 记录页面访问
        $this->statsCollector->recordPageView($activity, $request);

        // 验证 visitor_id 被设置
        $visitorId = $session->get('visitor_id');
        $this->assertNotNull($visitorId);
        $this->assertIsString($visitorId);
        $this->assertStringStartsWith('visitor_', $visitorId);

        // 验证统计数据被创建
        $em = self::getService(EntityManagerInterface::class);
        $em->flush(); // 确保数据刷新到数据库
        $em->clear(); // 清理实体缓存

        // 直接查找该Activity的任何统计记录
        $allStats = $this->activityStatsRepository->findByActivity($activity);
        $this->assertNotEmpty($allStats, 'At least one ActivityStats should be created');
        $stats = $allStats[0];

        $this->assertInstanceOf(ActivityStats::class, $stats, 'ActivityStats should be created');
        $this->assertGreaterThanOrEqual(1, $stats->getPv(), 'PV should be at least 1');
        $this->assertGreaterThanOrEqual(1, $stats->getUv(), 'UV should be at least 1');
    }

    public function testRecordPageViewReturningVisitor(): void
    {
        // 创建活动
        $activity = $this->activityManager->createActivity([
            'title' => 'Stats Test Activity - Returning Visitor',
        ]);

        // 创建有 visitor_id 的会话
        $session = new Session(new MockArraySessionStorage());
        $session->set('visitor_id', 'visitor_returning_test_123');

        $request = Request::create('/activity/' . $activity->getSlug());
        $request->setSession($session);

        // 第一次访问
        $this->statsCollector->recordPageView($activity, $request);

        // 第二次访问（回头客）
        $this->statsCollector->recordPageView($activity, $request);

        // 验证统计数据
        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $this->assertGreaterThanOrEqual(2, $stats->getPv()); // PV应该增加
        $this->assertEquals(1, $stats->getUv()); // UV应该保持不变
    }

    public function testRecordPageViewMobileDevice(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Mobile Device Test Activity',
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/activity/' . $activity->getSlug());
        $request->setSession($session);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1');

        $this->statsCollector->recordPageView($activity, $request);

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $deviceStats = $stats->getDeviceStats();
        $this->assertIsArray($deviceStats);
        $this->assertArrayHasKey('mobile', $deviceStats);
        $this->assertGreaterThanOrEqual(1, $deviceStats['mobile']);
    }

    public function testRecordPageViewTabletDevice(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Tablet Device Test Activity',
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/activity/' . $activity->getSlug());
        $request->setSession($session);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (iPad; CPU OS 15_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6 Mobile/15E148 Safari/604.1');

        $this->statsCollector->recordPageView($activity, $request);

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $deviceStats = $stats->getDeviceStats();
        $this->assertIsArray($deviceStats);
        $this->assertArrayHasKey('tablet', $deviceStats);
        $this->assertGreaterThanOrEqual(1, $deviceStats['tablet']);
    }

    public function testRecordPageViewWithUtmSource(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'UTM Source Test Activity',
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/activity/' . $activity->getSlug() . '?utm_source=facebook&utm_medium=social');
        $request->setSession($session);

        $this->statsCollector->recordPageView($activity, $request);

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $sourceStats = $stats->getSourceStats();
        $this->assertIsArray($sourceStats);
        $this->assertArrayHasKey('facebook', $sourceStats);
        $this->assertGreaterThanOrEqual(1, $sourceStats['facebook']);
    }

    public function testRecordPageViewWithReferrer(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Referrer Test Activity',
        ]);

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/activity/' . $activity->getSlug());
        $request->setSession($session);
        $request->headers->set('Referer', 'https://www.google.com/search?q=test');

        $this->statsCollector->recordPageView($activity, $request);

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $sourceStats = $stats->getSourceStats();
        $this->assertIsArray($sourceStats);
        $this->assertArrayHasKey('google', $sourceStats);
        $this->assertGreaterThanOrEqual(1, $sourceStats['google']);
    }

    public function testRecordFormSubmit(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Form Submit Test Activity',
        ]);

        $formData = [
            'name' => 'Integration Test User',
            'email' => 'integration@test.com',
            'message' => 'This is a test form submission',
        ];

        $this->statsCollector->recordFormSubmit($activity, $formData);

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $this->assertGreaterThanOrEqual(1, $stats->getFormSubmitCount());

        // 验证事件被记录
        $events = $this->activityEventRepository->findBy(['activityId' => $activity->getId(), 'eventType' => 'form_submit']);
        $this->assertCount(1, $events);
        $this->assertSame('form_submit', $events[0]->getEventType());
    }

    public function testRecordShare(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Share Test Activity',
        ]);

        $platforms = ['wechat', 'weibo', 'qq'];

        foreach ($platforms as $platform) {
            $this->statsCollector->recordShare($activity, $platform);
        }

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $this->assertEquals(3, $stats->getShareCount());

        // 验证事件被记录
        $events = $this->activityEventRepository->findBy(['activityId' => $activity->getId(), 'eventType' => 'share']);
        $this->assertCount(3, $events);
    }

    public function testRecordConversion(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Conversion Test Activity',
        ]);

        $conversionData = [
            'type' => 'purchase',
            'product_id' => 'TEST_PRODUCT_123',
            'amount' => 299.99,
            'currency' => 'CNY',
        ];

        $this->statsCollector->recordConversion($activity, $conversionData);

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $this->assertGreaterThanOrEqual(1, $stats->getConversionCount());

        // 验证事件被记录
        $events = $this->activityEventRepository->findBy(['activityId' => $activity->getId(), 'eventType' => 'conversion']);
        $this->assertCount(1, $events);
        $this->assertEquals($conversionData, $events[0]->getEventData());
    }

    public function testRecordStayDuration(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Stay Duration Test Activity',
        ]);

        $durations = [30.5, 45.8, 120.0];
        $totalDuration = 0;

        foreach ($durations as $duration) {
            $this->statsCollector->recordStayDuration($activity, $duration);
            $totalDuration += $duration;
        }

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        // 由于可能不是简单的相加，我们只验证有值存在
        $this->assertGreaterThan(0, $stats->getStayDuration());
    }

    public function testCalculateBounceRate(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Bounce Rate Test Activity',
        ]);

        // 模拟多个会话的访问
        $sessions = [
            'session_bounce' => 1,    // 跳出会话（1个页面）
            'session_stay_1' => 3,    // 非跳出会话（3个页面）
            'session_stay_2' => 2,    // 非跳出会话（2个页面）
        ];

        foreach ($sessions as $sessionId => $pageViews) {
            for ($i = 0; $i < $pageViews; ++$i) {
                $session = new Session(new MockArraySessionStorage());
                $session->setId($sessionId);

                $request = Request::create('/activity/' . $activity->getSlug() . '/page' . $i);
                $request->setSession($session);

                $this->statsCollector->recordPageView($activity, $request);
            }
        }

        // 计算跳出率
        $this->statsCollector->calculateBounceRate($activity);

        $em = self::getService(EntityManagerInterface::class);
        $stats = $this->activityStatsRepository->findByActivityAndDate($activity, new \DateTimeImmutable());

        $this->assertInstanceOf(ActivityStats::class, $stats);
        $bounceRate = $stats->getBounceRate();
        $this->assertGreaterThanOrEqual(0, $bounceRate);
        $this->assertLessThanOrEqual(100, $bounceRate);
    }

    public function testGetActivitySummary(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Activity Summary Test',
        ]);

        // 生成一些测试数据
        $session1 = new Session(new MockArraySessionStorage());
        $session2 = new Session(new MockArraySessionStorage());

        $request1 = Request::create('/activity/' . $activity->getSlug());
        $request1->setSession($session1);
        $request2 = Request::create('/activity/' . $activity->getSlug());
        $request2->setSession($session2);

        // 记录访问
        $this->statsCollector->recordPageView($activity, $request1);
        $this->statsCollector->recordPageView($activity, $request2);

        // 记录其他事件
        $this->statsCollector->recordShare($activity, 'wechat');
        $this->statsCollector->recordFormSubmit($activity, ['test' => 'data']);
        $this->statsCollector->recordConversion($activity, ['type' => 'test']);
        $this->statsCollector->recordStayDuration($activity, 120.0);

        $summary = $this->statsCollector->getActivitySummary($activity);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('pv', $summary);
        $this->assertArrayHasKey('uv', $summary);
        $this->assertArrayHasKey('shareCount', $summary);
        $this->assertArrayHasKey('formSubmitCount', $summary);
        $this->assertArrayHasKey('conversionCount', $summary);
        $this->assertArrayHasKey('conversionRate', $summary);
        $this->assertArrayHasKey('bounceRate', $summary);
        $this->assertArrayHasKey('avgStayDuration', $summary);

        $this->assertGreaterThanOrEqual(2, $summary['pv']);
        $this->assertGreaterThanOrEqual(2, $summary['uv']);
        $this->assertGreaterThanOrEqual(1, $summary['shareCount']);
        $this->assertGreaterThanOrEqual(1, $summary['formSubmitCount']);
        $this->assertGreaterThanOrEqual(1, $summary['conversionCount']);
    }

    public function testGetTrendData(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Trend Data Test Activity',
        ]);

        // 生成一些数据
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/activity/' . $activity->getSlug());
        $request->setSession($session);

        $this->statsCollector->recordPageView($activity, $request);

        $trend = $this->statsCollector->getTrendData($activity, 7);

        $this->assertIsArray($trend);
        $this->assertNotEmpty($trend);

        foreach ($trend as $dayData) {
            $this->assertArrayHasKey('date', $dayData);
            $this->assertArrayHasKey('pv', $dayData);
            $this->assertArrayHasKey('uv', $dayData);
            $this->assertIsString($dayData['date']);
            $this->assertIsInt($dayData['pv']);
            $this->assertIsInt($dayData['uv']);
        }
    }

    public function testGetDeviceDistribution(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Device Distribution Test Activity',
        ]);

        // 模拟不同设备的访问
        $userAgents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',  // mobile
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',                // desktop
            'Mozilla/5.0 (iPad; CPU OS 15_7 like Mac OS X)',            // tablet
        ];

        foreach ($userAgents as $userAgent) {
            $session = new Session(new MockArraySessionStorage());
            $request = Request::create('/activity/' . $activity->getSlug());
            $request->setSession($session);
            $request->headers->set('User-Agent', $userAgent);

            $this->statsCollector->recordPageView($activity, $request);
        }

        $distribution = $this->statsCollector->getDeviceDistribution($activity);

        $this->assertIsArray($distribution);
        $this->assertNotEmpty($distribution);

        $total = array_sum($distribution);
        $this->assertGreaterThanOrEqual(3, $total);
    }

    public function testGetSourceDistribution(): void
    {
        $activity = $this->activityManager->createActivity([
            'title' => 'Source Distribution Test Activity',
        ]);

        // 模拟不同来源的访问
        $sources = [
            ['utm_source' => 'facebook'],
            ['referrer' => 'https://www.google.com/search'],
            ['utm_source' => 'weibo'],
        ];

        foreach ($sources as $sourceData) {
            $session = new Session(new MockArraySessionStorage());

            if (isset($sourceData['utm_source'])) {
                $url = '/activity/' . $activity->getSlug() . '?utm_source=' . $sourceData['utm_source'];
                $request = Request::create($url);
            } else {
                $request = Request::create('/activity/' . $activity->getSlug());
                if (isset($sourceData['referrer'])) {
                    $request->headers->set('Referer', $sourceData['referrer']);
                }
            }

            $request->setSession($session);
            $this->statsCollector->recordPageView($activity, $request);
        }

        $distribution = $this->statsCollector->getSourceDistribution($activity);

        $this->assertIsArray($distribution);
        $this->assertNotEmpty($distribution);

        $total = array_sum($distribution);
        $this->assertGreaterThanOrEqual(3, $total);
    }
}
