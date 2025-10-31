<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\ButtonComponent;

/**
 * @internal
 */
#[CoversClass(ButtonComponent::class)]
final class ButtonComponentTest extends TestCase
{
    private ButtonComponent $component;

    public function testDefaultProperties(): void
    {
        self::assertSame('点击按钮', $this->component->text);
        self::assertSame('', $this->component->link);
        self::assertSame('primary', $this->component->style);
        self::assertSame('medium', $this->component->size);
        self::assertSame('auto', $this->component->width);
        self::assertSame('_self', $this->component->linkTarget);
        self::assertSame('', $this->component->className);
        self::assertSame('', $this->component->icon);
        self::assertSame('left', $this->component->iconPosition);
        self::assertFalse($this->component->disabled);
        self::assertSame('4px', $this->component->borderRadius);
    }

    public function testSetProperties(): void
    {
        $this->component->text = 'Click Me';
        $this->component->link = 'https://example.com';
        $this->component->style = 'danger';
        $this->component->size = 'large';
        $this->component->width = '200px';
        $this->component->linkTarget = '_blank';
        $this->component->className = 'custom-class';
        $this->component->icon = 'fa-check';
        $this->component->iconPosition = 'right';
        $this->component->disabled = true;
        $this->component->borderRadius = '8px';

        self::assertSame('Click Me', $this->component->text);
        self::assertSame('https://example.com', $this->component->link);
        self::assertSame('danger', $this->component->style);
        self::assertSame('large', $this->component->size);
        self::assertSame('200px', $this->component->width);
        self::assertSame('_blank', $this->component->linkTarget);
        self::assertSame('custom-class', $this->component->className);
        self::assertSame('fa-check', $this->component->icon);
        self::assertSame('right', $this->component->iconPosition);
        self::assertTrue($this->component->disabled);
        self::assertSame('8px', $this->component->borderRadius);
    }

    public function testGetButtonClass(): void
    {
        self::assertStringContainsString('btn', $this->component->getButtonClass());
        self::assertStringContainsString('btn-primary', $this->component->getButtonClass());

        $this->component->style = 'success';
        $this->component->size = 'small';
        self::assertStringContainsString('btn-success', $this->component->getButtonClass());
        self::assertStringContainsString('btn-sm', $this->component->getButtonClass());

        $this->component->disabled = true;
        self::assertStringContainsString('disabled', $this->component->getButtonClass());

        $this->component->className = 'extra-class';
        self::assertStringContainsString('extra-class', $this->component->getButtonClass());
    }

    public function testGetButtonStyle(): void
    {
        self::assertStringContainsString('border-radius: 4px', $this->component->getButtonStyle());

        $this->component->width = '300px';
        $this->component->borderRadius = '10px';
        $style = $this->component->getButtonStyle();
        self::assertStringContainsString('width: 300px', $style);
        self::assertStringContainsString('border-radius: 10px', $style);
    }

    public function testHasIcon(): void
    {
        self::assertFalse($this->component->hasIcon());

        $this->component->icon = 'fa-home';
        self::assertTrue($this->component->hasIcon());
    }

    public function testHasLink(): void
    {
        self::assertFalse($this->component->hasLink());

        $this->component->link = 'https://example.com';
        self::assertTrue($this->component->hasLink());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = new ButtonComponent();
    }
}
