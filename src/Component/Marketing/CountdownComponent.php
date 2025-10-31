<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Component\Marketing;

use Tourze\TopicActivityBundle\Component\AbstractComponent;

class CountdownComponent extends AbstractComponent
{
    protected string $type = 'countdown';

    protected string $name = '倒计时';

    protected string $category = 'marketing';

    protected string $icon = 'fa fa-clock';

    protected string $description = '活动倒计时显示';

    protected int $order = 110;

    public function getDefaultConfig(): array
    {
        return [
            'endTime' => '',
            'format' => 'DD天 HH时 MM分 SS秒',
            'showDays' => true,
            'showHours' => true,
            'showMinutes' => true,
            'showSeconds' => true,
            'prefix' => '距离活动结束还有',
            'suffix' => '',
            'expiredText' => '活动已结束',
            'fontSize' => '24px',
            'color' => '#ff0000',
            'backgroundColor' => '#fff',
            'padding' => '20px',
            'borderRadius' => '8px',
            'className' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'endTime' => [
                'type' => 'string',
                'required' => true,
                'label' => '结束时间',
                'editor' => 'datetime',
            ],
            'format' => [
                'type' => 'string',
                'required' => false,
                'label' => '显示格式',
                'default' => 'DD天 HH时 MM分 SS秒',
            ],
            'showDays' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '显示天数',
                'default' => true,
            ],
            'showHours' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '显示小时',
                'default' => true,
            ],
            'showMinutes' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '显示分钟',
                'default' => true,
            ],
            'showSeconds' => [
                'type' => 'boolean',
                'required' => false,
                'label' => '显示秒数',
                'default' => true,
            ],
            'prefix' => [
                'type' => 'string',
                'required' => false,
                'label' => '前缀文字',
                'maxLength' => 100,
            ],
            'suffix' => [
                'type' => 'string',
                'required' => false,
                'label' => '后缀文字',
                'maxLength' => 100,
            ],
            'expiredText' => [
                'type' => 'string',
                'required' => false,
                'label' => '过期提示',
                'default' => '活动已结束',
            ],
            'fontSize' => [
                'type' => 'string',
                'required' => false,
                'label' => '字体大小',
                'default' => '24px',
            ],
            'color' => [
                'type' => 'string',
                'required' => false,
                'label' => '文字颜色',
                'editor' => 'color',
                'default' => '#ff0000',
            ],
            'backgroundColor' => [
                'type' => 'string',
                'required' => false,
                'label' => '背景颜色',
                'editor' => 'color',
                'default' => '#fff',
            ],
            'padding' => [
                'type' => 'string',
                'required' => false,
                'label' => '内边距',
                'default' => '20px',
            ],
            'borderRadius' => [
                'type' => 'string',
                'required' => false,
                'label' => '圆角',
                'default' => '8px',
            ],
            'className' => [
                'type' => 'string',
                'required' => false,
                'label' => '自定义样式类',
            ],
        ];
    }
}
