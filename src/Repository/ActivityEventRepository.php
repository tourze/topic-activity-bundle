<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;
use Tourze\TopicActivityBundle\Exception\ActivityStateException;

/**
 * @extends ServiceEntityRepository<ActivityEvent>
 */
#[AsRepository(entityClass: ActivityEvent::class)]
class ActivityEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityEvent::class);
    }

    /**
     * @param array<string, mixed> $eventData
     */
    public function createEvent(Activity $activity, string $eventType, array $eventData = []): ActivityEvent
    {
        $activityId = $activity->getId();
        if (null === $activityId) {
            throw new ActivityStateException('Activity ID cannot be null');
        }
        $event = ActivityEvent::create($activityId);
        $event->setEventType($eventType);
        $event->setEventData($eventData);
        $sessionId = session_id();
        if (false === $sessionId) {
            $sessionId = uniqid('session_');
        }
        $event->setSessionId('' !== $sessionId ? $sessionId : uniqid('session_'));

        return $event;
    }

    public function save(ActivityEvent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityEvent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 按活动查找事件
     *
     * @return ActivityEvent[]
     */
    public function findByActivityId(int $activityId, int $limit = 100): array
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->orderBy('e.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityEvent> */
        return $result;
    }

    /**
     * 按会话查找事件
     *
     * @return ActivityEvent[]
     */
    public function findBySessionId(string $sessionId): array
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('e.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityEvent> */
        return $result;
    }

    /**
     * 统计活动的独立访客数量
     */
    public function countUniqueVisitors(int $activityId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.sessionId)')
            ->andWhere('e.activityId = :activityId')
            ->andWhere('e.createTime >= :startDate')
            ->andWhere('e.createTime <= :endDate')
            ->setParameter('activityId', $activityId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 获取事件类型分布
     *
     * @return array<string, int>
     */
    public function getEventTypeDistribution(int $activityId): array
    {
        /** @var array<array{eventType: string, cnt: string|int}> $results */
        $results = $this->createQueryBuilder('e')
            ->select('e.eventType, COUNT(e.id) as cnt')
            ->andWhere('e.activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->groupBy('e.eventType')
            ->getQuery()
            ->getResult()
        ;

        $distribution = [];
        foreach ($results as $result) {
            $distribution[$result['eventType']] = (int) $result['cnt'];
        }

        return $distribution;
    }

    /**
     * 获取转化漏斗数据
     */
    /**
     * @param array<string> $eventTypes
     * @return array<string, int>
     */
    public function getConversionFunnel(int $activityId, array $eventTypes): array
    {
        $funnel = [];

        foreach ($eventTypes as $eventType) {
            $count = (int) $this->createQueryBuilder('e')
                ->select('COUNT(DISTINCT e.sessionId)')
                ->andWhere('e.activityId = :activityId')
                ->andWhere('e.eventType = :eventType')
                ->setParameter('activityId', $activityId)
                ->setParameter('eventType', $eventType)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            $funnel[$eventType] = $count;
        }

        return $funnel;
    }

    /**
     * 获取会话的用户旅程
     *
     * @return ActivityEvent[]
     */
    public function getUserJourney(string $sessionId, int $activityId): array
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.sessionId = :sessionId')
            ->andWhere('e.activityId = :activityId')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('activityId', $activityId)
            ->orderBy('e.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityEvent> */
        return $result;
    }

    /**
     * 清理旧事件数据
     */
    public function cleanOldEvents(int $daysToKeep = 90): int
    {
        $cutoffDate = (new \DateTimeImmutable())->modify(sprintf('-%d days', $daysToKeep));

        $result = $this->createQueryBuilder('e')
            ->delete()
            ->where('e.createTime < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result));

        return $result;
    }

    /**
     * Get real-time active users
     */
    public function getActiveUsers(int $minutes = 5): int
    {
        $since = (new \DateTimeImmutable())->modify(sprintf('-%d minutes', $minutes));

        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.sessionId)')
            ->andWhere('e.createTime >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 查找访客事件
     */
    public function findVisitorEvent(int $activityId, string $sessionId, string $eventType): ?ActivityEvent
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.activityId = :activityId')
            ->andWhere('e.sessionId = :sessionId')
            ->andWhere('e.eventType = :eventType')
            ->setParameter('activityId', $activityId)
            ->setParameter('sessionId', $sessionId)
            ->setParameter('eventType', $eventType)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof ActivityEvent || null === $result);

        return $result;
    }

    /**
     * 查找今日事件
     *
     * @return ActivityEvent[]
     */
    public function findTodayEvents(Activity $activity): array
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        $result = $this->createQueryBuilder('e')
            ->andWhere('e.activityId = :activityId')
            ->andWhere('e.createTime >= :today')
            ->andWhere('e.createTime < :tomorrow')
            ->setParameter('activityId', $activity->getId())
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('e.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityEvent> */
        return $result;
    }

    /**
     * 根据活动查找事件
     *
     * @return ActivityEvent[]
     */
    public function findByActivity(Activity $activity): array
    {
        $activityId = $activity->getId();
        if (null === $activityId) {
            return [];
        }

        return $this->findByActivityId($activityId);
    }

    /**
     * 根据活动和会话查找事件
     *
     * @return ActivityEvent[]
     */
    public function findByActivityAndSession(Activity $activity, string $sessionId): array
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.activityId = :activityId')
            ->andWhere('e.sessionId = :sessionId')
            ->setParameter('activityId', $activity->getId())
            ->setParameter('sessionId', $sessionId)
            ->orderBy('e.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityEvent> */
        return $result;
    }

    /**
     * 根据活动和日期范围查找事件
     *
     * @return ActivityEvent[]
     */
    public function findByDateRange(Activity $activity, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.activityId = :activityId')
            ->andWhere('e.createTime >= :startDate')
            ->andWhere('e.createTime <= :endDate')
            ->setParameter('activityId', $activity->getId())
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityEvent> */
        return $result;
    }

    /**
     * 根据活动和事件类型查找事件
     *
     * @return ActivityEvent[]
     */
    public function findByEventType(Activity $activity, string $eventType): array
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.activityId = :activityId')
            ->andWhere('e.eventType = :eventType')
            ->setParameter('activityId', $activity->getId())
            ->setParameter('eventType', $eventType)
            ->orderBy('e.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityEvent> */
        return $result;
    }

    /**
     * 清理旧事件数据（别名方法）
     */
    public function cleanupOldEvents(int $daysToKeep = 90): int
    {
        return $this->cleanOldEvents($daysToKeep);
    }

    /**
     * 查找活跃会话
     *
     * @return string[]
     */
    public function findActiveSessions(Activity $activity, int $minutes = 30): array
    {
        $since = (new \DateTimeImmutable())->modify(sprintf('-%d minutes', $minutes));

        /** @var array<array{sessionId: string}> $result */
        $result = $this->createQueryBuilder('e')
            ->select('DISTINCT e.sessionId')
            ->andWhere('e.activityId = :activityId')
            ->andWhere('e.createTime >= :since')
            ->setParameter('activityId', $activity->getId())
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult()
        ;

        return array_column($result, 'sessionId');
    }

    /**
     * 统计今日事件数量
     */
    public function countTodayEvents(?Activity $activity = null): int
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        $queryBuilder = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.createTime >= :today')
            ->andWhere('e.createTime < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
        ;

        if (null !== $activity) {
            $queryBuilder
                ->andWhere('e.activityId = :activityId')
                ->setParameter('activityId', $activity->getId())
            ;
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
