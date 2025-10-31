<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\TextComponent;

/**
 * @internal
 */
#[CoversClass(TextComponent::class)]
final class TextComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testDefaultValues(): void
    {
        $component = new TextComponent();

        $this->assertSame('', $component->content);
        $this->assertSame('left', $component->alignment);
        $this->assertSame('14px', $component->fontSize);
        $this->assertSame('#333333', $component->color);
        $this->assertSame('transparent', $component->backgroundColor);
        $this->assertSame('10px', $component->padding);
        $this->assertSame('', $component->className);
    }

    public function testGetStyle(): void
    {
        $component = new TextComponent();
        $component->fontSize = '16px';
        $component->color = '#000000';
        $component->backgroundColor = '#ffffff';
        $component->padding = '20px';
        $component->alignment = 'center';

        $style = $component->getStyle();

        $this->assertStringContainsString('font-size: 16px', $style);
        $this->assertStringContainsString('color: #000000', $style);
        $this->assertStringContainsString('background-color: #ffffff', $style);
        $this->assertStringContainsString('padding: 20px', $style);
        $this->assertStringContainsString('text-align: center', $style);
    }

    public function testTransparentBackgroundNotIncludedInStyle(): void
    {
        $component = new TextComponent();
        $component->backgroundColor = 'transparent';

        $style = $component->getStyle();

        $this->assertStringNotContainsString('background-color', $style);
    }

    public function testEmptyPropertiesHandling(): void
    {
        $component = new TextComponent();
        $component->fontSize = '';
        $component->color = '';
        $component->padding = '';
        $component->alignment = '';

        $style = $component->getStyle();

        // 空字符串不应该生成样式
        $this->assertSame('', $style);
    }

    public function testStyleConcatenation(): void
    {
        $component = new TextComponent();
        $component->fontSize = '18px';
        $component->color = 'red';

        $style = $component->getStyle();

        // 检查样式是否正确用分号连接
        $this->assertMatchesRegularExpression('/font-size: 18px;\s*color: red/', $style);
    }
}
