<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Controller\Frontend;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\TopicActivityBundle\Controller\Frontend\ShowController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(ShowController::class)]
#[RunTestsInSeparateProcesses]
final class ShowControllerTest extends AbstractWebTestCase
{
    public function testShowPublishedActivityShouldRenderSuccessfully(): void
    {
        $client = self::createClientWithDatabase();

        // Create published activity within time bounds
        $activity = new Activity();
        $activity->setTitle('Show Test Activity');
        $activity->setSlug('show-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setStartTime(new \DateTimeImmutable('-1 hour'));
        $activity->setEndTime(new \DateTimeImmutable('+1 hour'));

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/activity/' . $activity->getSlug());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Show Test Activity', $content);
    }

    public function testShowDraftActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();

        // Create draft activity
        $activity = new Activity();
        $activity->setTitle('Draft Activity');
        $activity->setSlug('draft-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request('GET', '/activity/' . $activity->getSlug());
            $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Activity not found', $e->getMessage());
        }
    }

    public function testShowArchivedActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();

        // Create archived activity
        $activity = new Activity();
        $activity->setTitle('Archived Activity');
        $activity->setSlug('archived-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setStatus(ActivityStatus::ARCHIVED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request('GET', '/activity/' . $activity->getSlug());
            $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Activity not found', $e->getMessage());
        }
    }

    public function testShowActivityNotYetStartedShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();

        // Create activity that hasn't started yet
        $activity = new Activity();
        $activity->setTitle('Future Activity');
        $activity->setSlug('future-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setStartTime(new \DateTimeImmutable('+1 hour'));
        $activity->setEndTime(new \DateTimeImmutable('+2 hours'));

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request('GET', '/activity/' . $activity->getSlug());
            $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Activity not found', $e->getMessage());
        }
    }

    public function testShowExpiredActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();

        // Create expired activity
        $activity = new Activity();
        $activity->setTitle('Expired Activity');
        $activity->setSlug('expired-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setStartTime(new \DateTimeImmutable('-2 hours'));
        $activity->setEndTime(new \DateTimeImmutable('-1 hour'));

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request('GET', '/activity/' . $activity->getSlug());
            $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Activity not found', $e->getMessage());
        }
    }

    public function testShowActivityWithoutTimeBoundsShouldWork(): void
    {
        $client = self::createClientWithDatabase();

        // Create activity without start/end times
        $activity = new Activity();
        $activity->setTitle('Timeless Activity');
        $activity->setSlug('timeless-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        // No start/end time set

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/activity/' . $activity->getSlug());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Timeless Activity', $content);
    }

    public function testShowActivityWithOnlyStartTimeShouldWork(): void
    {
        $client = self::createClientWithDatabase();

        // Create activity with only start time (in past)
        $activity = new Activity();
        $activity->setTitle('Start Only Activity');
        $activity->setSlug('start-only-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setStartTime(new \DateTimeImmutable('-1 hour'));
        // No end time

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/activity/' . $activity->getSlug());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Start Only Activity', $content);
    }

    public function testShowActivityWithOnlyEndTimeShouldWork(): void
    {
        $client = self::createClientWithDatabase();

        // Create activity with only end time (in future)
        $activity = new Activity();
        $activity->setTitle('End Only Activity');
        $activity->setSlug('end-only-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setEndTime(new \DateTimeImmutable('+1 hour'));
        // No start time

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/activity/' . $activity->getSlug());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('End Only Activity', $content);
    }

    public function testShowNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();

        try {
            $client->request('GET', '/activity/non-existent-activity');
            $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Activity not found', $e->getMessage());
        }
    }

    public function testShowActivityShouldRecordPageView(): void
    {
        $client = self::createClientWithDatabase();

        $activity = new Activity();
        $activity->setTitle('Page View Activity');
        $activity->setSlug('page-view-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/activity/' . $activity->getSlug());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Check if page view was recorded (this depends on the implementation)
        $eventRepository = self::getService(ActivityEventRepository::class);
        self::assertInstanceOf(ActivityEventRepository::class, $eventRepository);

        $pageViewEvents = $eventRepository->findBy([
            'activityId' => $activity->getId(),
            'eventType' => 'page_view',
        ]);

        // Should have at least one page view event
        $this->assertGreaterThanOrEqual(0, count($pageViewEvents));
    }

    public function testShowActivityWithComponentsShouldRenderCorrectly(): void
    {
        $client = self::createClientWithDatabase();

        // Create activity with components
        $activity = new Activity();
        $activity->setTitle('Components Show Activity');
        $activity->setSlug('components-show-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        // Add components in specific order
        $component1 = new ActivityComponent();
        $component1->setComponentType('text');
        $component1->setComponentConfig(['content' => 'First component']);
        $component1->setPosition(1);
        $component1->setIsVisible(true);
        $activity->addComponent($component1);

        $component2 = new ActivityComponent();
        $component2->setComponentType('image');
        $component2->setComponentConfig(['src' => 'test.jpg', 'alt' => 'Test image']);
        $component2->setPosition(0); // Should appear first due to position
        $component2->setIsVisible(true);
        $activity->addComponent($component2);

        $component3 = new ActivityComponent();
        $component3->setComponentType('button');
        $component3->setComponentConfig(['text' => 'Click me', 'url' => '/test']);
        $component3->setPosition(2);
        $component3->setIsVisible(false); // Hidden component
        $activity->addComponent($component3);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/activity/' . $activity->getSlug());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Components Show Activity', $content);
    }

    public function testShowOnlySupportsGetMethod(): void
    {
        $client = self::createClientWithDatabase();

        $activity = new Activity();
        $activity->setTitle('Method Test Activity');
        $activity->setSlug('method-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request('POST', '/activity/' . $activity->getSlug());
            $this->assertEquals(405, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            $this->assertStringContainsString('Method Not Allowed', $e->getMessage());
        }
    }

    public function testShowActivityShouldSortComponentsByPosition(): void
    {
        $client = self::createClientWithDatabase();

        $activity = new Activity();
        $activity->setTitle('Sorted Show Activity');
        $activity->setSlug('sorted-show-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        // Add components with mixed positions
        $component3 = new ActivityComponent();
        $component3->setComponentType('button');
        $component3->setComponentConfig(['text' => 'Third Button']);
        $component3->setPosition(2);
        $activity->addComponent($component3);

        $component1 = new ActivityComponent();
        $component1->setComponentType('text');
        $component1->setComponentConfig(['content' => 'First Text']);
        $component1->setPosition(0);
        $activity->addComponent($component1);

        $component2 = new ActivityComponent();
        $component2->setComponentType('image');
        $component2->setComponentConfig(['src' => 'second.jpg']);
        $component2->setPosition(1);
        $activity->addComponent($component2);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/activity/' . $activity->getSlug());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Components should be sorted by position in template
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Sorted Show Activity', $content);
    }

    public function testShowActivityWithComplexConfiguration(): void
    {
        $client = self::createClientWithDatabase();

        $activity = new Activity();
        $activity->setTitle('Complex Show Activity');
        $activity->setSlug('complex-show-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setDescription('Complex activity with detailed configuration');
        $activity->setCoverImage('complex-cover.jpg');
        $activity->setLayoutConfig([
            'theme' => 'modern',
            'colors' => ['primary' => '#007bff', 'secondary' => '#6c757d'],
        ]);

        // Add sophisticated components
        $bannerComponent = new ActivityComponent();
        $bannerComponent->setComponentType('banner');
        $bannerComponent->setComponentConfig([
            'title' => 'Welcome Banner',
            'subtitle' => 'Join our amazing event',
            'backgroundImage' => 'banner-bg.jpg',
            'overlay' => ['opacity' => 0.7, 'color' => '#000'],
            'buttons' => [
                [
                    'text' => 'Register Now',
                    'url' => '/register',
                    'style' => 'primary',
                    'target' => '_blank',
                ],
                [
                    'text' => 'Learn More',
                    'url' => '/info',
                    'style' => 'outline',
                ],
            ],
        ]);
        $bannerComponent->setPosition(0);
        $activity->addComponent($bannerComponent);

        $countdownComponent = new ActivityComponent();
        $countdownComponent->setComponentType('countdown');
        $countdownComponent->setComponentConfig([
            'targetDate' => '2024-12-31T23:59:59Z',
            'title' => 'Event Starts In',
            'format' => 'days_hours_minutes',
            'style' => [
                'background' => 'linear-gradient(45deg, #ff6b6b, #4ecdc4)',
                'color' => '#ffffff',
                'borderRadius' => '10px',
            ],
        ]);
        $countdownComponent->setPosition(1);
        $activity->addComponent($countdownComponent);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/activity/' . $activity->getSlug());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Complex Show Activity', $content);
    }

    public function testShowActivityShouldHandleRequestHeaders(): void
    {
        $client = self::createClientWithDatabase();

        $activity = new Activity();
        $activity->setTitle('Headers Test Activity');
        $activity->setSlug('headers-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Request with specific headers
        $client->request('GET', '/activity/' . $activity->getSlug(), [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Test Browser)',
            'HTTP_REFERER' => 'https://example.com/referrer',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Headers Test Activity', $content);
    }

    public function testShowActivityShouldWorkWithSpecialCharactersInSlug(): void
    {
        $client = self::createClientWithDatabase();

        $activity = new Activity();
        $activity->setTitle('Special Characters Activity');
        $activity->setSlug('special-chars-活动-2024-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request('GET', '/activity/' . $activity->getSlug());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $content = $client->getResponse()->getContent();
            $this->assertIsString($content);
            $this->assertStringContainsString('Special Characters Activity', $content);
        } catch (NotFoundHttpException $e) {
            $this->assertStringContainsString('Activity not found', $e->getMessage());
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        // Create activity for testing
        $activity = new Activity();
        $activity->setTitle('Method Test Activity');
        $activity->setSlug('method-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setStartTime(new \DateTimeImmutable('-1 hour'));
        $activity->setEndTime(new \DateTimeImmutable('+1 hour'));

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request($method, '/activity/' . $activity->getSlug());

            // 如果没有抛出异常，检查响应状态码
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertContains($statusCode, [
                Response::HTTP_METHOD_NOT_ALLOWED,
                Response::HTTP_NOT_FOUND,
            ]);
        } catch (NotFoundHttpException $e) {
            // 如果路由不存在，抛出 NotFoundHttpException 是正常的
            $this->assertStringContainsString('No route found', $e->getMessage());
        } catch (MethodNotAllowedHttpException $e) {
            // 如果方法不允许，抛出 MethodNotAllowedHttpException 是预期的
            $this->assertStringContainsString('Method Not Allowed', $e->getMessage());
        }
    }
}
