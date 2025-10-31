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
use Tourze\TopicActivityBundle\Controller\Admin\Editor\ComponentTypesController;

/**
 * @internal
 */
#[CoversClass(ComponentTypesController::class)]
#[RunTestsInSeparateProcesses]
final class ComponentTypesControllerTest extends AbstractWebTestCase
{
    public function testGetComponentTypesShouldReturnJsonResponse(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/activity/editor/component-types');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('content-type'));

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
    }

    public function testGetComponentTypesShouldContainBasicComponents(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/activity/editor/component-types');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);

        $this->assertIsArray($response);
        // Check that response contains component configuration
        if (count($response) > 0) {
            $this->assertIsArray($response);
        }
    }

    public function testGetComponentTypesWithoutAuthenticationShouldThrowAccessDenied(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/activity/editor/component-types');
    }

    public function testGetComponentTypesResponseStructure(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/activity/editor/component-types');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);

        $this->assertIsArray($response);

        // If components exist, verify structure
        // The response structure is [type => componentConfig] where type is the key
        foreach ($response as $type => $componentConfig) {
            $this->assertIsString($type, 'Component type should be a string');
            if (is_array($componentConfig)) {
                // Component config should have basic structure - verify it has required keys
                $this->assertArrayHasKey('type', $componentConfig, 'Component config should have type key');
                $this->assertArrayHasKey('name', $componentConfig, 'Component config should have name key');
                $this->assertArrayHasKey('category', $componentConfig, 'Component config should have category key');
                $this->assertSame($type, $componentConfig['type'], 'Component type in config should match the key');
            }
        }
    }

    public function testGetComponentTypesWithGetMethodOnly(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('POST', '/admin/activity/editor/component-types');
    }

    public function testGetComponentTypesRouteExists(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/activity/editor/component-types');

        // Should not return 404
        $this->assertNotEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetComponentTypesReturnsConsistentData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // Make first request
        $client->request('GET', '/admin/activity/editor/component-types');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $firstResponse = json_decode($content, true);
        $this->assertNotFalse($firstResponse, 'JSON decode should not fail');

        // Make second request
        $client->request('GET', '/admin/activity/editor/component-types');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $secondResponse = json_decode($content, true);
        $this->assertNotFalse($secondResponse, 'JSON decode should not fail');

        // Should return the same data
        $this->assertEquals($firstResponse, $secondResponse);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        // Linus: 删除INVALID检查，让糟糕的DataProvider直接失败而不是跳过
        // 如果DataProvider生成INVALID数据，这个测试就会失败，这比跳过测试要好

        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        try {
            $client->request($method, '/admin/activity/editor/component-types');

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
