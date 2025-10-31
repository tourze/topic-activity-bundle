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
use Tourze\TopicActivityBundle\Controller\Admin\Editor\PublishController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

/**
 * @internal
 */
#[CoversClass(PublishController::class)]
#[RunTestsInSeparateProcesses]
final class PublishControllerTest extends AbstractWebTestCase
{
    public function testPublishDraftActivityShouldReturnSuccessAndUpdateStatus(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create draft activity
        $activity = new Activity();
        $activity->setTitle('Draft Activity to Publish');
        $activity->setSlug('draft-activity-to-publish-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);
        $activity->setDescription('This draft activity will be published');

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/' . $activity->getId() . '/editor/publish');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response, 'Response should be an array');

        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('published', $response['status']);

        // Verify activity status was updated
        $updatedActivity = $activityRepository->find($activity->getId());
        $this->assertNotNull($updatedActivity, 'Updated activity should not be null');
        $this->assertEquals(ActivityStatus::PUBLISHED, $updatedActivity->getStatus());
    }

    public function testPublishNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $nonExistentId = 999999;
        $client->request('POST', '/admin/activity/' . $nonExistentId . '/editor/publish');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response, 'Response should be an array');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Activity not found', $response['error']);
    }

    public function testPublishActivityWithoutAuthenticationShouldThrowAccessDenied(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('POST', '/admin/activity/1/editor/publish');
    }

    public function testPublishAlreadyPublishedActivityShouldReturnError(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create already published activity
        $activity = new Activity();
        $activity->setTitle('Already Published Activity');
        $activity->setSlug('already-published-activity-' . uniqid());
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activity->setDescription('This activity is already published');

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/' . $activity->getId() . '/editor/publish');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response, 'Response should be an array');

        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
        $this->assertStringContainsString('Activity cannot transition', $response['error']);
    }

    public function testPublishOnlySupportsPostMethod(): void
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
        $this->expectExceptionMessage('Method Not Allowed');

        $client->request('GET', '/admin/activity/' . $activity->getId() . '/editor/publish');
    }

    public function testPublishActivityWithInvalidIdShouldReturnError(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 测试非数字ID，这会导致路由参数转换错误
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('must be of type int, string given');

        $client->request('POST', '/admin/activity/abc/editor/publish');
    }

    public function testPublishArchivedActivityShouldWork(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity and properly set it to archived status
        // First create as draft, then archive it through proper state transition
        $activity = new Activity();
        $activity->setTitle('Archived Activity to Publish');
        $activity->setSlug('archived-activity-to-publish-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);
        $activity->setDescription('This archived activity will be published again');

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // Transition to published first, then to archived
        $activity->setStatus(ActivityStatus::PUBLISHED);
        $activityRepository->save($activity, true);
        $activity->setStatus(ActivityStatus::ARCHIVED);
        $activityRepository->save($activity, true);

        $client->request('POST', '/admin/activity/' . $activity->getId() . '/editor/publish');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $response = json_decode($content, true);
        $this->assertNotFalse($response, 'JSON decode should not fail');
        $this->assertIsArray($response, 'Response should be an array');

        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('published', $response['status']);

        // Verify activity status was updated
        $updatedActivity = $activityRepository->find($activity->getId());
        $this->assertNotNull($updatedActivity, 'Updated activity should not be null');
        $this->assertEquals(ActivityStatus::PUBLISHED, $updatedActivity->getStatus());
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
            $client->request($method, '/admin/activity/' . $activity->getId() . '/editor/publish');

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
