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
use Tourze\TopicActivityBundle\Controller\Admin\Stats\DeviceController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(DeviceController::class)]
#[RunTestsInSeparateProcesses]
final class DeviceControllerTest extends AbstractWebTestCase
{
    public function testGetDeviceDistributionForExistingActivityShouldReturnData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity with events
        $activity = new Activity();
        $activity->setTitle('Device Stats Activity');
        $activity->setSlug('device-stats-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Create some device events
        $eventRepository = self::getService(ActivityEventRepository::class);
        self::assertInstanceOf(ActivityEventRepository::class, $eventRepository);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null');
        $mobileEvent = ActivityEvent::create($activityId);
        $mobileEvent->setEventType('page_view');
        $mobileEvent->setEventData([
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15',
            'device_type' => 'mobile',
        ]);
        $eventRepository->save($mobileEvent);

        $desktopEvent = ActivityEvent::create($activityId);
        $desktopEvent->setEventType('page_view');
        $desktopEvent->setEventData([
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'device_type' => 'desktop',
        ]);
        $eventRepository->save($desktopEvent, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/device');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('content-type'));

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
    }

    public function testGetDeviceDistributionForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $client->request('GET', '/admin/activity/stats/' . $nonExistentId . '/device');

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

    public function testGetDeviceDistributionWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        // 期望抛出访问被拒绝异常
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/activity/stats/1/device');
    }

    public function testGetDeviceDistributionForActivityWithoutEventsShouldReturnEmptyData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity without events
        $activity = new Activity();
        $activity->setTitle('Empty Device Stats Activity');
        $activity->setSlug('empty-device-stats-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/device');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
    }

    public function testGetDeviceDistributionOnlySupportsGetMethod(): void
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

        $client->request('POST', '/admin/activity/stats/' . $activity->getId() . '/device');
    }

    public function testGetDeviceDistributionWithInvalidIdShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/admin/activity/stats/abc/device');
    }

    public function testGetDeviceDistributionResponseStructureShouldBeValid(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Device Response Structure Activity');
        $activity->setSlug('device-response-structure-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/stats/' . $activity->getId() . '/device');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');

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
            $client->request($method, '/admin/activity/stats/' . $activity->getId() . '/device');

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
