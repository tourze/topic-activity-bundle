<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\TopicActivityBundle\Entity\Activity;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Activity::class)]
class ActivityUpdateListener
{
    public function preUpdate(Activity $activity, PreUpdateEventArgs $args): void
    {
        $activity->setUpdateTime(new \DateTimeImmutable());
    }
}
