<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\ImageComponent;

/**
 * @internal
 */
#[CoversClass(ImageComponent::class)]
final class ImageComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testDefaultValues(): void
    {
        $component = new ImageComponent();

        $this->assertSame('', $component->src);
        $this->assertSame('', $component->alt);
        $this->assertSame('', $component->title);
        $this->assertSame('auto', $component->width);
        $this->assertSame('auto', $component->height);
        $this->assertSame('cover', $component->objectFit);
        $this->assertSame('', $component->link);
        $this->assertSame('_self', $component->linkTarget);
        $this->assertTrue($component->lazyLoad);
        $this->assertSame('0', $component->borderRadius);
        $this->assertSame('', $component->className);
    }

    public function testGetImageStyle(): void
    {
        $component = new ImageComponent();
        $component->width = '500px';
        $component->height = '300px';
        $component->objectFit = 'contain';
        $component->borderRadius = '10px';

        $style = $component->getImageStyle();

        $this->assertStringContainsString('width: 500px', $style);
        $this->assertStringContainsString('height: 300px', $style);
        $this->assertStringContainsString('object-fit: contain', $style);
        $this->assertStringContainsString('border-radius: 10px', $style);
    }

    public function testAutoValuesNotIncludedInStyle(): void
    {
        $component = new ImageComponent();
        $component->width = 'auto';
        $component->height = 'auto';

        $style = $component->getImageStyle();

        $this->assertStringNotContainsString('width:', $style);
        $this->assertStringNotContainsString('height:', $style);
    }

    public function testHasLink(): void
    {
        $component = new ImageComponent();

        $this->assertFalse($component->hasLink());

        $component->link = 'https://example.com';
        $this->assertTrue($component->hasLink());
    }

    public function testZeroBorderRadiusNotIncluded(): void
    {
        $component = new ImageComponent();
        $component->borderRadius = '0';

        $style = $component->getImageStyle();

        $this->assertStringNotContainsString('border-radius', $style);
    }
}
