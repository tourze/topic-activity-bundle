<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'activity:richtext', template: '@TopicActivity/components/richtext.html.twig')]
class RichTextComponent
{
    public string $content = '';

    public string $editorMode = 'wysiwyg';

    public bool $allowHtml = true;

    public bool $allowImages = true;

    public bool $allowVideos = false;

    public string $toolbar = 'full';

    public string $backgroundColor = 'transparent';

    public string $padding = '20px';

    public string $customClass = '';

    public string $className = '';

    /**
     * @param array<string, mixed> $props
     */
    public function mount(array $props = []): void
    {
        // 类型安全赋值
        $this->content = is_string($props['content'] ?? null) ? $props['content'] : '';
        $this->editorMode = is_string($props['editorMode'] ?? null) ? $props['editorMode'] : 'wysiwyg';
        $this->allowHtml = is_bool($props['allowHtml'] ?? null) ? $props['allowHtml'] : true;
        $this->allowImages = is_bool($props['allowImages'] ?? null) ? $props['allowImages'] : true;
        $this->allowVideos = is_bool($props['allowVideos'] ?? null) ? $props['allowVideos'] : false;
        $this->toolbar = is_string($props['toolbar'] ?? null) ? $props['toolbar'] : 'full';
        $this->backgroundColor = is_string($props['backgroundColor'] ?? null) ? $props['backgroundColor'] : 'transparent';
        $this->padding = is_string($props['padding'] ?? null) ? $props['padding'] : '20px';
        $this->customClass = is_string($props['customClass'] ?? null) ? $props['customClass'] : '';
        $this->className = is_string($props['className'] ?? null) ? $props['className'] : '';
    }

    public function getProcessedContent(): string
    {
        return $this->getSanitizedContent();
    }

    public function getSanitizedContent(): string
    {
        if (!$this->allowHtml) {
            return htmlspecialchars($this->content, ENT_QUOTES, 'UTF-8');
        }

        $allowedTags = '<p><br><strong><b><em><i><u><s><del><ins><blockquote><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div><pre><code><table><thead><tbody><tr><td><th>';

        if ($this->allowImages) {
            $allowedTags .= '<img><figure><figcaption>';
        }

        if ($this->allowVideos) {
            $allowedTags .= '<video><iframe>';
        }

        return strip_tags($this->content, $allowedTags);
    }

    /**
     * @return array<string>
     */
    public function getToolbarConfig(): array
    {
        $configs = [
            'full' => ['bold', 'italic', 'underline', 'strike', 'link', 'bulletList', 'orderedList', 'blockquote', 'code', 'heading', 'image', 'table'],
            'basic' => ['bold', 'italic', 'link', 'bulletList', 'orderedList'],
            'minimal' => ['bold', 'italic', 'link'],
        ];

        return $configs[$this->toolbar] ?? $configs['basic'];
    }
}
