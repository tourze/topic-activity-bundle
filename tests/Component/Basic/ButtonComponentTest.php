<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Component\Basic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Component\Basic\ButtonComponent;

/**
 * @internal
 */
#[CoversClass(ButtonComponent::class)]
#[RunTestsInSeparateProcesses]
class ButtonComponentTest extends AbstractIntegrationTestCase
{
    private ButtonComponent $component;

    protected function onSetUp(): void
    {
        $this->component = self::getService(ButtonComponent::class);
    }

    public function testGetType(): void
    {
        $this->assertSame('button', $this->component->getType());
    }

    public function testGetName(): void
    {
        $this->assertSame('按钮', $this->component->getName());
    }

    public function testGetCategory(): void
    {
        $this->assertSame('basic', $this->component->getCategory());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('fa fa-square', $this->component->getIcon());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('可点击按钮，支持多种样式和交互', $this->component->getDescription());
    }

    public function testGetOrder(): void
    {
        $this->assertSame(30, $this->component->getOrder());
    }

    public function testGetDefaultConfig(): void
    {
        $defaultConfig = $this->component->getDefaultConfig();

        $this->assertIsArray($defaultConfig);
        $this->assertArrayHasKey('text', $defaultConfig);
        $this->assertArrayHasKey('link', $defaultConfig);
        $this->assertArrayHasKey('linkTarget', $defaultConfig);
        $this->assertArrayHasKey('style', $defaultConfig);
        $this->assertArrayHasKey('size', $defaultConfig);
        $this->assertArrayHasKey('width', $defaultConfig);
        $this->assertArrayHasKey('icon', $defaultConfig);
        $this->assertArrayHasKey('iconPosition', $defaultConfig);
        $this->assertArrayHasKey('disabled', $defaultConfig);
        $this->assertArrayHasKey('borderRadius', $defaultConfig);
        $this->assertArrayHasKey('className', $defaultConfig);

        $this->assertSame('点击按钮', $defaultConfig['text']);
        $this->assertSame('', $defaultConfig['link']);
        $this->assertSame('_self', $defaultConfig['linkTarget']);
        $this->assertSame('primary', $defaultConfig['style']);
        $this->assertSame('medium', $defaultConfig['size']);
        $this->assertSame('auto', $defaultConfig['width']);
        $this->assertSame('', $defaultConfig['icon']);
        $this->assertSame('left', $defaultConfig['iconPosition']);
        $this->assertFalse($defaultConfig['disabled']);
        $this->assertSame('4px', $defaultConfig['borderRadius']);
        $this->assertSame('', $defaultConfig['className']);
    }

    public function testGetConfigSchema(): void
    {
        $schema = $this->component->getConfigSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('text', $schema);
        $this->assertArrayHasKey('link', $schema);
        $this->assertArrayHasKey('linkTarget', $schema);
        $this->assertArrayHasKey('style', $schema);
        $this->assertArrayHasKey('size', $schema);

        $this->assertSame('string', $schema['text']['type']);
        $this->assertTrue($schema['text']['required']);
        $this->assertSame('按钮文字', $schema['text']['label']);
        $this->assertSame(100, $schema['text']['maxLength']);

        $this->assertSame('url', $schema['link']['type']);
        $this->assertFalse($schema['link']['required']);

        $this->assertSame(['_self', '_blank'], $schema['linkTarget']['options']);
        $this->assertSame('_self', $schema['linkTarget']['default']);
    }

    public function testRender(): void
    {
        $config = ['text' => 'Test Button'];
        $result = $this->component->render($config);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testValidate(): void
    {
        $config = [];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('text', $errors);
        $this->assertStringContainsString('required', $errors['text']);

        $config = ['text' => 'Valid text'];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);

        $config = ['text' => str_repeat('a', 101)];
        $errors = $this->component->validate($config);
        $this->assertArrayHasKey('text', $errors);
        $this->assertStringContainsString('exceed 100 characters', $errors['text']);
    }

    public function testValidateWithInvalidUrl(): void
    {
        $config = [
            'text' => 'Button',
            'link' => 'invalid-url',
        ];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('link', $errors);
        $this->assertStringContainsString('must be of type url', $errors['link']);
    }

    public function testValidateWithValidUrl(): void
    {
        $config = [
            'text' => 'Button',
            'link' => 'https://example.com',
        ];
        $errors = $this->component->validate($config);

        $this->assertEmpty($errors);
    }
}
