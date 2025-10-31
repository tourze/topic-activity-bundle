<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: ActivityComponent::class)]
class ActivityComponentUpdateListener
{
    public function preUpdate(ActivityComponent $component, PreUpdateEventArgs $args): void
    {
        $component->setUpdateTime(new \DateTimeImmutable());
    }
}
