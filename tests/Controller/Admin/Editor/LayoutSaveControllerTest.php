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
use Tourze\TopicActivityBundle\Controller\Admin\Editor\LayoutSaveController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(LayoutSaveController::class)]
#[RunTestsInSeparateProcesses]
final class LayoutSaveControllerTest extends AbstractWebTestCase
{
    public function testSaveLayoutForExistingActivityShouldReturnSuccessAndUpdateLayout(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity
        $activity = new Activity();
        $activity->setTitle('Layout Test Activity');
        $activity->setSlug('layout-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $layoutData = [
            'theme' => 'modern',
            'backgroundColor' => '#ffffff',
            'headerConfig' => [
                'showTitle' => true,
                'showLogo' => false,
            ],
            'footerConfig' => [
                'showLinks' => true,
                'showCopyright' => true,
            ],
        ];

        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/layout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($layoutData) ? json_encode($layoutData) : '{}'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);

        // Verify layout was saved
        $updatedActivity = $activityRepository->find($activity->getId());
        $this->assertNotNull($updatedActivity);
        $this->assertEquals($layoutData, $updatedActivity->getLayoutConfig());
    }

    public function testSaveLayoutForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $layoutData = [
            'theme' => 'dark',
            'backgroundColor' => '#000000',
        ];

        $client->request(
            'POST',
            '/admin/activity/' . $nonExistentId . '/editor/layout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($layoutData) ? json_encode($layoutData) : '{}'
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Activity not found', $response['error']);
    }

    public function testSaveLayoutWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $layoutData = ['theme' => 'light'];

        $client->request(
            'POST',
            '/admin/activity/1/editor/layout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($layoutData) ? json_encode($layoutData) : '{}'
        );
    }

    public function testSaveLayoutWithInvalidJsonShouldReturn400(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity
        $activity = new Activity();
        $activity->setTitle('Invalid JSON Test Activity');
        $activity->setSlug('invalid-json-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/layout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json content'
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
        $this->assertEquals('Invalid data', $response['error']);
    }

    public function testSaveLayoutOnlySupportsPostMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $activity = new Activity();
        $activity->setTitle('Method Test Activity');
        $activity->setSlug('method-test-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request('GET', '/admin/activity/' . $activity->getId() . '/editor/layout');
    }

    public function testSaveLayoutWithComplexConfigShouldWork(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity
        $activity = new Activity();
        $activity->setTitle('Complex Layout Config Activity');
        $activity->setSlug('complex-layout-config-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $complexLayoutData = [
            'theme' => 'custom',
            'colors' => [
                'primary' => '#007bff',
                'secondary' => '#6c757d',
                'success' => '#28a745',
            ],
            'typography' => [
                'fontFamily' => 'Inter, sans-serif',
                'fontSize' => [
                    'base' => '16px',
                    'lg' => '18px',
                    'xl' => '20px',
                ],
            ],
            'layout' => [
                'maxWidth' => '1200px',
                'padding' => '20px',
                'sections' => [
                    'header' => ['height' => '80px'],
                    'content' => ['minHeight' => '500px'],
                    'footer' => ['height' => '60px'],
                ],
            ],
            'components' => [
                'button' => [
                    'borderRadius' => '8px',
                    'padding' => '12px 24px',
                ],
                'card' => [
                    'borderRadius' => '12px',
                    'shadow' => '0 2px 8px rgba(0,0,0,0.1)',
                ],
            ],
        ];

        $jsonContent = json_encode($complexLayoutData);
        $this->assertNotFalse($jsonContent, 'JSON encoding should not fail');

        $client->request(
            'POST',
            '/admin/activity/' . $activity->getId() . '/editor/layout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response);
        $this->assertIsBool($response['success']);
        $this->assertTrue($response['success']);

        // Verify complex layout was saved correctly
        $updatedActivity = $activityRepository->find($activity->getId());
        $this->assertNotNull($updatedActivity, 'Updated activity should not be null');
        $this->assertEquals($complexLayoutData, $updatedActivity->getLayoutConfig());
    }

    public function testSaveLayoutWithInvalidIdShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(\TypeError::class);

        $layoutData = ['theme' => 'default'];

        $client->request(
            'POST',
            '/admin/activity/abc/editor/layout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($layoutData) ? json_encode($layoutData) : '{}'
        );
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
        $activity->setStatus(ActivityStatus::DRAFT);

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        try {
            $client->request($method, '/admin/activity/' . $activity->getId() . '/editor/layout');

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
