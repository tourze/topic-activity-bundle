<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\TopicActivityBundle\DependencyInjection\Compiler\RouteCompilerPass;

/**
 * @internal
 */
#[CoversClass(RouteCompilerPass::class)]
class RouteCompilerPassTest extends TestCase
{
    private RouteCompilerPass $compilerPass;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compilerPass = new RouteCompilerPass();
        $this->container = new ContainerBuilder();
    }

    public function testProcessInTestEnvironment(): void
    {
        $this->container->setParameter('kernel.environment', 'test');

        $this->compilerPass->process($this->container);

        $this->assertTrue($this->container->hasDefinition('topic_activity.routes'));
    }

    public function testProcessInNonTestEnvironment(): void
    {
        $this->container->setParameter('kernel.environment', 'prod');

        $this->compilerPass->process($this->container);

        $this->assertFalse($this->container->hasDefinition('topic_activity.routes'));
    }

    public function testProcessInDevEnvironment(): void
    {
        $this->container->setParameter('kernel.environment', 'dev');

        $this->compilerPass->process($this->container);

        $this->assertFalse($this->container->hasDefinition('topic_activity.routes'));
    }
}
