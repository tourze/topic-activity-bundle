<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;

/**
 * @extends ServiceEntityRepository<Activity>
 */
#[AsRepository(entityClass: Activity::class)]
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function save(Activity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Activity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找已发布的活动
     *
     * @return Activity[]
     */
    public function findPublished(): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.deleteTime IS NULL')
            ->andWhere('(a.startTime IS NULL OR a.startTime <= :now)')
            ->andWhere('(a.endTime IS NULL OR a.endTime >= :now)')
            ->setParameter('status', ActivityStatus::PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<Activity> */
        return $result;
    }

    /**
     * 按状态查找活动
     *
     * @return Activity[]
     */
    public function findByStatus(ActivityStatus $status): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.deleteTime IS NULL')
            ->setParameter('status', $status)
            ->orderBy('a.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<Activity> */
        return $result;
    }

    /**
     * 根据 slug 查找活动
     */
    public function findBySlug(string $slug): ?Activity
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.deleteTime IS NULL')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof Activity || null === $result);

        return $result;
    }

    /**
     * 查找需要发布的活动
     *
     * @return Activity[]
     */
    public function findScheduledForPublishing(): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.startTime <= :now')
            ->andWhere('a.deleteTime IS NULL')
            ->setParameter('status', ActivityStatus::SCHEDULED)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<Activity> */
        return $result;
    }

    /**
     * 查找需要归档的活动
     *
     * @return Activity[]
     */
    public function findExpiredActivities(): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.endTime < :now')
            ->andWhere('a.deleteTime IS NULL')
            ->setParameter('status', ActivityStatus::PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<Activity> */
        return $result;
    }

    /**
     * 查找日期范围内的活动
     *
     * @return Activity[]
     */
    public function findInDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.createTime >= :startDate')
            ->andWhere('a.createTime <= :endDate')
            ->andWhere('a.deleteTime IS NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<Activity> */
        return $result;
    }

    /**
     * 按状态统计活动数量
     */
    public function countByStatus(ActivityStatus $status): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.status = :status')
            ->andWhere('a.deleteTime IS NULL')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 查找包含组件的活动
     *
     * @return Activity[]
     */
    public function findWithComponents(): array
    {
        $result = $this->createQueryBuilder('a')
            ->leftJoin('a.components', 'c')
            ->addSelect('c')
            ->andWhere('a.deleteTime IS NULL')
            ->orderBy('a.createTime', 'DESC')
            ->addOrderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<Activity> */
        return $result;
    }

    /**
     * 软删除活动
     */
    public function softDelete(Activity $activity, bool $flush = false): void
    {
        $activity->setDeleteTime(new \DateTimeImmutable());

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 恢复软删除的活动
     */
    public function restore(Activity $activity, bool $flush = false): void
    {
        $activity->setDeleteTime(null);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找活跃的活动
     *
     * @return Activity[]
     */
    public function findActiveActivities(): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.deleteTime IS NULL')
            ->andWhere('(a.startTime IS NULL OR a.startTime <= :now)')
            ->andWhere('(a.endTime IS NULL OR a.endTime >= :now)')
            ->setParameter('status', ActivityStatus::PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<Activity> */
        return $result;
    }

    /**
     * 查找日期范围内的活动（别名方法）
     *
     * @return Activity[]
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->findInDateRange($startDate, $endDate);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
