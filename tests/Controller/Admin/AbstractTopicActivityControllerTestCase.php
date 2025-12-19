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
     * 在 EasyAdmin 设置之后创建必要的目录
     */
    protected function afterEasyAdminSetUp(): void
    {
        parent::afterEasyAdminSetUp();

        // 创建当前测试类的目录
        $testClass = $this->getCurrentTestClass();
        $testDir = sys_get_temp_dir() . '/symfony-test-' . md5($testClass);

        $uploadDir = $testDir . '/public/uploads/activities/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $templateUploadDir = $testDir . '/public/uploads/templates/';
        if (!is_dir($templateUploadDir)) {
            mkdir($templateUploadDir, 0777, true);
        }
    }

    /**
     * 获取当前测试类名
     */
    protected function getCurrentTestClass(): string
    {
        return get_class($this);
    }

    /**
     * 设置测试环境，包括创建必要的目录
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::createKnownTestDirectories();
        self::createSpecificTestDirectories();
        self::createRandomTestDirectories();
        self::definePublicPath();
    }

    /**
     * 创建已知测试类的目录
     */
    private static function createKnownTestDirectories(): void
    {
        $possibleTestClasses = [
            'Tourze\\TopicActivityBundle\\Tests\\Controller\\Admin\\ActivityCrudControllerTest',
            'Tourze\\TopicActivityBundle\\Tests\\Controller\\Admin\\ActivityComponentCrudControllerTest',
            'Tourze\\TopicActivityBundle\\Tests\\Controller\\Admin\\ActivityTemplateCrudControllerTest',
            'Tourze\\TopicActivityBundle\\Tests\\Controller\\Admin\\ActivityStatsCrudControllerTest',
            'Tourze\\TopicActivityBundle\\Tests\\Controller\\Admin\\ActivityEventCrudControllerTest',
            'Tourze\\TopicActivityBundle\\Tests\\Controller\\Admin\\AbstractTopicActivityControllerTestCase',
        ];

        foreach ($possibleTestClasses as $testClass) {
            self::createTestDirectories($testClass);
        }
    }

    /**
     * 创建特定的测试目录
     */
    private static function createSpecificTestDirectories(): void
    {
        $specificTestDir = sys_get_temp_dir() . '/symfony-test-Tourze2f0949945ae63b8b456e7215fc3c9690';
        self::createUploadDirectories($specificTestDir);
    }

    /**
     * 创建随机测试目录
     */
    private static function createRandomTestDirectories(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $randomTestDir = sys_get_temp_dir() . '/symfony-test-' . md5(uniqid('Tourze', true));
            self::createUploadDirectories($randomTestDir);
        }
    }

    /**
     * 定义公共路径常量
     */
    private static function definePublicPath(): void
    {
        if (!defined('TEST_PUBLIC_PATH')) {
            $currentClass = self::class;
            define('TEST_PUBLIC_PATH', sys_get_temp_dir() . '/symfony-test-' . md5($currentClass) . '/public');
        }
    }

    /**
     * 为指定测试类创建目录
     */
    private static function createTestDirectories(string $testClass): void
    {
        $testDir = sys_get_temp_dir() . '/symfony-test-' . md5($testClass);
        self::createUploadDirectories($testDir);
    }

    /**
     * 创建上传相关目录
     */
    private static function createUploadDirectories(string $testDir): void
    {
        $uploadDir = $testDir . '/public/uploads/activities/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $templateUploadDir = $testDir . '/public/uploads/templates/';
        if (!is_dir($templateUploadDir)) {
            mkdir($templateUploadDir, 0777, true);
        }
    }
}
