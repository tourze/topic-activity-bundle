<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Component\Marketing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\TopicActivityBundle\Component\Marketing\BannerComponent;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(BannerComponent::class)]
class BannerComponentTest extends TestCase
{
    private BannerComponent $component;

    private Environment $twig;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = $this->createMock(Environment::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->component = new BannerComponent($this->twig, $this->validator);
    }

    public function testGetName(): void
    {
        $this->assertSame('轮播图', $this->component->getName());
    }

    public function testGetType(): void
    {
        $this->assertSame('banner', $this->component->getType());
    }

    public function testGetCategory(): void
    {
        $this->assertSame('marketing', $this->component->getCategory());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('fa fa-images', $this->component->getIcon());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('图片轮播展示', $this->component->getDescription());
    }

    public function testGetOrder(): void
    {
        $this->assertSame(130, $this->component->getOrder());
    }

    public function testIsVisible(): void
    {
        $this->assertTrue($this->component->isVisible());
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->component->supports('1.0'));
        $this->assertTrue($this->component->supports('2.0'));
    }

    public function testGetDefaultConfig(): void
    {
        $expected = [
            'images' => [],
            'autoplay' => true,
            'interval' => 3000,
            'showIndicators' => true,
            'showArrows' => true,
            'height' => '400px',
            'objectFit' => 'cover',
            'borderRadius' => '0',
            'className' => '',
        ];

        $this->assertSame($expected, $this->component->getDefaultConfig());
    }

    public function testGetConfigSchema(): void
    {
        $schema = $this->component->getConfigSchema();

        $this->assertArrayHasKey('images', $schema);
        $this->assertArrayHasKey('autoplay', $schema);
        $this->assertArrayHasKey('interval', $schema);
        $this->assertArrayHasKey('showIndicators', $schema);
        $this->assertArrayHasKey('showArrows', $schema);
        $this->assertArrayHasKey('height', $schema);
        $this->assertArrayHasKey('objectFit', $schema);
        $this->assertArrayHasKey('borderRadius', $schema);
        $this->assertArrayHasKey('className', $schema);

        // 测试 images 字段的配置
        $this->assertSame('array', $schema['images']['type']);
        $this->assertTrue($schema['images']['required']);
        $this->assertSame('图片列表', $schema['images']['label']);
        $this->assertSame('images', $schema['images']['editor']);

        // 测试 autoplay 字段的配置
        $this->assertSame('boolean', $schema['autoplay']['type']);
        $this->assertFalse($schema['autoplay']['required']);
        $this->assertTrue($schema['autoplay']['default']);

        // 测试 interval 字段的配置
        $this->assertSame('integer', $schema['interval']['type']);
        $this->assertSame(3000, $schema['interval']['default']);

        // 测试 objectFit 字段的选项
        $this->assertSame(['cover', 'contain', 'fill', 'none'], $schema['objectFit']['options']);
    }

    public function testRenderWithDefaultConfig(): void
    {
        /** @phpstan-ignore method.nonObject, method.nonObject, method.nonObject */
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@TopicActivity/components/banner.html.twig',
                [
                    'config' => $this->component->getDefaultConfig(),
                    'component' => $this->component,
                ]
            )
            ->willReturn('<div class="banner-component">Banner Content</div>')
        ;

        $result = $this->component->render();
        $this->assertSame('<div class="banner-component">Banner Content</div>', $result);
    }

    public function testRenderWithCustomConfig(): void
    {
        $customConfig = [
            'images' => [
                ['src' => '/image1.jpg', 'alt' => 'Image 1', 'link' => 'https://example.com'],
                ['src' => '/image2.jpg', 'alt' => 'Image 2'],
            ],
            'autoplay' => false,
            'interval' => 5000,
        ];

        $expectedConfig = array_merge($this->component->getDefaultConfig(), $customConfig);

        /** @phpstan-ignore method.nonObject, method.nonObject, method.nonObject */
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@TopicActivity/components/banner.html.twig',
                [
                    'config' => $expectedConfig,
                    'component' => $this->component,
                ]
            )
            ->willReturn('<div class="banner-component">Custom Banner</div>')
        ;

        $result = $this->component->render($customConfig);
        $this->assertSame('<div class="banner-component">Custom Banner</div>', $result);
    }

    public function testRenderHandlesException(): void
    {
        /** @phpstan-ignore method.nonObject, method.nonObject */
        $this->twig->expects($this->once())
            ->method('render')
            ->willThrowException(new \Exception('Template not found'))
        ;

        $result = $this->component->render();
        $this->assertSame('<!-- Component render error: Template not found -->', $result);
    }

    public function testValidateRequiredFields(): void
    {
        $config = [];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('images', $errors);
        $this->assertSame('Field "images" is required', $errors['images']);
    }

    public function testValidateWithValidConfig(): void
    {
        $config = [
            'images' => [
                ['src' => '/image1.jpg', 'alt' => 'Image 1'],
            ],
            'autoplay' => true,
            'interval' => 3000,
        ];

        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);
    }

    public function testValidateWithInvalidTypes(): void
    {
        $config = [
            'images' => 'not-an-array',
            'autoplay' => 'not-a-boolean',
            'interval' => 'not-an-integer',
        ];

        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('images', $errors);
        $this->assertArrayHasKey('autoplay', $errors);
        $this->assertArrayHasKey('interval', $errors);

        $this->assertStringContainsString('must be of type array', $errors['images']);
        $this->assertStringContainsString('must be of type boolean', $errors['autoplay']);
        $this->assertStringContainsString('must be of type integer', $errors['interval']);
    }
}
