<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\TopicActivityBundle\DependencyInjection\Compiler\RouteCompilerPass;

class TopicActivityBundle extends Bundle implements BundleDependencyInterface
{
    /**
     * @return array<class-string<Bundle>, array<string, bool>>
     */
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            EasyAdminBundle::class => ['all' => true],
            TwigBundle::class => ['all' => true],
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // 添加路由编译器传递以在测试环境中注册路由
        $container->addCompilerPass(new RouteCompilerPass());

        // 在测试环境中动态配置 Twig 组件命名空间
        if ($container->hasParameter('kernel.environment') && 'test' === $container->getParameter('kernel.environment')) {
            // 配置Twig模板路径
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    __DIR__ . '/../templates' => 'TopicActivity',
                ],
            ]);

            $container->prependExtensionConfig('twig_component', [
                'defaults' => [
                    'Tourze\TopicActivityBundle\Twig\Component\\' => '@TopicActivity/components/',
                ],
            ]);
        }
    }
}
