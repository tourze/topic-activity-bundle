<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Component\Basic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Component\Basic\ImageComponent;

/**
 * @internal
 */
#[CoversClass(ImageComponent::class)]
#[RunTestsInSeparateProcesses]
class ImageComponentTest extends AbstractIntegrationTestCase
{
    private ImageComponent $component;

    protected function onSetUp(): void
    {
        $this->component = self::getService(ImageComponent::class);
    }

    public function testGetType(): void
    {
        $this->assertSame('image', $this->component->getType());
    }

    public function testGetName(): void
    {
        $this->assertSame('图片', $this->component->getName());
    }

    public function testGetCategory(): void
    {
        $this->assertSame('basic', $this->component->getCategory());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('fa fa-image', $this->component->getIcon());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('图片展示组件，支持多种布局方式', $this->component->getDescription());
    }

    public function testGetOrder(): void
    {
        $this->assertSame(20, $this->component->getOrder());
    }

    public function testGetDefaultConfig(): void
    {
        $defaultConfig = $this->component->getDefaultConfig();

        $this->assertIsArray($defaultConfig);
        $this->assertArrayHasKey('src', $defaultConfig);
        $this->assertArrayHasKey('alt', $defaultConfig);
        $this->assertArrayHasKey('title', $defaultConfig);
        $this->assertArrayHasKey('width', $defaultConfig);
        $this->assertArrayHasKey('height', $defaultConfig);
        $this->assertArrayHasKey('objectFit', $defaultConfig);
        $this->assertArrayHasKey('link', $defaultConfig);
        $this->assertArrayHasKey('linkTarget', $defaultConfig);
        $this->assertArrayHasKey('borderRadius', $defaultConfig);
        $this->assertArrayHasKey('lazyLoad', $defaultConfig);
        $this->assertArrayHasKey('className', $defaultConfig);

        $this->assertSame('', $defaultConfig['src']);
        $this->assertSame('', $defaultConfig['alt']);
        $this->assertSame('', $defaultConfig['title']);
        $this->assertSame('auto', $defaultConfig['width']);
        $this->assertSame('auto', $defaultConfig['height']);
        $this->assertSame('cover', $defaultConfig['objectFit']);
        $this->assertSame('', $defaultConfig['link']);
        $this->assertSame('_self', $defaultConfig['linkTarget']);
        $this->assertSame('0', $defaultConfig['borderRadius']);
        $this->assertTrue($defaultConfig['lazyLoad']);
        $this->assertSame('', $defaultConfig['className']);
    }

    public function testGetConfigSchema(): void
    {
        $schema = $this->component->getConfigSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('src', $schema);
        $this->assertArrayHasKey('alt', $schema);
        $this->assertArrayHasKey('title', $schema);
        $this->assertArrayHasKey('width', $schema);
        $this->assertArrayHasKey('height', $schema);
        $this->assertArrayHasKey('objectFit', $schema);
        $this->assertArrayHasKey('link', $schema);
        $this->assertArrayHasKey('linkTarget', $schema);

        $this->assertSame('string', $schema['src']['type']);
        $this->assertTrue($schema['src']['required']);
        $this->assertSame('图片地址', $schema['src']['label']);
        $this->assertSame('image', $schema['src']['editor']);

        $this->assertSame('string', $schema['alt']['type']);
        $this->assertFalse($schema['alt']['required']);
        $this->assertSame('替代文本', $schema['alt']['label']);
        $this->assertSame(255, $schema['alt']['maxLength']);

        $this->assertSame('string', $schema['width']['type']);
        $this->assertFalse($schema['width']['required']);
        $this->assertSame('宽度', $schema['width']['label']);
        $this->assertSame('auto', $schema['width']['default']);

        $this->assertSame('string', $schema['objectFit']['type']);
        $this->assertSame(['cover', 'contain', 'fill', 'none', 'scale-down'], $schema['objectFit']['options']);
        $this->assertSame('cover', $schema['objectFit']['default']);

        $this->assertSame('url', $schema['link']['type']);
        $this->assertFalse($schema['link']['required']);
        $this->assertSame('链接地址', $schema['link']['label']);
    }

    public function testRender(): void
    {
        $config = ['src' => 'https://example.com/image.jpg', 'alt' => 'Test Image'];
        $result = $this->component->render($config);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testValidate(): void
    {
        $config = [];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('src', $errors);
        $this->assertStringContainsString('required', $errors['src']);

        $config = ['src' => 'https://example.com/image.jpg'];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);

        $config = ['src' => 123];
        $errors = $this->component->validate($config);
        $this->assertArrayHasKey('src', $errors);
        $this->assertStringContainsString('must be of type string', $errors['src']);
    }

    public function testValidateAltTextLength(): void
    {
        $config = [
            'src' => 'https://example.com/image.jpg',
            'alt' => str_repeat('a', 256),
        ];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('alt', $errors);
        $this->assertStringContainsString('exceed 255 characters', $errors['alt']);
    }

    public function testValidateWithInvalidLink(): void
    {
        $config = [
            'src' => 'image.jpg',
            'link' => 'invalid-url',
        ];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('link', $errors);
        $this->assertStringContainsString('must be of type url', $errors['link']);
    }
}
