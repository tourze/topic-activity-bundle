<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: ActivityTemplate::class)]
class ActivityTemplateUpdateListener
{
    public function preUpdate(ActivityTemplate $template, PreUpdateEventArgs $args): void
    {
        $template->setUpdateTime(new \DateTimeImmutable());
    }
}
