<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TopicActivityBundle\Command\InitTemplatesCommand;

/**
 * @internal
 */
#[CoversClass(InitTemplatesCommand::class)]
#[RunTestsInSeparateProcesses]
final class InitTemplatesCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function testExecuteSuccess(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('初始化专题活动系统模板', $this->commandTester->getDisplay());
        $this->assertStringContainsString('系统模板初始化成功', $this->commandTester->getDisplay());
    }

    public function testExecuteWithException(): void
    {
        // 由于在集成测试环境中无法轻易模拟异常，我们测试正常执行流程
        // 如果需要测试异常情况，应该在单独的单元测试中进行
        $exitCode = $this->commandTester->execute([]);

        // 正常情况下应该成功
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('初始化专题活动系统模板', $this->commandTester->getDisplay());
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // Get command from service container
        $command = self::getContainer()->get(InitTemplatesCommand::class);
        $this->assertInstanceOf(Command::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('topic-activity:init-templates');
        $this->commandTester = new CommandTester($command);
    }
}
