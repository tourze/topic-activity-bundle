<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Service\AttributeControllerLoader;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Setup logic if needed
    }

    public function testServiceInstance(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertInstanceOf(AttributeControllerLoader::class, $loader);
    }

    public function testAutoload(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->autoload();

        $this->assertNotNull($collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testLoad(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->load('test-resource');

        $this->assertNotNull($collection);
        $this->assertGreaterThan(0, $collection->count());
    }

    public function testSupports(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);

        $this->assertFalse($loader->supports('any-resource'));
        $this->assertFalse($loader->supports('any-resource', 'any-type'));
        $this->assertFalse($loader->supports(null, null));
    }
}
