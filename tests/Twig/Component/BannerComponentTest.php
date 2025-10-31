<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\BannerComponent;

/**
 * @internal
 */
#[CoversClass(BannerComponent::class)]
class BannerComponentTest extends TestCase
{
    private BannerComponent $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = new BannerComponent();
    }

    public function testDefaultProperties(): void
    {
        $this->assertSame([], $this->component->images);
        $this->assertTrue($this->component->autoplay);
        $this->assertSame(3000, $this->component->interval);
        $this->assertTrue($this->component->showIndicators);
        $this->assertTrue($this->component->showArrows);
        $this->assertSame('400px', $this->component->height);
        $this->assertSame('cover', $this->component->objectFit);
        $this->assertSame('0', $this->component->borderRadius);
        $this->assertSame('', $this->component->className);
    }

    public function testGetContainerStyleDefault(): void
    {
        $this->component->height = '300px';
        $this->component->borderRadius = '0';

        $result = $this->component->getContainerStyle();

        $this->assertSame('height: 300px', $result);
    }

    public function testGetContainerStyleWithBorderRadius(): void
    {
        $this->component->height = '500px';
        $this->component->borderRadius = '10px';

        $result = $this->component->getContainerStyle();

        $this->assertSame('height: 500px; border-radius: 10px; overflow: hidden', $result);
    }

    public function testGetContainerStyleWithoutHeight(): void
    {
        $this->component->height = '';
        $this->component->borderRadius = '8px';

        $result = $this->component->getContainerStyle();

        $this->assertSame('border-radius: 8px; overflow: hidden', $result);
    }

    public function testGetContainerStyleEmpty(): void
    {
        $this->component->height = '';
        $this->component->borderRadius = '0';

        $result = $this->component->getContainerStyle();

        $this->assertSame('', $result);
    }

    public function testGetImageStyle(): void
    {
        $this->component->height = '250px';
        $this->component->objectFit = 'contain';

        $result = $this->component->getImageStyle();

        $expected = 'width: 100%; height: 250px; object-fit: contain';
        $this->assertSame($expected, $result);
    }

    public function testGetImageStyleDefaults(): void
    {
        $result = $this->component->getImageStyle();

        $expected = 'width: 100%; height: 400px; object-fit: cover';
        $this->assertSame($expected, $result);
    }

    public function testHasImagesEmpty(): void
    {
        $this->component->images = [];

        $this->assertFalse($this->component->hasImages());
    }

    public function testHasImagesWithImages(): void
    {
        $this->component->images = [
            ['src' => '/image1.jpg', 'alt' => 'Image 1'],
            ['src' => '/image2.jpg', 'alt' => 'Image 2'],
        ];

        $this->assertTrue($this->component->hasImages());
    }

    public function testGetImagesCountEmpty(): void
    {
        $this->component->images = [];

        $this->assertSame(0, $this->component->getImagesCount());
    }

    public function testGetImagesCountWithImages(): void
    {
        $this->component->images = [
            ['src' => '/image1.jpg'],
            ['src' => '/image2.jpg'],
            ['src' => '/image3.jpg'],
        ];

        $this->assertSame(3, $this->component->getImagesCount());
    }

    public function testGetCarouselId(): void
    {
        $id1 = $this->component->getCarouselId();
        $id2 = $this->component->getCarouselId();

        $this->assertStringStartsWith('carousel-', $id1);
        $this->assertStringStartsWith('carousel-', $id2);
        $this->assertNotSame($id1, $id2); // Should generate unique IDs
    }

    public function testPropertiesArePublicAndModifiable(): void
    {
        $this->component->images = [
            ['src' => '/test.jpg', 'alt' => 'Test', 'link' => 'https://example.com'],
        ];
        $this->component->autoplay = false;
        $this->component->interval = 5000;
        $this->component->showIndicators = false;
        $this->component->showArrows = false;
        $this->component->height = '600px';
        $this->component->objectFit = 'fill';
        $this->component->borderRadius = '15px';
        $this->component->className = 'custom-banner';

        $this->assertCount(1, $this->component->images);
        $this->assertSame('/test.jpg', $this->component->images[0]['src']);
        $this->assertArrayHasKey('alt', $this->component->images[0]);
        $this->assertSame('Test', $this->component->images[0]['alt']);
        $this->assertArrayHasKey('link', $this->component->images[0]);
        $this->assertSame('https://example.com', $this->component->images[0]['link']);
        $this->assertFalse($this->component->autoplay);
        $this->assertSame(5000, $this->component->interval);
        $this->assertFalse($this->component->showIndicators);
        $this->assertFalse($this->component->showArrows);
        $this->assertSame('600px', $this->component->height);
        $this->assertSame('fill', $this->component->objectFit);
        $this->assertSame('15px', $this->component->borderRadius);
        $this->assertSame('custom-banner', $this->component->className);
    }

    public function testComplexImageConfiguration(): void
    {
        $images = [
            ['src' => '/banner1.jpg', 'alt' => 'Banner 1', 'link' => 'https://example1.com'],
            ['src' => '/banner2.jpg', 'alt' => 'Banner 2'],
            ['src' => '/banner3.jpg', 'link' => 'https://example3.com'],
            ['src' => '/banner4.jpg'],
        ];

        $this->component->images = $images;

        $this->assertTrue($this->component->hasImages());
        $this->assertSame(4, $this->component->getImagesCount());
        $this->assertSame($images, $this->component->images);

        // Test individual image properties
        $this->assertArrayHasKey('link', $this->component->images[0]);
        $this->assertSame('https://example1.com', $this->component->images[0]['link']);
        $this->assertArrayHasKey('alt', $this->component->images[0]);
        $this->assertSame('Banner 1', $this->component->images[0]['alt']);
        $this->assertArrayNotHasKey('link', $this->component->images[1]);
        $this->assertArrayNotHasKey('alt', $this->component->images[2]);
        $this->assertArrayNotHasKey('alt', $this->component->images[3]);
        $this->assertArrayNotHasKey('link', $this->component->images[3]);
    }
}
