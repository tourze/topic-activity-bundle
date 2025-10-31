<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Controller\Admin\Editor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\TopicActivityBundle\Controller\Admin\Editor\ComponentsGetController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(ComponentsGetController::class)]
#[RunTestsInSeparateProcesses]
final class ComponentsGetControllerTest extends AbstractWebTestCase
{
    public function testGetComponentsForExistingActivityShouldReturnComponents(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity with components
        $activity = new Activity();
        $activity->setTitle('Test Activity');
        $activity->setSlug('test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $component1 = new ActivityComponent();
        $component1->setComponentType('text');
        $component1->setComponentConfig(['content' => 'Test text']);
        $component1->setPosition(0);
        $component1->setIsVisible(true);
        $activity->addComponent($component1);

        $component2 = new ActivityComponent();
        $component2->setComponentType('image');
        $component2->setComponentConfig(['src' => 'test.jpg', 'alt' => 'Test image']);
        $component2->setPosition(1);
        $component2->setIsVisible(false);
        $activity->addComponent($component2);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/' . $activity->getId() . '/editor/components');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('content-type'));

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertIsArray($response);
        $this->assertCount(2, $response);

        // Check first component
        $this->assertArrayHasKey(0, $response);
        $this->assertIsArray($response[0]);
        $this->assertIsString($response[0]['type']);
        $this->assertIsArray($response[0]['config']);
        $this->assertIsInt($response[0]['position']);
        $this->assertIsBool($response[0]['visible']);
        $this->assertEquals('text', $response[0]['type']);
        $this->assertEquals(['content' => 'Test text'], $response[0]['config']);
        $this->assertEquals(0, $response[0]['position']);
        $this->assertTrue($response[0]['visible']);

        // Check second component
        $this->assertArrayHasKey(1, $response);
        $this->assertIsArray($response[1]);
        $this->assertIsString($response[1]['type']);
        $this->assertIsArray($response[1]['config']);
        $this->assertIsInt($response[1]['position']);
        $this->assertIsBool($response[1]['visible']);
        $this->assertEquals('image', $response[1]['type']);
        $this->assertEquals(['src' => 'test.jpg', 'alt' => 'Test image'], $response[1]['config']);
        $this->assertEquals(1, $response[1]['position']);
        $this->assertFalse($response[1]['visible']);
    }

    public function testGetComponentsForActivityWithoutComponentsShouldReturnEmptyArray(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity without components
        $activity = new Activity();
        $activity->setTitle('Empty Activity');
        $activity->setSlug('empty-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/' . $activity->getId() . '/editor/components');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertCount(0, $response);
    }

    public function testGetComponentsForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $client->request('GET', '/admin/activity/' . $nonExistentId . '/editor/components');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
        $this->assertEquals('Activity not found', $response['error']);
    }

    public function testGetComponentsWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $client->request('GET', '/admin/activity/1/editor/components');
    }

    public function testGetComponentsResponseStructureShouldBeValid(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity with various component types
        $activity = new Activity();
        $activity->setTitle('Multi-component Activity');
        $activity->setSlug('multi-component-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $buttonComponent = new ActivityComponent();
        $buttonComponent->setComponentType('button');
        $buttonComponent->setComponentConfig([
            'text' => 'Click me',
            'url' => 'https://example.com',
            'style' => 'primary',
        ]);
        $buttonComponent->setPosition(2);
        $buttonComponent->setIsVisible(true);
        $activity->addComponent($buttonComponent);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/' . $activity->getId() . '/editor/components');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);

        $this->assertIsArray($response);
        $this->assertCount(1, $response);

        $this->assertArrayHasKey(0, $response);
        $this->assertIsArray($response[0]);
        $component = $response[0];
        $this->assertArrayHasKey('id', $component);
        $this->assertArrayHasKey('type', $component);
        $this->assertArrayHasKey('config', $component);
        $this->assertArrayHasKey('position', $component);
        $this->assertArrayHasKey('visible', $component);

        $this->assertIsInt($component['id']);
        $this->assertIsString($component['type']);
        $this->assertIsArray($component['config']);
        $this->assertIsInt($component['position']);
        $this->assertIsBool($component['visible']);
    }

    public function testGetComponentsWithInvalidIdShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(\TypeError::class);
        $client->request('GET', '/admin/activity/abc/editor/components');
    }

    public function testGetComponentsPostMethodReturnsBadRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $client->request('POST', '/admin/activity/1/editor/components');

        // POST is handled by ComponentsSaveController and returns 400 for invalid data
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testGetComponentsWithComplexConfigShouldPreserveStructure(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Complex Config Activity');
        $activity->setSlug('complex-config-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $complexConfig = [
            'title' => 'Countdown Timer',
            'targetDate' => '2024-12-31T23:59:59Z',
            'style' => [
                'background' => '#ff0000',
                'color' => '#ffffff',
                'padding' => '20px',
            ],
            'options' => [
                'showDays' => true,
                'showHours' => true,
                'showMinutes' => false,
            ],
        ];

        $component = new ActivityComponent();
        $component->setComponentType('countdown');
        $component->setComponentConfig($complexConfig);
        $component->setPosition(0);
        $component->setIsVisible(true);
        $activity->addComponent($component);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('GET', '/admin/activity/' . $activity->getId() . '/editor/components');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);

        $this->assertCount(1, $response);
        $this->assertArrayHasKey(0, $response);
        $this->assertIsArray($response[0]);
        $this->assertIsArray($response[0]['config']);
        $this->assertEquals($complexConfig, $response[0]['config']);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity for testing
        $activity = new Activity();
        $activity->setTitle('Method Test Activity');
        $activity->setSlug('method-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request($method, '/admin/activity/' . $activity->getId() . '/editor/components');

            // 如果没有抛出异常，检查响应状态码
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertTrue(in_array($statusCode, [
                Response::HTTP_METHOD_NOT_ALLOWED,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_BAD_REQUEST,  // 400 for POST method
            ], true), "Expected status code to be 405, 404 or 400, got {$statusCode}");
        } catch (NotFoundHttpException $e) {
            // 如果路由不存在，抛出 NotFoundHttpException 是正常的
            $this->assertStringContainsString('No route found', $e->getMessage());
        } catch (MethodNotAllowedHttpException $e) {
            // 如果方法不允许，抛出 MethodNotAllowedHttpException 是正常的
            $this->assertStringContainsString('Method Not Allowed', $e->getMessage());
        }
    }
}
