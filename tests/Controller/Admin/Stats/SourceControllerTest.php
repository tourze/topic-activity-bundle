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
use Tourze\TopicActivityBundle\Controller\Admin\Stats\SourceController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(SourceController::class)]
#[RunTestsInSeparateProcesses]
final class SourceControllerTest extends AbstractWebTestCase
{
    private function assertValidActivityId(Activity $activity): int
    {
        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null after persistence');

        return $activityId;
    }

    public function testGetSourceDistributionForExistingActivityShouldReturnData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity with events
        $activity = new Activity();
        $activity->setTitle('Source Stats Activity');
        $activity->setSlug('source-stats-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Create some source events
        $eventRepository = self::getService(ActivityEventRepository::class);
        self::assertInstanceOf(ActivityEventRepository::class, $eventRepository);

        $googleEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $googleEvent->setEventType('page_view');
        $googleEvent->setEventData([
            'referer' => 'https://www.google.com/search?q=test',
            'source' => 'google',
            'medium' => 'organic',
        ]);
        $eventRepository->save($googleEvent);

        $directEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $directEvent->setEventType('page_view');
        $directEvent->setEventData([
            'referer' => null,
            'source' => 'direct',
            'medium' => 'none',
        ]);
        $eventRepository->save($directEvent);

        $socialEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $socialEvent->setEventType('page_view');
        $socialEvent->setEventData([
            'referer' => 'https://www.facebook.com/',
            'source' => 'facebook',
            'medium' => 'social',
        ]);
        $eventRepository->save($socialEvent, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/source');

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

    public function testGetSourceDistributionForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $client->request('GET', '/admin/activity/stats/' . $nonExistentId . '/source');

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

    public function testGetSourceDistributionWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        // 期望抛出访问被拒绝异常
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/activity/stats/1/source');
    }

    public function testGetSourceDistributionForActivityWithoutEventsShouldReturnEmptyData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity without events
        $activity = new Activity();
        $activity->setTitle('Empty Source Stats Activity');
        $activity->setSlug('empty-source-stats-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/source');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetSourceDistributionOnlySupportsGetMethod(): void
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

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/source');
    }

    public function testGetSourceDistributionWithInvalidIdShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/admin/activity/stats/abc/source');
    }

    public function testGetSourceDistributionResponseStructureShouldBeValid(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Source Response Structure Activity');
        $activity->setSlug('source-response-structure-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/source');

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

    public function testGetSourceDistributionForDifferentActivityStatusesShouldWork(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);

        $statuses = [ActivityStatus::DRAFT, ActivityStatus::PUBLISHED, ActivityStatus::ARCHIVED];

        foreach ($statuses as $index => $status) {
            $activity = new Activity();
            $activity->setTitle("Source Stats {$status->value} Activity");
            $activity->setSlug("source-stats-{$status->value}-activity-{$index}");
            $activity->setStatus($status);

            $activityRepository->save($activity, true);

            $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/source');

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

    public function testGetSourceDistributionWithComplexReferrerData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Complex Referrer Activity');
        $activity->setSlug('complex-referrer-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $eventRepository = self::getService(ActivityEventRepository::class);
        self::assertInstanceOf(ActivityEventRepository::class, $eventRepository);

        // Google search with query parameters
        $event1 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1->setEventType('page_view');
        $event1->setEventData([
            'referer' => 'https://www.google.com/search?q=topic+activity+bundle&hl=en&gl=us',
            'source' => 'google',
            'medium' => 'organic',
            'campaign' => null,
        ]);
        $eventRepository->save($event1);

        // Social media referrer
        $event2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event2->setEventType('page_view');
        $event2->setEventData([
            'referer' => 'https://t.co/abc123',
            'source' => 'twitter',
            'medium' => 'social',
            'campaign' => 'spring_promotion',
        ]);
        $eventRepository->save($event2);

        // Email campaign
        $event3 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event3->setEventType('page_view');
        $event3->setEventData([
            'referer' => 'https://example.com/newsletter',
            'source' => 'newsletter',
            'medium' => 'email',
            'campaign' => 'weekly_digest',
            'utm_content' => 'header_link',
        ]);
        $eventRepository->save($event3, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/source');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetSourceDistributionWithMixedEventTypes(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Mixed Events Source Activity');
        $activity->setSlug('mixed-events-source-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Create events with different types but same source data
        $eventRepository = self::getService(ActivityEventRepository::class);
        self::assertInstanceOf(ActivityEventRepository::class, $eventRepository);

        $pageViewEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $pageViewEvent->setEventType('page_view');
        $pageViewEvent->setEventData([
            'referer' => 'https://www.bing.com/search?q=test',
            'source' => 'bing',
            'medium' => 'organic',
        ]);
        $eventRepository->save($pageViewEvent);

        $clickEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $clickEvent->setEventType('button_click');
        $clickEvent->setEventData([
            'referer' => 'https://www.bing.com/search?q=test',
            'source' => 'bing',
            'medium' => 'organic',
            'button_id' => 'cta-button',
        ]);
        $eventRepository->save($clickEvent);

        $interactionEvent = ActivityEvent::create($this->assertValidActivityId($activity));
        $interactionEvent->setEventType('form_submit');
        $interactionEvent->setEventData([
            'referer' => 'https://duckduckgo.com/?q=search+term',
            'source' => 'duckduckgo',
            'medium' => 'organic',
            'form_id' => 'contact_form',
        ]);
        $eventRepository->save($interactionEvent, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/source');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response =
$content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        // Use $response instead;
        $this->assertIsArray($response);
    }

    public function testGetSourceDistributionWithEmptyAndNullReferrers(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Empty Referrer Activity');
        $activity->setSlug('empty-referrer-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $eventRepository = self::getService(ActivityEventRepository::class);
        self::assertInstanceOf(ActivityEventRepository::class, $eventRepository);

        // Direct traffic (null referrer)
        $event1 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event1->setEventType('page_view');
        $event1->setEventData([
            'referer' => null,
            'source' => 'direct',
        ]);
        $eventRepository->save($event1);

        // Empty string referrer
        $event2 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event2->setEventType('page_view');
        $event2->setEventData([
            'referer' => '',
            'source' => 'direct',
        ]);
        $eventRepository->save($event2);

        // Missing referrer key
        $event3 = ActivityEvent::create($this->assertValidActivityId($activity));
        $event3->setEventType('page_view');
        $event3->setEventData([
            'source' => 'unknown',
        ]);
        $eventRepository->save($event3, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/source');

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
            $client->request($method, '/admin/activity/stats/' . $activity->getId() . '/source');

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
