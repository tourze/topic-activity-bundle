<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Tourze\TopicActivityBundle\Event\ActivityLifecycleEvent;

#[Autoconfigure(public: true)]
#[AsEventListener(event: ActivityLifecycleEvent::AFTER_PUBLISH, method: 'onActivityPublished')]
#[AsEventListener(event: ActivityLifecycleEvent::AFTER_ARCHIVE, method: 'onActivityArchived')]
#[AsEventListener(event: ActivityLifecycleEvent::AFTER_DELETE, method: 'onActivityDeleted')]
class ActivityEventListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onActivityPublished(ActivityLifecycleEvent $event): void
    {
        $activity = $event->getActivity();

        $this->logger->info('Activity published event triggered', [
            'activity_id' => $activity->getId(),
            'title' => $activity->getTitle(),
            'scheduled' => null !== $event->getContextValue('scheduledTime'),
        ]);

        // Here you could:
        // - Send notifications to subscribers
        // - Clear caches
        // - Trigger CDN invalidation
        // - Send analytics events
    }

    public function onActivityArchived(ActivityLifecycleEvent $event): void
    {
        $activity = $event->getActivity();

        $this->logger->info('Activity archived event triggered', [
            'activity_id' => $activity->getId(),
            'title' => $activity->getTitle(),
        ]);

        // Here you could:
        // - Archive related data
        // - Generate final reports
        // - Clean up temporary files
    }

    public function onActivityDeleted(ActivityLifecycleEvent $event): void
    {
        $activity = $event->getActivity();
        $isHardDelete = $event->getContextValue('hard', false);

        $this->logger->info('Activity deleted event triggered', [
            'activity_id' => $activity->getId(),
            'title' => $activity->getTitle(),
            'hard_delete' => $isHardDelete,
        ]);

        // Here you could:
        // - Clean up related files
        // - Remove from search index
        // - Clear related caches
    }
}
