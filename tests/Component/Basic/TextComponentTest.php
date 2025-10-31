<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Component\Basic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Component\Basic\TextComponent;

/**
 * @internal
 */
#[CoversClass(TextComponent::class)]
#[RunTestsInSeparateProcesses]
class TextComponentTest extends AbstractIntegrationTestCase
{
    private TextComponent $component;

    protected function onSetUp(): void
    {
        $this->component = self::getService(TextComponent::class);
    }

    public function testGetType(): void
    {
        $this->assertSame('text', $this->component->getType());
    }

    public function testGetName(): void
    {
        $this->assertSame('文本', $this->component->getName());
    }

    public function testGetCategory(): void
    {
        $this->assertSame('basic', $this->component->getCategory());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('fa fa-font', $this->component->getIcon());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('富文本编辑器，支持格式化文本内容', $this->component->getDescription());
    }

    public function testGetOrder(): void
    {
        $this->assertSame(10, $this->component->getOrder());
    }

    public function testGetDefaultConfig(): void
    {
        $defaultConfig = $this->component->getDefaultConfig();

        $this->assertIsArray($defaultConfig);
        $this->assertArrayHasKey('content', $defaultConfig);
        $this->assertArrayHasKey('alignment', $defaultConfig);
        $this->assertArrayHasKey('fontSize', $defaultConfig);
        $this->assertArrayHasKey('color', $defaultConfig);
        $this->assertArrayHasKey('backgroundColor', $defaultConfig);
        $this->assertArrayHasKey('padding', $defaultConfig);
        $this->assertArrayHasKey('className', $defaultConfig);

        $this->assertSame('', $defaultConfig['content']);
        $this->assertSame('left', $defaultConfig['alignment']);
        $this->assertSame('14px', $defaultConfig['fontSize']);
        $this->assertSame('#333333', $defaultConfig['color']);
        $this->assertSame('transparent', $defaultConfig['backgroundColor']);
        $this->assertSame('10px', $defaultConfig['padding']);
        $this->assertSame('', $defaultConfig['className']);
    }

    public function testGetConfigSchema(): void
    {
        $schema = $this->component->getConfigSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('content', $schema);
        $this->assertArrayHasKey('alignment', $schema);
        $this->assertArrayHasKey('fontSize', $schema);
        $this->assertArrayHasKey('color', $schema);
        $this->assertArrayHasKey('backgroundColor', $schema);
        $this->assertArrayHasKey('padding', $schema);
        $this->assertArrayHasKey('className', $schema);

        $this->assertSame('string', $schema['content']['type']);
        $this->assertFalse($schema['content']['required']);
        $this->assertSame('文本内容', $schema['content']['label']);
        $this->assertSame('richtext', $schema['content']['editor']);

        $this->assertSame('string', $schema['alignment']['type']);
        $this->assertFalse($schema['alignment']['required']);
        $this->assertSame('对齐方式', $schema['alignment']['label']);
        $this->assertSame(['left', 'center', 'right', 'justify'], $schema['alignment']['options']);
        $this->assertSame('left', $schema['alignment']['default']);

        $this->assertSame('string', $schema['fontSize']['type']);
        $this->assertFalse($schema['fontSize']['required']);
        $this->assertSame('字体大小', $schema['fontSize']['label']);
        $this->assertSame('14px', $schema['fontSize']['default']);

        $this->assertSame('string', $schema['color']['type']);
        $this->assertFalse($schema['color']['required']);
        $this->assertSame('文字颜色', $schema['color']['label']);
        $this->assertSame('color', $schema['color']['editor']);
        $this->assertSame('#333333', $schema['color']['default']);

        $this->assertSame('string', $schema['backgroundColor']['type']);
        $this->assertFalse($schema['backgroundColor']['required']);
        $this->assertSame('背景颜色', $schema['backgroundColor']['label']);
        $this->assertSame('color', $schema['backgroundColor']['editor']);
        $this->assertSame('transparent', $schema['backgroundColor']['default']);
    }

    public function testRender(): void
    {
        $config = [
            'content' => '<p>Test content</p>',
            'alignment' => 'center',
            'color' => '#000000',
        ];
        $result = $this->component->render($config);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testValidate(): void
    {
        $config = [];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);

        $config = [
            'content' => 'Valid content',
            'alignment' => 'left',
            'fontSize' => '16px',
            'color' => '#ffffff',
        ];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);

        $config = ['fontSize' => 16];
        $errors = $this->component->validate($config);
        $this->assertArrayHasKey('fontSize', $errors);
        $this->assertStringContainsString('must be of type string', $errors['fontSize']);
    }

    public function testValidateWithInvalidAlignment(): void
    {
        $config = ['alignment' => 'invalid-alignment'];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors); // Basic validation doesn't check options
    }
}
