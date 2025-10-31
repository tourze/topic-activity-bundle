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
use Tourze\TopicActivityBundle\Controller\Admin\Editor\ComponentPreviewController;

/**
 * @internal
 */
#[CoversClass(ComponentPreviewController::class)]
#[RunTestsInSeparateProcesses]
final class ComponentPreviewControllerTest extends AbstractWebTestCase
{
    public function testPreviewComponentWithValidDataShouldReturnJsonResponse(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $componentData = [
            'type' => 'text',
            'config' => [
                'content' => 'This is a test text component',
                'fontSize' => '16px',
                'color' => '#333333',
            ],
        ];

        $jsonContent = json_encode($componentData);
        $this->assertIsString($jsonContent);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
    }

    public function testPreviewComponentWithImageTypeShouldReturnValidPreview(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $componentData = [
            'type' => 'image',
            'config' => [
                'src' => 'https://example.com/image.jpg',
                'alt' => 'Test image',
                'width' => '300px',
                'height' => '200px',
            ],
        ];

        $jsonContent = json_encode($componentData);
        $this->assertIsString($jsonContent);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
    }

    public function testPreviewComponentWithButtonTypeShouldReturnValidPreview(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $componentData = [
            'type' => 'button',
            'config' => [
                'text' => 'Click Me',
                'url' => 'https://example.com',
                'style' => 'primary',
                'size' => 'large',
            ],
        ];

        $jsonContent = json_encode($componentData);
        $this->assertIsString($jsonContent);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
    }

    public function testPreviewComponentWithInvalidJsonShouldReturnBadRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json content'
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

    public function testPreviewComponentWithoutTypeShouldReturnBadRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $componentData = [
            'config' => [
                'content' => 'Missing type field',
            ],
        ];

        $jsonContent = json_encode($componentData);
        $this->assertIsString($jsonContent);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
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

    public function testPreviewComponentWithoutConfigShouldUseDefaults(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $componentData = [
            'type' => 'text',
            // Missing config - should use defaults
        ];

        $jsonContent = json_encode($componentData);
        $this->assertIsString($jsonContent);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
    }

    public function testPreviewComponentWithEmptyRequestBodyShouldReturnBadRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
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

    public function testPreviewComponentWithComplexConfigShouldHandleCorrectly(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $componentData = [
            'type' => 'countdown',
            'config' => [
                'title' => 'Event Countdown',
                'targetDate' => '2024-12-31T23:59:59Z',
                'style' => [
                    'background' => 'linear-gradient(45deg, #ff6b6b, #ee5a24)',
                    'color' => '#ffffff',
                    'padding' => '20px',
                    'borderRadius' => '10px',
                ],
                'options' => [
                    'showDays' => true,
                    'showHours' => true,
                    'showMinutes' => true,
                    'showSeconds' => false,
                ],
                'labels' => [
                    'days' => 'Days',
                    'hours' => 'Hours',
                    'minutes' => 'Minutes',
                ],
            ],
        ];

        $jsonContent = json_encode($componentData);
        $this->assertIsString($jsonContent);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $response = json_decode($content, true);
        $this->assertIsArray($response);
    }

    public function testPreviewComponentWithoutAuthenticationShouldThrowAccessDenied(): void
    {
        $client = self::createClientWithDatabase();

        $componentData = [
            'type' => 'text',
            'config' => [
                'content' => 'Test content',
            ],
        ];

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $jsonContent = json_encode($componentData);
        $this->assertIsString($jsonContent);

        $client->request(
            'POST',
            '/admin/activity/editor/preview-component',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );
    }

    public function testPreviewComponentOnlySupportsPostMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $this->expectException(MethodNotAllowedHttpException::class);
        $this->expectExceptionMessage('No route found for "GET http://localhost/admin/activity/editor/preview-component": Method Not Allowed (Allow: POST)');

        $client->request('GET', '/admin/activity/editor/preview-component');
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        // Linus: 删除INVALID检查，让糟糕的DataProvider直接失败而不是跳过
        // 如果DataProvider生成INVALID数据，这个测试就会失败，这比跳过测试要好

        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        try {
            $client->request($method, '/admin/activity/editor/preview-component');

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
