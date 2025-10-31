<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\CustomHtmlComponent;

/**
 * @internal
 */
#[CoversClass(CustomHtmlComponent::class)]
final class CustomHtmlComponentTest extends TestCase
{
    private CustomHtmlComponent $component;

    public function testDefaultProperties(): void
    {
        self::assertSame('', $this->component->html);
        self::assertSame('', $this->component->css);
        self::assertSame('', $this->component->javascript);
        self::assertTrue($this->component->sandbox);
        self::assertFalse($this->component->allowScripts);
        self::assertSame('', $this->component->wrapperClass);
        self::assertSame('auto', $this->component->height);
        self::assertSame('transparent', $this->component->backgroundColor);
    }

    public function testSetProperties(): void
    {
        $this->component->html = '<div>Custom HTML Content</div>';
        $this->component->css = '.custom { color: red; }';
        $this->component->javascript = 'console.log("test");';
        $this->component->sandbox = false;
        $this->component->allowScripts = true;
        $this->component->wrapperClass = 'custom-html-wrapper';
        $this->component->height = '200px';
        $this->component->backgroundColor = '#ffffff';

        self::assertSame('<div>Custom HTML Content</div>', $this->component->html);
        self::assertSame('.custom { color: red; }', $this->component->css);
        self::assertSame('console.log("test");', $this->component->javascript);
        self::assertFalse($this->component->sandbox);
        self::assertTrue($this->component->allowScripts);
        self::assertSame('custom-html-wrapper', $this->component->wrapperClass);
        self::assertSame('200px', $this->component->height);
        self::assertSame('#ffffff', $this->component->backgroundColor);
    }

    public function testSanitizeContent(): void
    {
        $unsafeContent = '<script>alert("XSS")</script><div>Safe content</div>';
        $this->component->html = $unsafeContent;

        $sanitized = $this->component->getSanitizedHtml();
        self::assertStringNotContainsString('<script>', $sanitized);
        self::assertStringContainsString('Safe content', $sanitized);
    }

    public function testGetSanitizedContentWithSafeHtml(): void
    {
        $safeContent = '<div class="test"><p>Paragraph</p><a href="https://example.com">Link</a></div>';
        $this->component->html = $safeContent;

        $sanitized = $this->component->getSanitizedHtml();
        self::assertStringContainsString('<div', $sanitized);
        self::assertStringContainsString('<p>Paragraph</p>', $sanitized);
        self::assertStringContainsString('<a href="https://example.com">Link</a>', $sanitized);
    }

    public function testMount(): void
    {
        $props = [
            'html' => '<div>Test HTML</div>',
            'css' => '.test { color: red; }',
            'javascript' => 'console.log("test");',
            'sandbox' => false,
            'allowScripts' => true,
            'wrapperClass' => 'test-class',
            'height' => '300px',
            'backgroundColor' => '#000000',
        ];

        $this->component->mount($props);

        self::assertSame('<div>Test HTML</div>', $this->component->html);
        self::assertSame('.test { color: red; }', $this->component->css);
        self::assertSame('console.log("test");', $this->component->javascript);
        self::assertFalse($this->component->sandbox);
        self::assertTrue($this->component->allowScripts);
        self::assertSame('test-class', $this->component->wrapperClass);
        self::assertSame('300px', $this->component->height);
        self::assertSame('#000000', $this->component->backgroundColor);
    }

    public function testMountWithEmptyProps(): void
    {
        $this->component->mount([]);

        self::assertSame('', $this->component->html);
        self::assertSame('', $this->component->css);
        self::assertSame('', $this->component->javascript);
        self::assertTrue($this->component->sandbox);
        self::assertFalse($this->component->allowScripts);
        self::assertSame('', $this->component->wrapperClass);
        self::assertSame('auto', $this->component->height);
        self::assertSame('transparent', $this->component->backgroundColor);
    }

    public function testGenerateUniqueId(): void
    {
        $id1 = $this->component->generateUniqueId();
        $id2 = $this->component->generateUniqueId();

        $this->assertIsString($id1);
        $this->assertIsString($id2);
        $this->assertNotEquals($id1, $id2);
        $this->assertNotEmpty($id1);
        $this->assertNotEmpty($id2);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = new CustomHtmlComponent();
    }
}
