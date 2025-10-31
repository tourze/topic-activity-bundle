<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Component\Basic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Component\Basic\VideoComponent;

/**
 * @internal
 */
#[CoversClass(VideoComponent::class)]
#[RunTestsInSeparateProcesses]
class VideoComponentTest extends AbstractIntegrationTestCase
{
    private VideoComponent $component;

    protected function onSetUp(): void
    {
        $this->component = self::getService(VideoComponent::class);
    }

    public function testGetType(): void
    {
        $this->assertSame('video', $this->component->getType());
    }

    public function testGetName(): void
    {
        $this->assertSame('视频', $this->component->getName());
    }

    public function testGetCategory(): void
    {
        $this->assertSame('basic', $this->component->getCategory());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('fa fa-video', $this->component->getIcon());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('视频播放器组件', $this->component->getDescription());
    }

    public function testGetOrder(): void
    {
        $this->assertSame(40, $this->component->getOrder());
    }

    public function testGetDefaultConfig(): void
    {
        $defaultConfig = $this->component->getDefaultConfig();

        $this->assertIsArray($defaultConfig);
        $this->assertArrayHasKey('src', $defaultConfig);
        $this->assertArrayHasKey('poster', $defaultConfig);
        $this->assertArrayHasKey('autoplay', $defaultConfig);
        $this->assertArrayHasKey('controls', $defaultConfig);
        $this->assertArrayHasKey('loop', $defaultConfig);
        $this->assertArrayHasKey('muted', $defaultConfig);
        $this->assertArrayHasKey('width', $defaultConfig);
        $this->assertArrayHasKey('height', $defaultConfig);
        $this->assertArrayHasKey('className', $defaultConfig);

        $this->assertSame('', $defaultConfig['src']);
        $this->assertSame('', $defaultConfig['poster']);
        $this->assertFalse($defaultConfig['autoplay']);
        $this->assertTrue($defaultConfig['controls']);
        $this->assertFalse($defaultConfig['loop']);
        $this->assertFalse($defaultConfig['muted']);
        $this->assertSame('100%', $defaultConfig['width']);
        $this->assertSame('auto', $defaultConfig['height']);
        $this->assertSame('', $defaultConfig['className']);
    }

    public function testGetConfigSchema(): void
    {
        $schema = $this->component->getConfigSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('src', $schema);
        $this->assertArrayHasKey('poster', $schema);
        $this->assertArrayHasKey('autoplay', $schema);
        $this->assertArrayHasKey('controls', $schema);
        $this->assertArrayHasKey('loop', $schema);
        $this->assertArrayHasKey('muted', $schema);
        $this->assertArrayHasKey('width', $schema);
        $this->assertArrayHasKey('height', $schema);
        $this->assertArrayHasKey('className', $schema);

        $this->assertSame('string', $schema['src']['type']);
        $this->assertTrue($schema['src']['required']);
        $this->assertSame('视频地址', $schema['src']['label']);
        $this->assertSame('video', $schema['src']['editor']);

        $this->assertSame('string', $schema['poster']['type']);
        $this->assertFalse($schema['poster']['required']);
        $this->assertSame('封面图', $schema['poster']['label']);
        $this->assertSame('image', $schema['poster']['editor']);

        $this->assertSame('boolean', $schema['autoplay']['type']);
        $this->assertFalse($schema['autoplay']['required']);
        $this->assertSame('自动播放', $schema['autoplay']['label']);
        $this->assertFalse($schema['autoplay']['default']);

        $this->assertSame('boolean', $schema['controls']['type']);
        $this->assertFalse($schema['controls']['required']);
        $this->assertSame('显示控制栏', $schema['controls']['label']);
        $this->assertTrue($schema['controls']['default']);

        $this->assertSame('boolean', $schema['loop']['type']);
        $this->assertFalse($schema['loop']['required']);
        $this->assertSame('循环播放', $schema['loop']['label']);
        $this->assertFalse($schema['loop']['default']);

        $this->assertSame('boolean', $schema['muted']['type']);
        $this->assertFalse($schema['muted']['required']);
        $this->assertSame('静音', $schema['muted']['label']);
        $this->assertFalse($schema['muted']['default']);

        $this->assertSame('string', $schema['width']['type']);
        $this->assertFalse($schema['width']['required']);
        $this->assertSame('宽度', $schema['width']['label']);
        $this->assertSame('100%', $schema['width']['default']);

        $this->assertSame('string', $schema['height']['type']);
        $this->assertFalse($schema['height']['required']);
        $this->assertSame('高度', $schema['height']['label']);
        $this->assertSame('auto', $schema['height']['default']);

        $this->assertSame('string', $schema['className']['type']);
        $this->assertFalse($schema['className']['required']);
        $this->assertSame('自定义样式类', $schema['className']['label']);
    }

    public function testRender(): void
    {
        $config = [
            'src' => 'https://example.com/video.mp4',
            'poster' => 'https://example.com/poster.jpg',
            'controls' => true,
            'autoplay' => false,
        ];
        $result = $this->component->render($config);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testValidate(): void
    {
        $config = [];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('src', $errors);
        $this->assertStringContainsString('required', $errors['src']);

        $config = ['src' => 'https://example.com/video.mp4'];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);

        $config = [
            'src' => 'https://example.com/video.mp4',
            'autoplay' => true,
            'controls' => false,
            'loop' => true,
            'muted' => true,
        ];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors);
    }

    public function testValidateWithInvalidSrc(): void
    {
        $config = ['src' => 'not-a-url'];
        $errors = $this->component->validate($config);
        $this->assertEmpty($errors); // Basic validation for string type doesn't validate URL format
    }

    public function testValidateWithInvalidTypes(): void
    {
        $config = [
            'src' => 'https://example.com/video.mp4',
            'autoplay' => 'yes',
            'controls' => 'no',
            'width' => 100,
        ];
        $errors = $this->component->validate($config);

        $this->assertArrayHasKey('autoplay', $errors);
        $this->assertStringContainsString('must be of type boolean', $errors['autoplay']);

        $this->assertArrayHasKey('controls', $errors);
        $this->assertStringContainsString('must be of type boolean', $errors['controls']);

        $this->assertArrayHasKey('width', $errors);
        $this->assertStringContainsString('must be of type string', $errors['width']);
    }
}
