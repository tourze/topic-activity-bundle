<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\RichTextComponent;

/**
 * @internal
 */
#[CoversClass(RichTextComponent::class)]
final class RichTextComponentTest extends TestCase
{
    private RichTextComponent $component;

    public function testDefaultProperties(): void
    {
        self::assertSame('', $this->component->content);
        self::assertSame('wysiwyg', $this->component->editorMode);
        self::assertTrue($this->component->allowHtml);
        self::assertTrue($this->component->allowImages);
        self::assertFalse($this->component->allowVideos);
        self::assertSame('full', $this->component->toolbar);
        self::assertSame('transparent', $this->component->backgroundColor);
        self::assertSame('20px', $this->component->padding);
        self::assertSame('', $this->component->customClass);
        self::assertSame('', $this->component->className);
    }

    public function testSetProperties(): void
    {
        $this->component->content = '<p>Rich text content with <strong>formatting</strong></p>';
        $this->component->editorMode = 'markdown';
        $this->component->allowHtml = false;
        $this->component->allowImages = false;
        $this->component->allowVideos = true;
        $this->component->toolbar = 'basic';
        $this->component->backgroundColor = '#ffffff';
        $this->component->padding = '10px';
        $this->component->customClass = 'rich-text-wrapper';
        $this->component->className = 'custom-class';

        self::assertSame('<p>Rich text content with <strong>formatting</strong></p>', $this->component->content);
        self::assertSame('markdown', $this->component->editorMode);
        self::assertFalse($this->component->allowHtml);
        self::assertFalse($this->component->allowImages);
        self::assertTrue($this->component->allowVideos);
        self::assertSame('basic', $this->component->toolbar);
        self::assertSame('#ffffff', $this->component->backgroundColor);
        self::assertSame('10px', $this->component->padding);
        self::assertSame('rich-text-wrapper', $this->component->customClass);
        self::assertSame('custom-class', $this->component->className);
    }

    public function testMount(): void
    {
        $props = [
            'content' => '<p>Test content</p>',
            'editorMode' => 'wysiwyg',
            'allowHtml' => true,
            'allowImages' => true,
            'allowVideos' => false,
            'toolbar' => 'full',
            'backgroundColor' => '#f0f0f0',
            'padding' => '15px',
            'customClass' => 'test-class',
            'className' => 'another-class',
        ];

        $this->component->mount($props);

        self::assertSame('<p>Test content</p>', $this->component->content);
        self::assertSame('wysiwyg', $this->component->editorMode);
        self::assertTrue($this->component->allowHtml);
        self::assertTrue($this->component->allowImages);
        self::assertFalse($this->component->allowVideos);
        self::assertSame('full', $this->component->toolbar);
        self::assertSame('#f0f0f0', $this->component->backgroundColor);
        self::assertSame('15px', $this->component->padding);
        self::assertSame('test-class', $this->component->customClass);
        self::assertSame('another-class', $this->component->className);
    }

    public function testGetProcessedContent(): void
    {
        $this->component->content = '<p>Test paragraph</p><h1>Heading</h1>';

        $processed = $this->component->getProcessedContent();
        self::assertStringContainsString('<p>Test paragraph</p>', $processed);
        self::assertStringContainsString('<h1>Heading</h1>', $processed);
    }

    public function testSanitizeContent(): void
    {
        $unsafeContent = '<script>alert("XSS")</script><p>Safe paragraph</p><style>body{display:none}</style>';
        $this->component->content = $unsafeContent;

        $processed = $this->component->getSanitizedContent();
        self::assertStringNotContainsString('<script>', $processed);
        self::assertStringNotContainsString('<style>', $processed);
        self::assertStringContainsString('Safe paragraph', $processed);
    }

    public function testSanitizeContentWithoutHtml(): void
    {
        $htmlContent = '<p>HTML content</p><strong>bold</strong>';
        $this->component->content = $htmlContent;
        $this->component->allowHtml = false;

        $processed = $this->component->getSanitizedContent();
        self::assertStringContainsString('&lt;p&gt;HTML content&lt;/p&gt;', $processed);
        self::assertStringContainsString('&lt;strong&gt;bold&lt;/strong&gt;', $processed);
    }

    public function testPreserveFormattingTags(): void
    {
        $formattedContent = '<p>Text with <strong>bold</strong>, <em>italic</em>, <u>underline</u>, and <a href="https://example.com">link</a></p>';
        $this->component->content = $formattedContent;

        $processed = $this->component->getSanitizedContent();
        self::assertStringContainsString('<strong>bold</strong>', $processed);
        self::assertStringContainsString('<em>italic</em>', $processed);
        self::assertStringContainsString('<u>underline</u>', $processed);
        self::assertStringContainsString('<a href="https://example.com">link</a>', $processed);
    }

    public function testGetToolbarConfig(): void
    {
        $config = $this->component->getToolbarConfig();
        self::assertContains('bold', $config);
        self::assertContains('italic', $config);
        self::assertContains('link', $config);

        $this->component->toolbar = 'minimal';
        $config = $this->component->getToolbarConfig();
        self::assertEquals(['bold', 'italic', 'link'], $config);

        $this->component->toolbar = 'basic';
        $config = $this->component->getToolbarConfig();
        self::assertContains('bold', $config);
        self::assertContains('bulletList', $config);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = new RichTextComponent();
    }
}
