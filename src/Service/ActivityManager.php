<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;
use Tourze\TopicActivityBundle\Event\ActivityLifecycleEvent;
use Tourze\TopicActivityBundle\Exception\ActivityStateException;
use Tourze\TopicActivityBundle\Exception\ActivityStatusTransitionException;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;

#[WithMonologChannel(channel: 'topic_activity')]
class ActivityManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityRepository $activityRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 创建新活动
     */
    /**
     * @param array<string, mixed> $data
     */
    public function createActivity(array $data): Activity
    {
        $activity = new Activity();
        $this->updateActivityFromData($activity, $data);

        $event = new ActivityLifecycleEvent($activity, $data);
        $this->eventDispatcher->dispatch($event);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Activity created', [
            'id' => $activity->getId(),
            'title' => $activity->getTitle(),
        ]);

        return $activity;
    }

    /**
     * 根据数据数组更新活动
     */
    /**
     * @param array<string, mixed> $data
     */
    private function updateActivityFromData(Activity $activity, array $data): void
    {
        $this->updateBasicFields($activity, $data);
        $this->updateConfigFields($activity, $data);
        $this->updateTimeFields($activity, $data);
        $this->updateMetaFields($activity, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateBasicFields(Activity $activity, array $data): void
    {
        $this->updateTitleField($activity, $data);
        $this->updateSlugField($activity, $data);
        $this->updateOptionalStringField($activity, $data, 'description', $activity->setDescription(...));
        $this->updateOptionalStringField($activity, $data, 'coverImage', $activity->setCoverImage(...));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateTitleField(Activity $activity, array $data): void
    {
        if (isset($data['title']) && is_string($data['title'])) {
            $activity->setTitle($data['title']);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param callable(string|null): void $setter
     */
    private function updateOptionalStringField(Activity $activity, array $data, string $key, callable $setter): void
    {
        if (!isset($data[$key])) {
            return;
        }

        $value = $data[$key];
        $setter(is_string($value) ? $value : null);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateSlugField(Activity $activity, array $data): void
    {
        if (isset($data['slug'])) {
            $slug = $data['slug'];
            $activity->setSlug(is_string($slug) ? $slug : null);

            return;
        }

        if ($this->shouldGenerateSlug($activity, $data)) {
            if (!isset($data['title'])) {
                throw new \InvalidArgumentException('Title is required for slug generation');
            }
            /** @var string $title */
            $title = $data['title'];
            $activity->setSlug($this->generateSlug($title));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function shouldGenerateSlug(Activity $activity, array $data): bool
    {
        if (!isset($data['title']) || !is_string($data['title'])) {
            return false;
        }

        $currentSlug = $activity->getSlug();

        return null === $currentSlug || '' === $currentSlug;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateConfigFields(Activity $activity, array $data): void
    {
        $this->updateConfigField($data, 'layoutConfig', $activity->setLayoutConfig(...));
        $this->updateConfigField($data, 'seoConfig', $activity->setSeoConfig(...));
        $this->updateConfigField($data, 'shareConfig', $activity->setShareConfig(...));
        $this->updateConfigField($data, 'accessConfig', $activity->setAccessConfig(...));
    }

    /**
     * @param array<string, mixed> $data
     * @param callable(array<string, mixed>): void $setter
     */
    private function updateConfigField(array $data, string $key, callable $setter): void
    {
        if (!isset($data[$key])) {
            return;
        }

        $value = $data[$key];
        if (is_array($value)) {
            /** @var array<string, mixed> $value */
            $setter($value);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateTimeFields(Activity $activity, array $data): void
    {
        if (isset($data['startTime'])) {
            $startTime = $data['startTime'];
            $activity->setStartTime($startTime instanceof \DateTimeImmutable ? $startTime : null);
        }

        if (isset($data['endTime'])) {
            $endTime = $data['endTime'];
            $activity->setEndTime($endTime instanceof \DateTimeImmutable ? $endTime : null);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateMetaFields(Activity $activity, array $data): void
    {
        $this->updateTemplateId($activity, $data);
        $this->updateUserField($activity, $data, 'createdBy', $activity->setCreatedBy(...));
        $this->updateUserField($activity, $data, 'updatedBy', $activity->setUpdatedBy(...));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateTemplateId(Activity $activity, array $data): void
    {
        if (isset($data['templateId'])) {
            $templateId = $data['templateId'];
            $activity->setTemplateId(is_int($templateId) ? $templateId : null);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param callable(string): void $setter
     */
    private function updateUserField(Activity $activity, array $data, string $key, callable $setter): void
    {
        if (!isset($data[$key])) {
            return;
        }

        $value = $data[$key];
        if (is_string($value) || is_numeric($value)) {
            $setter((string) $value);
        }
    }

    /**
     * 生成唯一的 slug
     */
    private function generateSlug(string $title): string
    {
        $baseSlug = $this->slugger->slug($title)->lower()->toString();

        return $this->ensureUniqueSlug($baseSlug);
    }

    private function ensureUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (null !== $this->activityRepository->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            ++$counter;
        }

        return $slug;
    }

    /**
     * 更新现有活动
     */
    /**
     * @param array<string, mixed> $data
     */
    public function updateActivity(Activity $activity, array $data): Activity
    {
        $event = new ActivityLifecycleEvent($activity, $data);
        $this->eventDispatcher->dispatch($event);

        $this->updateActivityFromData($activity, $data);

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Activity updated', [
            'id' => $activity->getId(),
            'title' => $activity->getTitle(),
        ]);

        return $activity;
    }

    /**
     * Delete an activity (soft delete)
     */
    public function deleteActivity(Activity $activity, bool $hard = false): void
    {
        $event = new ActivityLifecycleEvent($activity, ['hard' => $hard]);
        $this->eventDispatcher->dispatch($event);

        if ($hard) {
            $this->entityManager->remove($activity);
        } else {
            $this->activityRepository->softDelete($activity);
        }

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Activity deleted', [
            'id' => $activity->getId(),
            'title' => $activity->getTitle(),
            'hard' => $hard,
        ]);
    }

    /**
     * Restore a soft-deleted activity
     */
    public function restoreActivity(Activity $activity): Activity
    {
        if (!$activity->isDeleted()) {
            throw ActivityStateException::notDeleted();
        }

        $event = new ActivityLifecycleEvent($activity);
        $this->eventDispatcher->dispatch($event);

        $this->activityRepository->restore($activity);
        $activity->setStatus(ActivityStatus::DRAFT);

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Activity restored', [
            'id' => $activity->getId(),
            'title' => $activity->getTitle(),
        ]);

        return $activity;
    }

    /**
     * 复制活动
     */
    public function duplicateActivity(Activity $source, ?string $newTitle = null): Activity
    {
        $activity = $this->createDuplicateActivity($source, $newTitle);
        $this->copyComponents($source, $activity);

        $event = new ActivityLifecycleEvent($activity, ['source' => $source]);
        $this->eventDispatcher->dispatch($event);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Activity duplicated', [
            'source_id' => $source->getId(),
            'new_id' => $activity->getId(),
            'new_title' => $activity->getTitle(),
        ]);

        return $activity;
    }

    private function createDuplicateActivity(Activity $source, ?string $newTitle): Activity
    {
        $activity = new Activity();

        $activity->setTitle($newTitle ?? $source->getTitle() . ' (Copy)');
        $activity->setSlug($this->generateSlug($activity->getTitle()));
        $activity->setDescription($source->getDescription());
        $activity->setCoverImage($source->getCoverImage());
        $activity->setLayoutConfig($source->getLayoutConfig());
        $activity->setSeoConfig($source->getSeoConfig());
        $activity->setShareConfig($source->getShareConfig());
        $activity->setAccessConfig($source->getAccessConfig());
        $activity->setTemplateId($source->getTemplateId());

        return $activity;
    }

    private function copyComponents(Activity $source, Activity $target): void
    {
        foreach ($source->getComponents() as $sourceComponent) {
            $component = new ActivityComponent();
            $component->setActivity($target);
            $component->setComponentType($sourceComponent->getComponentType());
            $component->setComponentConfig($sourceComponent->getComponentConfig());
            $component->setPosition($sourceComponent->getPosition());
            $component->setIsVisible($sourceComponent->isVisible());

            $target->addComponent($component);
        }
    }

    /**
     * 处理计划中的活动
     */
    public function processScheduledActivities(): int
    {
        $activities = $this->activityRepository->findScheduledForPublishing();

        return $this->processBatch($activities, fn ($activity) => $this->publishActivity($activity), 'publish');
    }

    /**
     * 发布活动
     */
    public function publishActivity(Activity $activity, ?\DateTimeImmutable $scheduledTime = null): Activity
    {
        $this->validatePublishTransition($activity);

        $event = new ActivityLifecycleEvent($activity, ['scheduledTime' => $scheduledTime]);
        $this->eventDispatcher->dispatch($event);

        $this->setPublishStatus($activity, $scheduledTime);

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Activity published', [
            'id' => $activity->getId(),
            'title' => $activity->getTitle(),
            'scheduled' => null !== $scheduledTime,
        ]);

        return $activity;
    }

    private function validatePublishTransition(Activity $activity): void
    {
        if (!$activity->getStatus()->canTransitionTo(ActivityStatus::PUBLISHED)) {
            throw ActivityStatusTransitionException::cannotTransition($activity->getStatus()->value, ActivityStatus::PUBLISHED->value);
        }
    }

    private function setPublishStatus(Activity $activity, ?\DateTimeImmutable $scheduledTime): void
    {
        if (null !== $scheduledTime && $scheduledTime > new \DateTimeImmutable()) {
            $activity->setStatus(ActivityStatus::SCHEDULED);
            $activity->setStartTime($scheduledTime);
        } else {
            $activity->setStatus(ActivityStatus::PUBLISHED);
        }
    }

    /**
     * 处理过期的活动
     */
    public function processExpiredActivities(): int
    {
        $activities = $this->activityRepository->findExpiredActivities();

        return $this->processBatch($activities, fn ($activity) => $this->archiveActivity($activity), 'archive');
    }

    /**
     * @param Activity[] $activities
     * @param callable(Activity): Activity $processor
     */
    private function processBatch(array $activities, callable $processor, string $action): int
    {
        $count = 0;

        foreach ($activities as $activity) {
            try {
                $processor($activity);
                ++$count;
            } catch (\Exception $e) {
                $this->logger->error("Failed to {$action} activity", [
                    'id' => $activity->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            $this->logger->info("Processed {$action} activities", ['count' => $count]);
        }

        return $count;
    }

    /**
     * 归档活动
     */
    public function archiveActivity(Activity $activity): Activity
    {
        $this->validateArchiveTransition($activity);

        $event = new ActivityLifecycleEvent($activity);
        $this->eventDispatcher->dispatch($event);

        $activity->setStatus(ActivityStatus::ARCHIVED);

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Activity archived', [
            'id' => $activity->getId(),
            'title' => $activity->getTitle(),
        ]);

        return $activity;
    }

    private function validateArchiveTransition(Activity $activity): void
    {
        if (!$activity->getStatus()->canTransitionTo(ActivityStatus::ARCHIVED)) {
            throw ActivityStatusTransitionException::cannotTransition($activity->getStatus()->value, ActivityStatus::ARCHIVED->value);
        }
    }

    /**
     * 更新活动组件
     */
    /**
     * @param array<int, array<string, mixed>> $componentsData
     */
    public function updateActivityComponents(Activity $activity, array $componentsData): void
    {
        $this->removeExistingComponents($activity);
        $this->addNewComponents($activity, $componentsData);

        $this->entityManager->flush();

        $this->logger->info('Activity components updated', [
            'activity_id' => $activity->getId(),
            'components_count' => count($componentsData),
        ]);
    }

    private function removeExistingComponents(Activity $activity): void
    {
        foreach ($activity->getComponents() as $component) {
            $activity->removeComponent($component);
            $this->entityManager->remove($component);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $componentsData
     */
    private function addNewComponents(Activity $activity, array $componentsData): void
    {
        foreach ($componentsData as $index => $componentData) {
            if (!is_array($componentData)) {
                continue;
            }

            $component = $this->createComponentFromData($activity, $componentData, $index);
            $activity->addComponent($component);
            $this->entityManager->persist($component);
        }
    }

    /**
     * @param array<string, mixed> $componentData
     */
    private function createComponentFromData(Activity $activity, array $componentData, int $index): ActivityComponent
    {
        $component = new ActivityComponent();
        $component->setActivity($activity);

        $type = $componentData['type'] ?? '';
        $component->setComponentType(is_string($type) ? $type : '');

        $validConfig = $this->extractValidConfig($componentData['config'] ?? []);
        $component->setComponentConfig($validConfig);

        $position = $componentData['position'] ?? $index;
        $component->setPosition(is_int($position) ? $position : $index);

        $visible = $componentData['visible'] ?? true;
        $component->setIsVisible(is_bool($visible) ? $visible : true);

        return $component;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractValidConfig(mixed $config): array
    {
        if (!is_array($config)) {
            return [];
        }

        $validConfig = [];
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $validConfig[$key] = $value;
            }
        }

        return $validConfig;
    }
}
