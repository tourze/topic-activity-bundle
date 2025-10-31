<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'activity:file_upload', template: '@TopicActivity/components/file_upload.html.twig')]
class FileUploadComponent
{
    public string $label = '选择文件';

    public string $name = 'file';

    public bool $multiple = false;

    public string $accept = '*';

    public int $maxSize = 10485760;

    public int $maxFiles = 10;

    public bool $required = false;

    public bool $showPreview = true;

    public bool $showProgress = true;

    public string $uploadUrl = '/api/upload';

    public string $buttonText = '点击上传';

    public string $dropText = '或将文件拖放到这里';

    public string $customClass = '';

    /**
     * @param array<string, mixed> $props
     */
    public function mount(array $props = []): void
    {
        $this->initializeStringProperties($props);
        $this->initializeBooleanProperties($props);
        $this->initializeIntegerProperties($props);
    }

    /**
     * @param array<string, mixed> $props
     */
    private function initializeStringProperties(array $props): void
    {
        $label = $props['label'] ?? '选择文件';
        $this->label = is_string($label) ? $label : '选择文件';

        $name = $props['name'] ?? 'file';
        $this->name = is_string($name) ? $name : 'file';

        $accept = $props['accept'] ?? '*';
        $this->accept = is_string($accept) ? $accept : '*';

        $uploadUrl = $props['uploadUrl'] ?? '/api/upload';
        $this->uploadUrl = is_string($uploadUrl) ? $uploadUrl : '/api/upload';

        $buttonText = $props['buttonText'] ?? '点击上传';
        $this->buttonText = is_string($buttonText) ? $buttonText : '点击上传';

        $dropText = $props['dropText'] ?? '或将文件拖放到这里';
        $this->dropText = is_string($dropText) ? $dropText : '或将文件拖放到这里';

        $customClass = $props['customClass'] ?? '';
        $this->customClass = is_string($customClass) ? $customClass : '';
    }

    /**
     * @param array<string, mixed> $props
     */
    private function initializeBooleanProperties(array $props): void
    {
        $this->multiple = (bool) ($props['multiple'] ?? false);
        $this->required = (bool) ($props['required'] ?? false);
        $this->showPreview = (bool) ($props['showPreview'] ?? true);
        $this->showProgress = (bool) ($props['showProgress'] ?? true);
    }

    /**
     * @param array<string, mixed> $props
     */
    private function initializeIntegerProperties(array $props): void
    {
        $maxSize = $props['maxSize'] ?? 10485760;
        $this->maxSize = is_int($maxSize) ? $maxSize : 10485760;

        $maxFiles = $props['maxFiles'] ?? 10;
        $this->maxFiles = is_int($maxFiles) ? $maxFiles : 10;
    }

    public function getAcceptTypes(): string
    {
        $types = [
            'image' => 'image/*',
            'video' => 'video/*',
            'audio' => 'audio/*',
            'pdf' => '.pdf',
            'doc' => '.doc,.docx',
            'excel' => '.xls,.xlsx',
            'zip' => '.zip,.rar,.7z',
        ];

        return $types[$this->accept] ?? $this->accept;
    }

    public function getMaxSizeInMB(): float
    {
        return round($this->maxSize / 1024 / 1024, 2);
    }

    public function getFileTypeIcon(string $type): string
    {
        if (str_starts_with($type, 'image/')) {
            return 'fa-file-image';
        }
        if (str_starts_with($type, 'video/')) {
            return 'fa-file-video';
        }
        if (str_starts_with($type, 'audio/')) {
            return 'fa-file-audio';
        }
        if (str_contains($type, 'pdf')) {
            return 'fa-file-pdf';
        }
        if (str_contains($type, 'word') || str_contains($type, 'document')) {
            return 'fa-file-word';
        }
        if (str_contains($type, 'excel') || str_contains($type, 'spreadsheet')) {
            return 'fa-file-excel';
        }
        if (str_contains($type, 'zip') || str_contains($type, 'compressed')) {
            return 'fa-file-archive';
        }

        return 'fa-file';
    }

    public function getUploadId(): string
    {
        return uniqid('upload_', true);
    }
}
