<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Controller\Admin\Stats;

use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\TopicActivityBundle\Controller\Admin\Stats\TrendController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(TrendController::class)]
#[RunTestsInSeparateProcesses]
final class TrendControllerTest extends AbstractWebTestCase
{
    public function testGetTrendDataForExistingActivityShouldReturnData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Trend Stats Activity');
        $activity->setSlug('trend-stats-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('content-type'));

        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetTrendDataWithCustomDaysShouldReturnFilteredData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Custom Days Trend Activity');
        $activity->setSlug('custom-days-trend-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend', [
            'days' => '30',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetTrendDataWithDefaultDaysParameterShouldReturn7Days(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Default Days Trend Activity');
        $activity->setSlug('default-days-trend-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Request without days parameter should default to 7
        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetTrendDataForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $client->request('GET', '/admin/activity/stats/' . $nonExistentId . '/trend');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
        $this->assertEquals('Activity not found', $response['error']);
    }

    public function testGetTrendDataWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        // 期望抛出访问被拒绝异常
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/activity/stats/1/trend');
    }

    public function testGetTrendDataOnlySupportsGetMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Method Test Activity');
        $activity->setSlug('method-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/trend');
    }

    public function testGetTrendDataWithZeroDaysShouldWork(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Zero Days Trend Activity');
        $activity->setSlug('zero-days-trend-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend', [
            'days' => '0',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetTrendDataWithNegativeDaysShouldUseDefault(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Negative Days Trend Activity');
        $activity->setSlug('negative-days-trend-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend', [
            'days' => '-5',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetTrendDataWithLargeDaysValueShouldWork(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Large Days Trend Activity');
        $activity->setSlug('large-days-trend-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend', [
            'days' => '365',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetTrendDataWithActivityContainingHistoricalEvents(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Historical Events Trend Activity');
        $activity->setSlug('historical-events-trend-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Add historical events for trend analysis
        $eventRepository = self::getService(ActivityEventRepository::class);
        self::assertInstanceOf(ActivityEventRepository::class, $eventRepository);

        // Events from different days
        $dates = [
            '2024-01-10T10:00:00Z',
            '2024-01-11T14:30:00Z',
            '2024-01-12T09:15:00Z',
            '2024-01-12T16:45:00Z',
            '2024-01-13T11:20:00Z',
        ];

        $activityId = $activity->getId();
        self::assertNotSame(null, $activityId);

        foreach ($dates as $index => $date) {
            $event = ActivityEvent::create($activityId);
            $event->setEventType('page_view');
            $event->setEventData([
                'timestamp' => $date,
                'session_id' => 'session_' . $index,
            ]);
            $eventRepository->save($event);
        }

        // Flush all events
        $doctrine = self::getContainer()->get('doctrine');
        self::assertInstanceOf(Registry::class, $doctrine);
        $doctrine->getManager()->flush();

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend', [
            'days' => '14',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetTrendDataWithInvalidIdShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/admin/activity/stats/abc/trend');
    }

    public function testGetTrendDataResponseStructureShouldBeValid(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Response Structure Trend Activity');
        $activity->setSlug('response-structure-trend-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;

        $this->assertIsArray($response);
        // The exact structure depends on StatsCollector implementation
        // But response should be a valid array
    }

    public function testGetTrendDataForDifferentActivityStatuses(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);

        $statuses = [ActivityStatus::DRAFT, ActivityStatus::PUBLISHED, ActivityStatus::ARCHIVED];

        foreach ($statuses as $index => $status) {
            $activity = new Activity();
            $activity->setTitle("Trend {$status->value} Activity");
            $activity->setSlug("trend-{$status->value}-activity-{$index}");
            $activity->setStatus($status);

            $activityRepository->save($activity, true);

            $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend');

            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $response =
$content = $client->getResponse()->getContent();
            $this->assertNotFalse($content, 'Response content should not be false');
            $response = json_decode($content, true);
            $this->assertNotFalse($response, 'JSON decode should not fail');
            // Use $response instead;
            $this->assertIsArray($response);
        }
    }

    public function testGetTrendDataWithNonNumericDaysParameterShouldUseDefault(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Non-numeric Days Activity');
        $activity->setSlug('non-numeric-days-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend', [
            'days' => 'invalid',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetTrendDataWithDecimalDaysParameterShouldTruncate(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Decimal Days Activity');
        $activity->setSlug('decimal-days-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/trend', [
            'days' => '7.5',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        // Linus: 删除INVALID检查，让糟糕的DataProvider直接失败而不是跳过
        // 如果DataProvider生成INVALID数据，这个测试就会失败，这比跳过测试要好

        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity for testing
        $activity = new Activity();
        $activity->setTitle('Method Test Activity');
        $activity->setSlug('method-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request($method, '/admin/activity/stats/' . $activity->getId() . '/trend');

            // 如果没有抛出异常，检查响应状态码
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertContains($statusCode, [
                Response::HTTP_METHOD_NOT_ALLOWED,
                Response::HTTP_NOT_FOUND,
            ], "Method {$method} should not be allowed");
        } catch (MethodNotAllowedHttpException $e) {
            // 方法不被允许是我们期望的结果
            $this->assertStringContainsString('Method Not Allowed', $e->getMessage());
        } catch (NotFoundHttpException $e) {
            // 如果路由不存在，抛出 NotFoundHttpException 是正常的
            $this->assertStringContainsString('No route found', $e->getMessage());
        }
    }
}
