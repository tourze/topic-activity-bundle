<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;

/**
 * @extends ServiceEntityRepository<ActivityComponent>
 */
#[AsRepository(entityClass: ActivityComponent::class)]
class ActivityComponentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityComponent::class);
    }

    public function save(ActivityComponent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityComponent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据活动查找组件
     *
     * @return ActivityComponent[]
     * @phpstan-return list<ActivityComponent>
     */
    public function findByActivity(Activity $activity): array
    {
        $result = $this->createQueryBuilder('c')
            ->andWhere('c.activity = :activity')
            ->setParameter('activity', $activity)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityComponent> */
        return $result;
    }

    /**
     * 查找活动的可见组件
     *
     * @return ActivityComponent[]
     * @phpstan-return list<ActivityComponent>
     */
    public function findVisibleByActivity(Activity $activity): array
    {
        $result = $this->createQueryBuilder('c')
            ->andWhere('c.activity = :activity')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('activity', $activity)
            ->setParameter('visible', true)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityComponent> */
        return $result;
    }

    /**
     * 按类型查找组件
     *
     * @return ActivityComponent[]
     * @phpstan-return list<ActivityComponent>
     */
    public function findByType(string $type): array
    {
        $result = $this->createQueryBuilder('c')
            ->andWhere('c.componentType = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityComponent> */
        return $result;
    }

    /**
     * 获取活动的最大位置
     */
    public function getMaxPosition(Activity $activity): int
    {
        $result = $this->createQueryBuilder('c')
            ->select('MAX(c.position) as maxPos')
            ->andWhere('c.activity = :activity')
            ->setParameter('activity', $activity)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return null !== $result ? (int) $result : 0;
    }

    /**
     * 重新排序活动的组件
     */
    /**
     * @param array<int> $componentIds
     */
    public function reorderComponents(Activity $activity, array $componentIds): void
    {
        $position = 0;
        foreach ($componentIds as $componentId) {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.position', ':position')
                ->where('c.id = :id')
                ->andWhere('c.activity = :activity')
                ->setParameter('position', $position++)
                ->setParameter('id', $componentId)
                ->setParameter('activity', $activity)
                ->getQuery()
                ->execute()
            ;
        }
    }

    /**
     * 按类型统计组件数量
     *
     * @return array<string, int>
     */
    public function countByType(): array
    {
        /** @var array<array{componentType: string, cnt: string|int}> $results */
        $results = $this->createQueryBuilder('c')
            ->select('c.componentType, COUNT(c.id) as cnt')
            ->groupBy('c.componentType')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['componentType']] = (int) $result['cnt'];
        }

        return $counts;
    }

    /**
     * 根据活动和类型查找组件
     *
     * @return ActivityComponent[]
     * @phpstan-return list<ActivityComponent>
     */
    public function findByActivityAndType(Activity $activity, string $type): array
    {
        $result = $this->createQueryBuilder('c')
            ->andWhere('c.activity = :activity')
            ->andWhere('c.componentType = :type')
            ->setParameter('activity', $activity)
            ->setParameter('type', $type)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityComponent> */
        return $result;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
