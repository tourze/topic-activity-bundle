<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Twig\Component\TextComponent;

/**
 * @internal
 */
#[CoversClass(TextComponent::class)]
#[RunTestsInSeparateProcesses]
final class TextComponentTest extends AbstractIntegrationTestCase
{
    private TextComponent $component;

    protected function onSetUp(): void
    {
        $this->component = self::getService(TextComponent::class);
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('', $this->component->content);
        $this->assertSame('left', $this->component->alignment);
        $this->assertSame('14px', $this->component->fontSize);
        $this->assertSame('#333333', $this->component->color);
        $this->assertSame('transparent', $this->component->backgroundColor);
        $this->assertSame('10px', $this->component->padding);
        $this->assertSame('', $this->component->className);
    }

    public function testGetStyle(): void
    {
        $this->component->fontSize = '16px';
        $this->component->color = '#000000';
        $this->component->backgroundColor = '#ffffff';
        $this->component->padding = '20px';
        $this->component->alignment = 'center';

        $style = $this->component->getStyle();

        $this->assertStringContainsString('font-size: 16px', $style);
        $this->assertStringContainsString('color: #000000', $style);
        $this->assertStringContainsString('background-color: #ffffff', $style);
        $this->assertStringContainsString('padding: 20px', $style);
        $this->assertStringContainsString('text-align: center', $style);
    }

    public function testTransparentBackgroundNotIncludedInStyle(): void
    {
        $this->component->backgroundColor = 'transparent';

        $style = $this->component->getStyle();

        $this->assertStringNotContainsString('background-color', $style);
    }

    public function testEmptyPropertiesHandling(): void
    {
        $this->component->fontSize = '';
        $this->component->color = '';
        $this->component->padding = '';
        $this->component->alignment = '';

        $style = $this->component->getStyle();

        // 空字符串不应该生成样式
        $this->assertSame('', $style);
    }

    public function testStyleConcatenation(): void
    {
        $this->component->fontSize = '18px';
        $this->component->color = 'red';

        $style = $this->component->getStyle();

        // 检查样式是否正确用分号连接
        $this->assertMatchesRegularExpression('/font-size: 18px;\s*color: red/', $style);
    }
}
