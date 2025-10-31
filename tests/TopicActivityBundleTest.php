<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\TopicActivityBundle\TopicActivityBundle;

/**
 * @internal
 */
#[CoversClass(TopicActivityBundle::class)]
#[RunTestsInSeparateProcesses]
final class TopicActivityBundleTest extends AbstractBundleTestCase
{
    private TopicActivityBundle $bundle;

    protected function onSetUp(): void
    {
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $this->bundle = new TopicActivityBundle();
    }

    public function testBundleCanBeInstantiated(): void
    {
        // Act: 创建Bundle对象
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $bundle = new TopicActivityBundle();

        // Assert: 验证Bundle对象
        $this->assertInstanceOf(TopicActivityBundle::class, $bundle);
        $this->assertInstanceOf(BundleInterface::class, $bundle);
    }

    public function testBundleImplementsBundleDependencyInterface(): void
    {
        // Assert: 验证接口实现
        $this->assertInstanceOf(BundleDependencyInterface::class, $this->bundle);
    }

    public function testGetBundleDependenciesReturnsCorrectDependencies(): void
    {
        // Act: 获取Bundle依赖
        $dependencies = TopicActivityBundle::getBundleDependencies();

        // Assert: 验证依赖配置
        $this->assertIsArray($dependencies);
        $this->assertCount(3, $dependencies);

        // 验证DoctrineBundle依赖
        $this->assertArrayHasKey(DoctrineBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[DoctrineBundle::class]);

        // 验证EasyAdminBundle依赖
        $this->assertArrayHasKey(EasyAdminBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[EasyAdminBundle::class]);

        // 验证TwigBundle依赖
        $this->assertArrayHasKey(TwigBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[TwigBundle::class]);
    }

    public function testBundleDependenciesAllHaveAllEnvironmentEnabled(): void
    {
        // Act: 获取依赖配置
        $dependencies = TopicActivityBundle::getBundleDependencies();

        // Assert: 验证所有依赖都在all环境下启用
        foreach ($dependencies as $bundleClass => $config) {
            $this->assertArrayHasKey('all', $config, "Bundle {$bundleClass} should have 'all' environment configured");
            $this->assertTrue($config['all'], "Bundle {$bundleClass} should be enabled in 'all' environment");
        }
    }

    public function testBundleNameIsCorrect(): void
    {
        // Act: 获取Bundle名称
        $name = $this->bundle->getName();

        // Assert: 验证Bundle名称
        $this->assertEquals('TopicActivityBundle', $name);
    }

    public function testBundleNamespaceIsCorrect(): void
    {
        // Act: 获取Bundle命名空间
        $namespace = $this->bundle->getNamespace();

        // Assert: 验证Bundle命名空间
        $this->assertEquals('Tourze\TopicActivityBundle', $namespace);
    }

    public function testGetPath(): void
    {
        // Act: 获取Bundle路径
        $path = $this->bundle->getPath();

        // Assert: 验证Bundle路径包含正确的目录
        $this->assertIsString($path);
        $this->assertStringContainsString('topic-activity-bundle', $path);
        $this->assertDirectoryExists($path);
    }

    public function testBundleCanBeBootedAndShutdown(): void
    {
        // Act & Assert: 模拟Bundle启动和关闭
        // 如果启动或关闭时抛出异常，测试会失败
        $this->bundle->boot();
        $this->bundle->shutdown();

        // 验证Bundle实例仍然有效
        $this->assertInstanceOf(TopicActivityBundle::class, $this->bundle);
    }

    public function testBundleContainerBuilderIntegration(): void
    {
        // 注意：这里只测试Bundle基本功能，不涉及复杂的容器构建
        // 实际的容器集成测试应该在集成测试中进行

        // Act: 获取Bundle基本信息
        $reflection = new \ReflectionClass($this->bundle);

        // Assert: 验证Bundle类的基本特征
        $this->assertTrue($reflection->isSubclassOf(Bundle::class));
        $this->assertTrue($reflection->implementsInterface(BundleDependencyInterface::class));
        $this->assertFalse($reflection->isAbstract());
        $this->assertTrue($reflection->isInstantiable());
    }

    public function testBundleDependencyConfigurationStructure(): void
    {
        // Act: 获取依赖配置
        $dependencies = TopicActivityBundle::getBundleDependencies();

        // Assert: 验证配置结构
        foreach ($dependencies as $bundleClass => $config) {
            $this->assertIsString($bundleClass, 'Bundle class should be a string');
            $this->assertIsArray($config, 'Bundle configuration should be an array');
            $this->assertNotEmpty($bundleClass, 'Bundle class should not be empty');
            $this->assertNotEmpty($config, 'Bundle configuration should not be empty');

            // 验证Bundle类名结尾
            $this->assertStringEndsWith('Bundle', $bundleClass, 'Bundle class should end with "Bundle"');

            // 验证配置结构
            foreach ($config as $environment => $enabled) {
                $this->assertIsString($environment, 'Environment should be a string');
                $this->assertIsBool($enabled, 'Environment status should be boolean');
            }
        }
    }

    public function testStaticMethodAccessibility(): void
    {
        // Act: 检查静态方法可访问性
        $reflection = new \ReflectionClass(TopicActivityBundle::class);
        $method = $reflection->getMethod('getBundleDependencies');

        // Assert: 验证方法特征
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType instanceof \ReflectionNamedType ? $returnType->getName() : 'mixed');
    }
}
