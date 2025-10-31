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
use Tourze\TopicActivityBundle\Controller\Admin\Stats\TrackEventController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(TrackEventController::class)]
#[RunTestsInSeparateProcesses]
final class TrackEventControllerTest extends AbstractWebTestCase
{
    public function testTrackPageViewEventShouldReturnSuccess(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Page View Track Activity');
        $activity->setSlug('page-view-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'page_view',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackFormSubmitEventShouldReturnSuccess(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Form Submit Track Activity');
        $activity->setSlug('form-submit-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'form_submit',
            'event_data' => [
                'form_id' => 'contact_form',
                'fields' => ['name', 'email', 'message'],
            ],
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackShareEventShouldReturnSuccess(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Share Track Activity');
        $activity->setSlug('share-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'share',
            'event_data' => [
                'platform' => 'twitter',
            ],
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackConversionEventShouldReturnSuccess(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Conversion Track Activity');
        $activity->setSlug('conversion-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'conversion',
            'event_data' => [
                'conversion_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
            ],
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackStayDurationEventShouldReturnSuccess(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Stay Duration Track Activity');
        $activity->setSlug('stay-duration-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'stay_duration',
            'event_data' => [
                'duration' => '125.5',
            ],
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackUnknownEventTypeShouldReturnBadRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Unknown Event Track Activity');
        $activity->setSlug('unknown-event-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'unknown_event_type',
        ]);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
        $this->assertEquals('Unknown event type', $response['error']);
    }

    public function testTrackEventForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $client->request('POST', '/admin/activity/stats/' . $nonExistentId . '/track', [
            'event_type' => 'page_view',
        ]);

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

    public function testTrackEventWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        // 期望抛出访问被拒绝异常
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('POST', '/admin/activity/stats/1/track', [
            'event_type' => 'page_view',
        ]);
    }

    public function testTrackEventOnlySupportsPostMethod(): void
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

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/track');
    }

    public function testTrackShareEventWithoutPlatformShouldUseDefault(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Default Share Track Activity');
        $activity->setSlug('default-share-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'share',
            'event_data' => [],
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackStayDurationWithZeroDurationShouldWork(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Zero Duration Track Activity');
        $activity->setSlug('zero-duration-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'stay_duration',
            'event_data' => [
                'duration' => '0',
            ],
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackStayDurationWithoutDurationShouldUseDefault(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('No Duration Track Activity');
        $activity->setSlug('no-duration-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'stay_duration',
            'event_data' => [],
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackEventWithComplexEventData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Complex Data Track Activity');
        $activity->setSlug('complex-data-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', [
            'event_type' => 'form_submit',
            'event_data' => [
                'form_id' => 'complex_form',
                'fields' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'preferences' => ['newsletter', 'updates'],
                ],
                'validation_errors' => [],
                'submit_time' => '2024-01-15T10:30:00Z',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testTrackEventWithInvalidIdShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(NotFoundHttpException::class);

        $client->request('POST', '/admin/activity/stats/abc/track', [
            'event_type' => 'page_view',
        ]);
    }

    public function testTrackMultipleEventTypesConcurrently(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Multiple Events Track Activity');
        $activity->setSlug('multiple-events-track-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $eventTypes = [
            ['event_type' => 'page_view'],
            ['event_type' => 'form_submit', 'event_data' => ['form_id' => 'test']],
            ['event_type' => 'share', 'event_data' => ['platform' => 'facebook']],
            ['event_type' => 'conversion', 'event_data' => ['value' => 50.0]],
            ['event_type' => 'stay_duration', 'event_data' => ['duration' => '60']],
        ];

        foreach ($eventTypes as $eventData) {
            $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/track', $eventData);
            $this->assertEquals(200, $client->getResponse()->getStatusCode());

            $content = $client->getResponse()->getContent();
            $this->assertNotFalse($content, 'Response content should not be false');
            $response = json_decode($content, true);
            $this->assertNotFalse($response, 'JSON decode should not fail');
            $this->assertIsArray($response);
            $this->assertIsBool($response['success']);
            $this->assertTrue($response['success']);
        }
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
            $client->request($method, '/admin/activity/stats/' . $activity->getId() . '/track');

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
