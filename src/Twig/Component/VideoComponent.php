<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(name: 'topic_activity:video', template: '@TopicActivity/components/video.html.twig')]
final class VideoComponent
{
    #[ExposeInTemplate]
    public string $src = '';

    #[ExposeInTemplate]
    public string $poster = '';

    #[ExposeInTemplate]
    public string $url = '';

    #[ExposeInTemplate]
    public bool $autoplay = false;

    #[ExposeInTemplate]
    public bool $controls = true;

    #[ExposeInTemplate]
    public bool $loop = false;

    #[ExposeInTemplate]
    public bool $muted = false;

    #[ExposeInTemplate]
    public string $width = '100%';

    #[ExposeInTemplate]
    public string $height = 'auto';

    #[ExposeInTemplate]
    public string $className = '';

    #[ExposeInTemplate]
    public string $aspectRatio = '16:9';

    public function getVideoStyle(): string
    {
        $styles = [];

        if ('' !== $this->width) {
            $styles[] = sprintf('width: %s', $this->width);
        }

        if ('' !== $this->height && 'auto' !== $this->height) {
            $styles[] = sprintf('height: %s', $this->height);
        }

        return implode('; ', $styles);
    }

    public function getVideoType(): string
    {
        if ('' === $this->src) {
            return 'video/mp4';
        }

        $extension = strtolower(pathinfo($this->src, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            default => 'video/mp4',
        };
    }

    public function getPaddingBottom(): string
    {
        $parts = explode(':', $this->aspectRatio);
        if (2 !== count($parts)) {
            return '56.25%'; // 16:9 default
        }

        $width = (float) $parts[0];
        $height = (float) $parts[1];

        if ($width <= 0) {
            return '56.25%';
        }

        return sprintf('%.2f%%', ($height / $width) * 100);
    }

    public function getVideoId(): string
    {
        if ('' === $this->url) {
            return '';
        }

        // YouTube URL pattern
        $matchResult = preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', $this->url, $matches);
        if ($matchResult > 0) {
            return $matches[1];
        }

        // Generic video URL - return last part
        $path = parse_url($this->url, PHP_URL_PATH);
        if (is_string($path) && '' !== $path) {
            return pathinfo($path, PATHINFO_FILENAME);
        }

        return '';
    }
}
