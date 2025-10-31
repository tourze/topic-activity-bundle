<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Twig\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TopicActivityBundle\Twig\Component\FileUploadComponent;

/**
 * @internal
 */
#[CoversClass(FileUploadComponent::class)]
final class FileUploadComponentTest extends TestCase
{
    private FileUploadComponent $component;

    public function testDefaultProperties(): void
    {
        self::assertSame('选择文件', $this->component->label);
        self::assertSame('file', $this->component->name);
        self::assertFalse($this->component->multiple);
        self::assertSame('*', $this->component->accept);
        self::assertSame(10485760, $this->component->maxSize);
        self::assertSame(10, $this->component->maxFiles);
        self::assertFalse($this->component->required);
        self::assertTrue($this->component->showPreview);
        self::assertTrue($this->component->showProgress);
        self::assertSame('/api/upload', $this->component->uploadUrl);
        self::assertSame('点击上传', $this->component->buttonText);
        self::assertSame('或将文件拖放到这里', $this->component->dropText);
        self::assertSame('', $this->component->customClass);
    }

    public function testSetProperties(): void
    {
        $this->component->label = '上传图片';
        $this->component->name = 'image';
        $this->component->multiple = true;
        $this->component->accept = 'image/*';
        $this->component->maxSize = 5242880;
        $this->component->maxFiles = 5;
        $this->component->required = true;
        $this->component->showPreview = false;
        $this->component->showProgress = false;
        $this->component->uploadUrl = '/custom/upload';
        $this->component->buttonText = '选择图片';
        $this->component->dropText = '拖放图片到此处';
        $this->component->customClass = 'custom-upload';

        self::assertSame('上传图片', $this->component->label);
        self::assertSame('image', $this->component->name);
        self::assertTrue($this->component->multiple);
        self::assertSame('image/*', $this->component->accept);
        self::assertSame(5242880, $this->component->maxSize);
        self::assertSame(5, $this->component->maxFiles);
        self::assertTrue($this->component->required);
        self::assertFalse($this->component->showPreview);
        self::assertFalse($this->component->showProgress);
        self::assertSame('/custom/upload', $this->component->uploadUrl);
        self::assertSame('选择图片', $this->component->buttonText);
        self::assertSame('拖放图片到此处', $this->component->dropText);
        self::assertSame('custom-upload', $this->component->customClass);
    }

    public function testMount(): void
    {
        $props = [
            'label' => '上传文档',
            'name' => 'document',
            'multiple' => true,
            'accept' => 'pdf',
            'maxSize' => 20971520,
            'maxFiles' => 3,
            'required' => true,
            'showPreview' => false,
            'showProgress' => true,
            'uploadUrl' => '/api/document/upload',
            'buttonText' => '选择文档',
            'dropText' => '拖放文档到此处',
            'customClass' => 'document-upload',
        ];

        $this->component->mount($props);

        self::assertSame('上传文档', $this->component->label);
        self::assertSame('document', $this->component->name);
        self::assertTrue($this->component->multiple);
        self::assertSame('pdf', $this->component->accept);
        self::assertSame(20971520, $this->component->maxSize);
        self::assertSame(3, $this->component->maxFiles);
        self::assertTrue($this->component->required);
        self::assertFalse($this->component->showPreview);
        self::assertTrue($this->component->showProgress);
        self::assertSame('/api/document/upload', $this->component->uploadUrl);
        self::assertSame('选择文档', $this->component->buttonText);
        self::assertSame('拖放文档到此处', $this->component->dropText);
        self::assertSame('document-upload', $this->component->customClass);
    }

    public function testGetMaxSizeInMB(): void
    {
        self::assertSame(10.0, $this->component->getMaxSizeInMB());

        $this->component->maxSize = 5242880;
        self::assertSame(5.0, $this->component->getMaxSizeInMB());

        $this->component->maxSize = 1572864;
        self::assertSame(1.5, $this->component->getMaxSizeInMB());
    }

    public function testGetAcceptTypes(): void
    {
        self::assertSame('*', $this->component->getAcceptTypes());

        $this->component->accept = 'image';
        self::assertSame('image/*', $this->component->getAcceptTypes());

        $this->component->accept = 'pdf';
        self::assertSame('.pdf', $this->component->getAcceptTypes());

        $this->component->accept = 'doc';
        self::assertSame('.doc,.docx', $this->component->getAcceptTypes());
    }

    public function testGetUploadId(): void
    {
        $id = $this->component->getUploadId();
        self::assertStringStartsWith('upload_', $id);
        self::assertGreaterThan(7, strlen($id));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = new FileUploadComponent();
    }
}
