<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Twig\Component\ImageComponent;

/**
 * @internal
 */
#[CoversClass(ImageComponent::class)]
#[RunTestsInSeparateProcesses]
final class ImageComponentTest extends AbstractIntegrationTestCase
{
    private ImageComponent $component;

    protected function onSetUp(): void
    {
        $this->component = self::getService(ImageComponent::class);
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('', $this->component->src);
        $this->assertSame('', $this->component->alt);
        $this->assertSame('', $this->component->title);
        $this->assertSame('auto', $this->component->width);
        $this->assertSame('auto', $this->component->height);
        $this->assertSame('cover', $this->component->objectFit);
        $this->assertSame('', $this->component->link);
        $this->assertSame('_self', $this->component->linkTarget);
        $this->assertTrue($this->component->lazyLoad);
        $this->assertSame('0', $this->component->borderRadius);
        $this->assertSame('', $this->component->className);
    }

    public function testGetImageStyle(): void
    {
        $this->component->width = '500px';
        $this->component->height = '300px';
        $this->component->objectFit = 'contain';
        $this->component->borderRadius = '10px';

        $style = $this->component->getImageStyle();

        $this->assertStringContainsString('width: 500px', $style);
        $this->assertStringContainsString('height: 300px', $style);
        $this->assertStringContainsString('object-fit: contain', $style);
        $this->assertStringContainsString('border-radius: 10px', $style);
    }

    public function testAutoValuesNotIncludedInStyle(): void
    {
        $this->component->width = 'auto';
        $this->component->height = 'auto';

        $style = $this->component->getImageStyle();

        $this->assertStringNotContainsString('width:', $style);
        $this->assertStringNotContainsString('height:', $style);
    }

    public function testHasLink(): void
    {
        $this->assertFalse($this->component->hasLink());

        $this->component->link = 'https://example.com';
        $this->assertTrue($this->component->hasLink());
    }

    public function testZeroBorderRadiusNotIncluded(): void
    {
        $this->component->borderRadius = '0';

        $style = $this->component->getImageStyle();

        $this->assertStringNotContainsString('border-radius', $style);
    }
}
