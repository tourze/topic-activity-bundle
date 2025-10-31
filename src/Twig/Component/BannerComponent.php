<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(name: 'topic_activity:banner', template: '@TopicActivity/components/banner.html.twig')]
final class BannerComponent
{
    /**
     * @var array<array{src: string, alt?: string, link?: string}>
     */
    #[ExposeInTemplate]
    public array $images = [];

    #[ExposeInTemplate]
    public bool $autoplay = true;

    #[ExposeInTemplate]
    public int $interval = 3000;

    #[ExposeInTemplate]
    public bool $showIndicators = true;

    #[ExposeInTemplate]
    public bool $showArrows = true;

    #[ExposeInTemplate]
    public string $height = '400px';

    #[ExposeInTemplate]
    public string $objectFit = 'cover';

    #[ExposeInTemplate]
    public string $borderRadius = '0';

    #[ExposeInTemplate]
    public string $className = '';

    public function getContainerStyle(): string
    {
        $styles = [];

        if ('' !== $this->height) {
            $styles[] = sprintf('height: %s', $this->height);
        }

        if ('' !== $this->borderRadius && '0' !== $this->borderRadius) {
            $styles[] = sprintf('border-radius: %s', $this->borderRadius);
            $styles[] = 'overflow: hidden';
        }

        return implode('; ', $styles);
    }

    public function getImageStyle(): string
    {
        $styles = [
            'width: 100%',
            sprintf('height: %s', $this->height),
            sprintf('object-fit: %s', $this->objectFit),
        ];

        return implode('; ', $styles);
    }

    public function hasImages(): bool
    {
        return [] !== $this->images;
    }

    public function getImagesCount(): int
    {
        return count($this->images);
    }

    public function getCarouselId(): string
    {
        return 'carousel-' . uniqid();
    }
}
