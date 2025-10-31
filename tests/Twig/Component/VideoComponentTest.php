<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\VideoComponent;

/**
 * @internal
 */
#[CoversClass(VideoComponent::class)]
final class VideoComponentTest extends TestCase
{
    private VideoComponent $component;

    public function testDefaultProperties(): void
    {
        self::assertSame('', $this->component->src);
        self::assertSame('', $this->component->url);
        self::assertSame('', $this->component->poster);
        self::assertTrue($this->component->controls);
        self::assertFalse($this->component->autoplay);
        self::assertFalse($this->component->loop);
        self::assertFalse($this->component->muted);
        self::assertSame('16:9', $this->component->aspectRatio);
        self::assertSame('', $this->component->className);
    }

    public function testSetProperties(): void
    {
        $this->component->url = 'https://example.com/video.mp4';
        $this->component->poster = 'https://example.com/poster.jpg';
        $this->component->controls = false;
        $this->component->autoplay = true;
        $this->component->loop = true;
        $this->component->muted = true;
        $this->component->aspectRatio = '4:3';
        $this->component->className = 'custom-video';

        self::assertSame('https://example.com/video.mp4', $this->component->url);
        self::assertSame('https://example.com/poster.jpg', $this->component->poster);
        self::assertFalse($this->component->controls);
        self::assertTrue($this->component->autoplay);
        self::assertTrue($this->component->loop);
        self::assertTrue($this->component->muted);
        self::assertSame('4:3', $this->component->aspectRatio);
        self::assertSame('custom-video', $this->component->className);
    }

    public function testGetVideoType(): void
    {
        $this->component->src = 'video.mp4';
        self::assertSame('video/mp4', $this->component->getVideoType());

        $this->component->src = 'video.webm';
        self::assertSame('video/webm', $this->component->getVideoType());

        $this->component->src = 'video.ogg';
        self::assertSame('video/ogg', $this->component->getVideoType());

        $this->component->src = 'video.unknown';
        self::assertSame('video/mp4', $this->component->getVideoType());
    }

    public function testGetPaddingBottom(): void
    {
        self::assertSame('56.25%', $this->component->getPaddingBottom());

        $this->component->aspectRatio = '4:3';
        self::assertSame('75.00%', $this->component->getPaddingBottom());

        $this->component->aspectRatio = '21:9';
        self::assertSame('42.86%', $this->component->getPaddingBottom());

        $this->component->aspectRatio = '1:1';
        self::assertSame('100.00%', $this->component->getPaddingBottom());
    }

    public function testGetVideoId(): void
    {
        // Empty URL should return empty string
        self::assertSame('', $this->component->getVideoId());

        // Test with YouTube URL
        $this->component->url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        self::assertSame('dQw4w9WgXcQ', $this->component->getVideoId());

        // Test with YouTube short URL
        $this->component->url = 'https://youtu.be/dQw4w9WgXcQ';
        self::assertSame('dQw4w9WgXcQ', $this->component->getVideoId());

        // Test with regular video URL
        $this->component->url = 'https://example.com/video.mp4';
        self::assertSame('video', $this->component->getVideoId());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = new VideoComponent();
    }
}
