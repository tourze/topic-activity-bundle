<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TopicActivityBundle\Controller\Admin\ActivityEventCrudController;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Repository\ActivityEventRepository;
use Tourze\TopicActivityBundle\Tests\Controller\Admin\AbstractTopicActivityControllerTestCase;

/**
 * @internal
 */
#[CoversClass(ActivityEventCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ActivityEventCrudControllerTest extends AbstractTopicActivityControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return ActivityEvent::class;
    }

    protected function getControllerService(): ActivityEventCrudController
    {
        $controller = self::getContainer()->get(ActivityEventCrudController::class);
        $this->assertInstanceOf(ActivityEventCrudController::class, $controller);

        return $controller;
    }

    public static function provideIndexPageHeaders(): iterable
    {
        return [
            'ID' => ['ID'],
            '活动ID' => ['活动ID'],
            '事件类型' => ['事件类型'],
            '设备类型' => ['设备类型'],
            '创建时间' => ['创建时间'],
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // NEW action is disabled for ActivityEvent - events are auto-generated
        // Provide dummy data to prevent PHPUnit error - tests will be skipped
        return [
            'disabled' => ['disabled'],
        ];
    }

    public static function provideEditPageFields(): iterable
    {
        // EDIT action is disabled for ActivityEvent - events are read-only
        // Provide dummy data to prevent PHPUnit error - tests will be skipped
        return [
            'disabled' => ['disabled'],
        ];
    }

    public function testIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Navigate to ActivityEvent CRUD
        $link = $crawler->filter('a[href*="ActivityEventCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testEventListing(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create test event data
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $event = ActivityEvent::create(1);
        $event->setSessionId('test-session-123');
        $event->setUserId(1);
        $event->setEventType(ActivityEvent::EVENT_VIEW);
        $event->setEventData(['page' => 'homepage']);
        $event->setClientIp('127.0.0.1');
        $event->setUserAgent('Test User Agent');
        $event->setReferer('https://example.com');

        $entityManager->persist($event);
        $entityManager->flush();

        // Test that we can access the controller class and its basic configuration
        $controller = $this->getControllerService();
        $this->assertInstanceOf(ActivityEventCrudController::class, $controller);
        $this->assertEquals(ActivityEvent::class, ActivityEventCrudController::getEntityFqcn());
    }

    public function testEventDataPersistence(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $event = ActivityEvent::create(1);
        $event->setSessionId('test-session-456');
        $event->setUserId(2);
        $event->setEventType(ActivityEvent::EVENT_CLICK);
        $event->setEventData(['button' => 'subscribe', 'position' => 'header']);
        $event->setClientIp('192.168.1.1');
        $event->setUserAgent('Mozilla/5.0 Test Browser');

        $entityManager->persist($event);
        $entityManager->flush();

        $repository = self::getService(ActivityEventRepository::class);
        $savedEvent = $repository->find($event->getId());

        $this->assertNotNull($savedEvent);
        $this->assertEquals(1, $savedEvent->getActivityId());
        $this->assertEquals('test-session-456', $savedEvent->getSessionId());
        $this->assertEquals(2, $savedEvent->getUserId());
        $this->assertEquals(ActivityEvent::EVENT_CLICK, $savedEvent->getEventType());
        $this->assertEquals(['button' => 'subscribe', 'position' => 'header'], $savedEvent->getEventData());
        $this->assertEquals('192.168.1.1', $savedEvent->getClientIp());
        $this->assertEquals('Mozilla/5.0 Test Browser', $savedEvent->getUserAgent());
    }

    public function testEventAnalysisMethods(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // Create event with mobile user agent
        $event = ActivityEvent::create(1);
        $event->setSessionId('mobile-session');
        $event->setEventType(ActivityEvent::EVENT_VIEW);
        $event->setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X)');
        $event->setReferer('https://google.com/search?q=test');

        $entityManager->persist($event);
        $entityManager->flush();

        $this->assertEquals('mobile', $event->getDeviceType());
        $this->assertEquals('search', $event->getSource());
        $this->assertTrue($event->isViewEvent());
        $this->assertFalse($event->isClickEvent());
    }

    public function testAnalyzeActivityEvents(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Verify controller can be instantiated and has the method
        $controller = $this->getControllerService();
        $this->assertInstanceOf(ActivityEventCrudController::class, $controller);

        // Verify method exists through reflection
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('analyzeActivityEvents'));
    }

    public function testExportActivityEvents(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Verify controller can be instantiated and has the method
        $controller = $this->getControllerService();
        $this->assertInstanceOf(ActivityEventCrudController::class, $controller);

        // Verify method exists through reflection
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('exportActivityEvents'));
    }

    public function testCleanupOldEvents(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Verify controller can be instantiated and has the method
        $controller = $this->getControllerService();
        $this->assertInstanceOf(ActivityEventCrudController::class, $controller);

        // Verify method exists through reflection
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('cleanupOldEvents'));
    }
}
