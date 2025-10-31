<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // 只在测试环境中注册路由
        if ('test' !== $container->getParameter('kernel.environment')) {
            return;
        }

        // 将Bundle的控制器路由添加到路由配置中
        $this->addRouteResource($container);
    }

    private function addRouteResource(ContainerBuilder $container): void
    {
        // 为测试环境添加路由资源
        $routeResource = [
            'resource' => dirname(__DIR__, 2) . '/Controller/',
            'type' => 'attribute',
        ];

        // 如果有路由加载器定义，添加我们的路由资源
        if ($container->hasDefinition('routing.loader.annotation.directory')) {
            $definition = $container->getDefinition('routing.loader.annotation.directory');
            $definition->addMethodCall('setRouteResource', [$routeResource]);
        }

        // 创建一个新的路由定义来加载我们的控制器
        $routeDefinition = new Definition();
        $routeDefinition->setClass('Symfony\Component\Routing\RouteCollection');
        $routeDefinition->setFactory([new Reference('routing.loader.annotation.directory'), 'load']);
        $routeDefinition->setArguments([dirname(__DIR__, 2) . '/Controller/', 'attribute']);

        $container->setDefinition('topic_activity.routes', $routeDefinition);
    }
}
