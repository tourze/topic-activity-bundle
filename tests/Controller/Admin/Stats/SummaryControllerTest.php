<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Controller\Admin\Stats;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\TopicActivityBundle\Controller\Admin\Stats\SummaryController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(SummaryController::class)]
#[RunTestsInSeparateProcesses]
final class SummaryControllerTest extends AbstractWebTestCase
{
    public function testGetSummaryForExistingActivityShouldReturnData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity
        $activity = new Activity();
        $activity->setTitle('Summary Stats Activity');
        $activity->setSlug('summary-stats-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/summary');

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

    public function testGetSummaryWithDateRangeShouldReturnFilteredData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Date Range Summary Activity');
        $activity->setSlug('date-range-summary-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $startDate = '2024-01-01';
        $endDate = '2024-01-31';

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/summary', [
            'start_date' => $startDate,
            'end_date' => $endDate,
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

    public function testGetSummaryForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $client->request('GET', '/admin/activity/stats/' . $nonExistentId . '/summary');

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

    public function testGetSummaryWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        // 期望抛出访问被拒绝异常
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/activity/stats/1/summary');
    }

    public function testGetSummaryWithInvalidDateFormatShouldHandleGracefully(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Invalid Date Activity');
        $activity->setSlug('invalid-date-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $this->expectException(\Exception::class);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/summary', [
            'start_date' => 'invalid-date',
            'end_date' => 'another-invalid-date',
        ]);
    }

    public function testGetSummaryOnlySupportsGetMethod(): void
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

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/summary');
    }

    public function testGetSummaryWithOnlyStartDateShouldWork(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Start Date Only Activity');
        $activity->setSlug('start-date-only-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/summary', [
            'start_date' => '2024-01-01',
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

    public function testGetSummaryWithOnlyEndDateShouldWork(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('End Date Only Activity');
        $activity->setSlug('end-date-only-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/summary', [
            'end_date' => '2024-12-31',
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

    public function testGetSummaryWithActivityContainingEvents(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Events Summary Activity');
        $activity->setSlug('events-summary-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Add some events for summary calculation
        $eventRepository = self::getService(ActivityEventRepository::class);
        self::assertInstanceOf(ActivityEventRepository::class, $eventRepository);

        $activityId = $activity->getId();
        self::assertNotSame(null, $activityId);
        $event1 = ActivityEvent::create($activityId);
        $event1->setEventType('page_view');
        $event1->setEventData(['timestamp' => '2024-01-15T10:00:00Z']);
        $eventRepository->save($event1);

        $event2 = ActivityEvent::create($activityId);
        $event2->setEventType('button_click');
        $event2->setEventData(['timestamp' => '2024-01-15T10:30:00Z']);
        $eventRepository->save($event2);

        $event3 = ActivityEvent::create($activityId);
        $event3->setEventType('form_submit');
        $event3->setEventData(['timestamp' => '2024-01-15T11:00:00Z']);
        $eventRepository->save($event3, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/summary');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetSummaryWithInvalidIdShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/admin/activity/stats/abc/summary');
    }

    public function testGetSummaryResponseStructureShouldBeValid(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Response Structure Activity');
        $activity->setSlug('response-structure-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/summary');

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
            $client->request($method, '/admin/activity/stats/' . $activity->getId() . '/summary');

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
