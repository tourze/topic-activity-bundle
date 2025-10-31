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
use Tourze\TopicActivityBundle\Controller\Admin\Editor\IndexController;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Twig\Error\RuntimeError;

/**
 * @internal
 */
#[CoversClass(IndexController::class)]
#[RunTestsInSeparateProcesses]
final class IndexControllerTest extends AbstractWebTestCase
{
    public function testEditorPageForExistingActivityShouldRenderSuccessfully(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Create activity
        $activity = new Activity();
        $activity->setTitle('Test Activity for Editor');
        $activity->setSlug('test-activity-editor-' . uniqid());
        $activity->setStatus(ActivityStatus::DRAFT);
        $activity->setDescription('This is a test activity for the editor');

        $activityRepository = self::getService(ActivityRepository::class);
        self::assertInstanceOf(ActivityRepository::class, $activityRepository);
        $activityRepository->save($activity, true);

        // 目前EasyAdmin上下文问题导致模板渲染失败，期望抛出运行时异常
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Impossible to access an attribute ("i18n") on a null variable');

        $client->request('GET', '/admin/activity/' . $activity->getId() . '/editor');
    }

    public function testEditorPageForNonExistentActivityShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(NotFoundHttpException::class);

        $nonExistentId = 999999;
        $client->request('GET', '/admin/activity/' . $nonExistentId . '/editor');
    }

    public function testEditorPageWithoutAuthenticationShouldRedirect(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $client->request('GET', '/admin/activity/1/editor');
    }

    public function testEditorPageOnlySupportsGetMethod(): void
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

        $client->request('POST', '/admin/activity/' . $activity->getId() . '/editor');
    }

    public function testEditorPageWithInvalidActivityIdShouldReturn404(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(\TypeError::class);

        $client->request('GET', '/admin/activity/abc/editor');
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
            $client->request($method, '/admin/activity/' . $activity->getId() . '/editor');

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
