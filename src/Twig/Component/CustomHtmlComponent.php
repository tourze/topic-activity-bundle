<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'activity:custom_html', template: '@TopicActivity/components/custom_html.html.twig')]
class CustomHtmlComponent
{
    public string $html = '';

    public string $css = '';

    public string $javascript = '';

    public bool $sandbox = true;

    public bool $allowScripts = false;

    public string $wrapperClass = '';

    public string $height = 'auto';

    public string $backgroundColor = 'transparent';

    /**
     * @param array<string, mixed> $props
     */
    public function mount(array $props = []): void
    {
        // 类型安全赋值
        $this->html = is_string($props['html'] ?? null) ? $props['html'] : '';
        $this->css = is_string($props['css'] ?? null) ? $props['css'] : '';
        $this->javascript = is_string($props['javascript'] ?? null) ? $props['javascript'] : '';
        $this->sandbox = is_bool($props['sandbox'] ?? null) ? $props['sandbox'] : true;
        $this->allowScripts = is_bool($props['allowScripts'] ?? null) ? $props['allowScripts'] : false;
        $this->wrapperClass = is_string($props['wrapperClass'] ?? null) ? $props['wrapperClass'] : '';
        $this->height = is_string($props['height'] ?? null) ? $props['height'] : 'auto';
        $this->backgroundColor = is_string($props['backgroundColor'] ?? null) ? $props['backgroundColor'] : 'transparent';
    }

    public function getSanitizedHtml(): string
    {
        if (!$this->allowScripts) {
            // 移除所有script标签和on事件属性
            $html = preg_replace('#<script[^>]*>.*?</script>#is', '', $this->html);
            $html = preg_replace('/\son\w+="[^"]*"/i', '', $html ?? '');
            $html = preg_replace("/\\son\\w+='[^']*'/i", '', $html ?? '');

            return $html ?? '';
        }

        return $this->html;
    }

    public function getSandboxAttributes(): string
    {
        if (!$this->sandbox) {
            return '';
        }

        $attributes = ['allow-same-origin', 'allow-popups', 'allow-forms'];

        if ($this->allowScripts) {
            $attributes[] = 'allow-scripts';
        }

        return 'sandbox="' . implode(' ', $attributes) . '"';
    }

    public function generateUniqueId(): string
    {
        return 'custom-html-' . uniqid();
    }
}
