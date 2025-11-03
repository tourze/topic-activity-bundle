<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitBase\TestCaseHelper;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * TopicActivity Bundle 专用的测试基类，确保正确的Dashboard配置
 * @internal
 */
#[CoversClass(AbstractTopicActivityControllerTestCase::class)]
#[RunTestsInSeparateProcesses]
abstract class AbstractTopicActivityControllerTestCase extends AbstractEasyAdminControllerTestCase
{
    /**
     * 构建带有明确Dashboard配置的 EasyAdmin URL
     *
     * @param string $action CRUD 操作
     * @param array<string, mixed> $parameters 额外参数
     */
    protected function generateAdminUrlWithDashboard(string $action, array $parameters = []): string
    {
        $reflection = new \ReflectionClass($this);
        $controllerClass = TestCaseHelper::extractCoverClass($reflection);

        if (null === $controllerClass) {
            throw new \LogicException('Test class must declare a CoversClass attribute');
        }

        /** @var AdminUrlGenerator $generator */
        $generator = clone self::getService(AdminUrlGenerator::class);

        return $generator
            ->unsetAll()
            ->setDashboard('SymfonyTestingFramework\Controller\Admin\DashboardController')
            ->setController($controllerClass)
            ->setAction($action)
            ->setAll($parameters)
            ->generateUrl()
        ;
    }

    /**
     * 重写父类方法，确保返回有效的 Dashboard 控制器
     */
    protected function getPreferredDashboardControllerFqcn(): ?string
    {
        return 'SymfonyTestingFramework\Controller\Admin\DashboardController';
    }

    /**
     * 重写父类方法，确保总是返回一个有效的 Dashboard FQCN
     */
    private function resolveDashboardControllerFqcn(): ?string
    {
        // 直接返回固定的 Dashboard 控制器，避免复杂的注册表查找
        return 'SymfonyTestingFramework\Controller\Admin\DashboardController';
    }

    /**
     * 设置测试环境，包括创建必要的目录
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // 创建文件上传目录
        $uploadDir = sys_get_temp_dir() . '/symfony-test-' . md5(static::class) . '/public/uploads/activities/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    }
}
