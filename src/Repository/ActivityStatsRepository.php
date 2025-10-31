<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityStats;

/**
 * @extends ServiceEntityRepository<ActivityStats>
 */
#[AsRepository(entityClass: ActivityStats::class)]
class ActivityStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityStats::class);
    }

    public function remove(ActivityStats $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找或创建今日统计数据
     */
    public function findOrCreateForToday(Activity $activity): ActivityStats
    {
        $today = new \DateTimeImmutable('today');

        // 优先查询是否已存在当天记录
        $stats = $this->findOneBy([
            'activity' => $activity,
            'date' => $today,
        ]);

        if (null !== $stats) {
            return $stats;
        }

        // 不存在则创建，若并发或之前已有脏数据，捕获唯一约束后回读
        $stats = new ActivityStats();
        $stats->setActivity($activity);
        $stats->setDate($today);

        try {
            $this->save($stats, true);

            return $stats;
        } catch (\Throwable $e) {
            // 可能是并发或测试数据重复导致的唯一约束冲突，回读已存在记录
            $this->getEntityManager()->clear();
            $existing = $this->findOneBy([
                'activity' => $activity,
                'date' => $today,
            ]);
            if (null !== $existing) {
                return $existing;
            }

            throw $e;
        }
    }

    public function save(ActivityStats $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 获取活动的聚合统计数据
     */
    /**
     * @return array<string, float|int>
     */
    public function getAggregatedStats(Activity $activity): array
    {
        /** @var array{totalPv: string|int|null, totalUv: string|int|null, totalShares: string|int|null, totalFormSubmits: string|int|null, totalConversions: string|int|null, avgStayDuration: string|float|null, avgBounceRate: string|float|null} $result */
        $result = $this->createQueryBuilder('s')
            ->select('
                SUM(s.pv) as totalPv,
                SUM(s.uv) as totalUv,
                SUM(s.shareCount) as totalShares,
                SUM(s.formSubmitCount) as totalFormSubmits,
                SUM(s.conversionCount) as totalConversions,
                AVG(s.stayDuration) as avgStayDuration,
                AVG(s.bounceRate) as avgBounceRate
            ')
            ->andWhere('s.activity = :activity')
            ->setParameter('activity', $activity)
            ->getQuery()
            ->getSingleResult()
        ;

        return [
            'totalPv' => (int) ($result['totalPv'] ?? 0),
            'totalUv' => (int) ($result['totalUv'] ?? 0),
            'totalShares' => (int) ($result['totalShares'] ?? 0),
            'totalFormSubmits' => (int) ($result['totalFormSubmits'] ?? 0),
            'totalConversions' => (int) ($result['totalConversions'] ?? 0),
            'avgStayDuration' => (float) ($result['avgStayDuration'] ?? 0),
            'avgBounceRate' => (float) ($result['avgBounceRate'] ?? 0),
        ];
    }

    /**
     * 获取图表用的每日统计数据
     *
     * @return array<string, mixed>
     */
    public function getDailyStats(Activity $activity, int $days = 30): array
    {
        $endDate = new \DateTimeImmutable('today');
        $startDate = $endDate->modify(sprintf('-%d days', $days));
        if (false === $startDate) {
            throw new \RuntimeException('Invalid date modification');
        }

        $stats = $this->findByDateRange($activity, $startDate, $endDate);

        $dailyData = [];
        foreach ($stats as $stat) {
            $date = $stat->getDate()->format('Y-m-d');
            $dailyData[$date] = [
                'pv' => $stat->getPv(),
                'uv' => $stat->getUv(),
                'shares' => $stat->getShareCount(),
                'conversions' => $stat->getConversionCount(),
                'conversionRate' => $stat->getConversionRate(),
            ];
        }

        return $dailyData;
    }

    /**
     * 获取日期范围内的统计数据
     *
     * @return ActivityStats[]
     */
    public function findByDateRange(Activity $activity, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.activity = :activity')
            ->andWhere('s.date >= :startDate')
            ->andWhere('s.date <= :endDate')
            ->setParameter('activity', $activity)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityStats> */
        return $result;
    }

    /**
     * 获取表现最佳的活动
     *
     * @return array<array{activity: Activity, metrics: array<string, int>}>
     */
    public function getTopPerformingActivities(int $limit = 10): array
    {
        /** @var array<array{activityId: string|int, totalPv: string|int, totalUv: string|int, totalConversions: string|int}> $results */
        $results = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.activity) as activityId, SUM(s.pv) as totalPv, SUM(s.uv) as totalUv, SUM(s.conversionCount) as totalConversions')
            ->groupBy('s.activity')
            ->orderBy('totalPv', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        $topActivities = [];
        foreach ($results as $result) {
            $activity = $this->getEntityManager()->find(Activity::class, $result['activityId']);
            if (null !== $activity) {
                $topActivities[] = [
                    'activity' => $activity,
                    'metrics' => [
                        'totalPv' => (int) $result['totalPv'],
                        'totalUv' => (int) $result['totalUv'],
                        'totalConversions' => (int) $result['totalConversions'],
                    ],
                ];
            }
        }

        return $topActivities;
    }

    /**
     * 根据活动和日期查找统计数据
     */
    public function findByActivityAndDate(Activity $activity, \DateTimeImmutable $date): ?ActivityStats
    {
        // 使用简单的findOneBy查询，让Doctrine处理日期比较
        return $this->findOneBy([
            'activity' => $activity,
            'date' => new \DateTimeImmutable($date->format('Y-m-d')),
        ]);
    }

    /**
     * 根据活动和日期范围查找统计数据
     *
     * @return ActivityStats[]
     */
    public function findByActivityAndDateRange(Activity $activity, ?\DateTimeImmutable $startDate = null, ?\DateTimeImmutable $endDate = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.activity = :activity')
            ->setParameter('activity', $activity)
        ;

        if (null !== $startDate) {
            $qb->andWhere('s.date >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('s.date <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        $result = $qb
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityStats> */
        return $result;
    }

    /**
     * 根据活动查找统计数据
     *
     * @return ActivityStats[]
     */
    public function findByActivity(Activity $activity): array
    {
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.activity = :activity')
            ->setParameter('activity', $activity)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityStats> */
        return $result;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * 获取总统计数据
     */
    /**
     * @return array<string, int>
     */
    public function getTotalStats(): array
    {
        /** @var array{totalPv: string|int|null, totalUv: string|int|null, totalShares: string|int|null, totalFormSubmits: string|int|null, totalConversions: string|int|null, totalActivities: string|int|null} $result */
        $result = $this->createQueryBuilder('s')
            ->select('
                SUM(s.pv) as totalPv,
                SUM(s.uv) as totalUv,
                SUM(s.shareCount) as totalShares,
                SUM(s.formSubmitCount) as totalFormSubmits,
                SUM(s.conversionCount) as totalConversions,
                COUNT(DISTINCT s.activity) as totalActivities
            ')
            ->getQuery()
            ->getSingleResult()
        ;

        return [
            'totalPv' => (int) ($result['totalPv'] ?? 0),
            'totalUv' => (int) ($result['totalUv'] ?? 0),
            'totalShares' => (int) ($result['totalShares'] ?? 0),
            'totalFormSubmits' => (int) ($result['totalFormSubmits'] ?? 0),
            'totalConversions' => (int) ($result['totalConversions'] ?? 0),
            'totalActivities' => (int) ($result['totalActivities'] ?? 0),
        ];
    }
}
