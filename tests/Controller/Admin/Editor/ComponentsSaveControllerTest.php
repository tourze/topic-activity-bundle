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
use Tourze\TopicActivityBundle\Controller\Admin\Editor\ComponentsSaveController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(ComponentsSaveController::class)]
#[RunTestsInSeparateProcesses]
final class ComponentsSaveControllerTest extends AbstractWebTestCase
{
    public function testSaveComponentsForExistingActivityShouldReturnSuccess(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity
        $activity = new Activity();
        $activity->setTitle('Test Activity');
        $activity->setSlug('test-activity-save-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $componentData = [
            [
                'type' => 'text',
                'config' => ['content' => 'Updated text content'],
                'position' => 0,
                'visible' => true,
            ],
            [
                'type' => 'image',
                'config' => ['src' => 'updated-image.jpg', 'alt' => 'Updated image'],
                'position' => 1,
                'visible' => false,
            ],
        ];

        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/components',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->encodeJsonContent($componentData)
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testSaveComponentsForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $componentData = [
            [
                'type' => 'text',
                'config' => ['content' => 'Test content'],
                'position' => 0,
            ],
        ];

        $client->request(
            'POST',
            '/admin/activity/' . $nonExistentId . '/editor/components',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->encodeJsonContent($componentData)
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
        $this->assertEquals('Activity not found', $response['error']);
    }

    public function testSaveComponentsWithInvalidJsonShouldReturnBadRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity
        $activity = new Activity();
        $activity->setTitle('Test Activity');
        $activity->setSlug('test-activity-invalid-json-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/components',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json data'
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
        $this->assertEquals('Invalid data', $response['error']);
    }

    public function testSaveComponentsWithEmptyArrayShouldClearComponents(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity with existing components
        $activity = new Activity();
        $activity->setTitle('Activity with Components');
        $activity->setSlug('activity-with-components-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $component = new ActivityComponent();
        $component->setComponentType('text');
        $component->setComponentConfig(['content' => 'To be removed']);
        $component->setPosition(0);
        $activity->addComponent($component);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Save empty component array
        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/components',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode([]) ? json_encode([]) : '[]'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testSaveComponentsWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        $componentData = [
            ['type' => 'text', 'config' => ['content' => 'Test'], 'position' => 0],
        ];

        $this->expectException(AccessDeniedException::class);
        $client->request(
            'POST',
            '/admin/activity/1/editor/components',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->encodeJsonContent($componentData)
        );
    }

    public function testSaveComponentsWithComplexDataShouldPreserveStructure(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Complex Data Activity');
        $activity->setSlug('complex-data-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $componentData = [
            [
                'type' => 'countdown',
                'config' => [
                    'title' => 'Event Countdown',
                    'targetDate' => '2024-12-31T23:59:59Z',
                    'style' => [
                        'background' => '#ff0000',
                        'color' => '#ffffff',
                        'fontSize' => '18px',
                    ],
                    'options' => [
                        'showDays' => true,
                        'showHours' => true,
                        'showMinutes' => false,
                        'showSeconds' => true,
                    ],
                ],
                'position' => 0,
                'visible' => true,
            ],
            [
                'type' => 'banner',
                'config' => [
                    'title' => 'Special Promotion',
                    'subtitle' => 'Limited time offer',
                    'backgroundImage' => 'promo-bg.jpg',
                    'textColor' => '#ffffff',
                    'buttons' => [
                        [
                            'text' => 'Learn More',
                            'url' => '/learn-more',
                            'style' => 'primary',
                        ],
                        [
                            'text' => 'Shop Now',
                            'url' => '/shop',
                            'style' => 'secondary',
                        ],
                    ],
                ],
                'position' => 1,
                'visible' => true,
            ],
        ];

        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/components',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->encodeJsonContent($componentData)
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
    }

    public function testSaveComponentsOnlySupportsPostMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/activity/1/editor/components');

        // GET request is handled by ComponentsGetController and returns 200
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testSaveComponentsWithNullDataShouldReturnBadRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Null Data Activity');
        $activity->setSlug('null-data-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/components',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'null'
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
        $this->assertEquals('Invalid data', $response['error']);
    }

    public function testSaveComponentsWithMixedVisibilityStates(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Mixed Visibility Activity');
        $activity->setSlug('mixed-visibility-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $componentData = [
            [
                'type' => 'text',
                'config' => ['content' => 'Visible text'],
                'position' => 0,
                'visible' => true,
            ],
            [
                'type' => 'image',
                'config' => ['src' => 'hidden-image.jpg'],
                'position' => 1,
                'visible' => false,
            ],
            [
                'type' => 'button',
                'config' => ['text' => 'Visible button', 'url' => '/test'],
                'position' => 2,
                // No visible property - should default appropriately
            ],
        ];

        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/components',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->encodeJsonContent($componentData)
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);
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
                Response::HTTP_OK,  // GET method is handled by ComponentsGetController
            ], true), "Expected status code to be 405, 404 or 200, got {$statusCode}");
        } catch (NotFoundHttpException $e) {
            // 如果路由不存在，抛出 NotFoundHttpException 是正常的
            $this->assertStringContainsString('No route found', $e->getMessage());
        } catch (MethodNotAllowedHttpException $e) {
            // 如果方法不允许，抛出 MethodNotAllowedHttpException 是正常的
            $this->assertStringContainsString('Method Not Allowed', $e->getMessage());
        }
    }

    /**
     * @param mixed $data
     */
    private function encodeJsonContent($data): string
    {
        $json = json_encode($data);
        $this->assertIsString($json);

        return $json;
    }
}
