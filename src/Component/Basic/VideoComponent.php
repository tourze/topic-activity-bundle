<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component\Basic;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TopicActivityBundle\Component\AbstractComponent;

#[Autoconfigure(tags: ['topic_activity.component'])]
class VideoComponent extends AbstractComponent
{
    protected string $type = 'video';

    protected string $name = '视频';

    protected string $category = 'basic';

    protected string $icon = 'fa fa-video';

    protected string $description = '视频播放器组件';

    protected int $order = 40;

    public function getDefaultConfig(): array
    {
        return [
            'src' => '',
            'poster' => '',
            'autoplay' => false,
            'controls' => true,
            'loop' => false,
            'muted' => false,
            'width' => '100%',
            'height' => 'auto',
            'className' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'src' => [
                'type' => 'string',
                'required' => true,
                'label' => '视频地址',
                'editor' => 'video',
            ],
            'poster' => [
                'type' => 'string',
                'required' => false,
                'label' => '封面图',
                'editor' => 'image',
            ],
            'autoplay' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '自动播放',
                'default' => false,
            ],
            'controls' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '显示控制栏',
                'default' => true,
            ],
            'loop' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '循环播放',
                'default' => false,
            ],
            'muted' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '静音',
                'default' => false,
            ],
            'width' => [
                'type' => 'string',
                'required' => false,
                'label' => '宽度',
                'default' => '100%',
            ],
            'height' => [
                'type' => 'string',
                'required' => false,
                'label' => '高度',
                'default' => 'auto',
            ],
            'className' => [
                'type' => 'string',
                'required' => false,
                'label' => '自定义样式类',
            ],
        ];
    }
}
