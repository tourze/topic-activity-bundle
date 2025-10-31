<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\DividerComponent;

/**
 * @internal
 */
#[CoversClass(DividerComponent::class)]
final class DividerComponentTest extends TestCase
{
    private DividerComponent $component;

    public function testDefaultProperties(): void
    {
        self::assertSame('solid', $this->component->style);
        self::assertSame('#e5e5e5', $this->component->color);
        self::assertSame('1px', $this->component->thickness);
        self::assertSame('20px 0', $this->component->margin);
        self::assertSame('100%', $this->component->width);
        self::assertSame('center', $this->component->align);
        self::assertSame('', $this->component->text);
        self::assertFalse($this->component->showIcon);
        self::assertSame('fa-star', $this->component->icon);
    }

    public function testSetProperties(): void
    {
        $this->component->style = 'dashed';
        $this->component->color = '#333333';
        $this->component->thickness = '2px';
        $this->component->margin = '30px 0';
        $this->component->width = '80%';
        $this->component->align = 'left';
        $this->component->text = 'Test Divider';
        $this->component->showIcon = true;
        $this->component->icon = 'fa-heart';

        self::assertSame('dashed', $this->component->style);
        self::assertSame('#333333', $this->component->color);
        self::assertSame('2px', $this->component->thickness);
        self::assertSame('30px 0', $this->component->margin);
        self::assertSame('80%', $this->component->width);
        self::assertSame('left', $this->component->align);
        self::assertSame('Test Divider', $this->component->text);
        self::assertTrue($this->component->showIcon);
        self::assertSame('fa-heart', $this->component->icon);
    }

    public function testMount(): void
    {
        $props = [
            'style' => 'dotted',
            'color' => '#ff0000',
            'thickness' => '3px',
            'margin' => '40px 0',
            'width' => '90%',
            'align' => 'right',
            'text' => 'Custom Divider',
            'showIcon' => true,
            'icon' => 'fa-star',
        ];

        $this->component->mount($props);

        self::assertSame('dotted', $this->component->style);
        self::assertSame('#ff0000', $this->component->color);
        self::assertSame('3px', $this->component->thickness);
        self::assertSame('40px 0', $this->component->margin);
        self::assertSame('90%', $this->component->width);
        self::assertSame('right', $this->component->align);
        self::assertSame('Custom Divider', $this->component->text);
        self::assertTrue($this->component->showIcon);
        self::assertSame('fa-star', $this->component->icon);
    }

    public function testGetAlignStyle(): void
    {
        $style = $this->component->getAlignStyle();
        self::assertStringContainsString('margin-left: auto; margin-right: auto;', $style);

        $this->component->align = 'left';
        $style = $this->component->getAlignStyle();
        self::assertStringContainsString('margin-left: 0; margin-right: auto;', $style);

        $this->component->align = 'right';
        $style = $this->component->getAlignStyle();
        self::assertStringContainsString('margin-left: auto; margin-right: 0;', $style);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = new DividerComponent();
    }
}
