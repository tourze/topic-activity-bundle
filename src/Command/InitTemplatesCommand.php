<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TopicActivityBundle\Service\TemplateManager;

#[AsCommand(
    name: 'topic-activity:init-templates',
    description: '初始化系统预设模板',
)]
#[Autoconfigure(public: true)]
class InitTemplatesCommand extends Command
{
    public function __construct(
        private readonly TemplateManager $templateManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('初始化专题活动系统模板');

        try {
            $this->templateManager->createSystemTemplates();
            $io->success('系统模板初始化成功！');

            $templates = $this->templateManager->getAvailableTemplates();
            $io->table(
                ['ID', '名称', '代码', '分类', '使用次数'],
                array_map(fn ($t) => [
                    $t->getId(),
                    $t->getName(),
                    $t->getCode(),
                    $t->getCategory(),
                    $t->getUsageCount(),
                ], $templates)
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('模板初始化失败: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
