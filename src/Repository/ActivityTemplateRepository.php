<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;

/**
 * @extends ServiceEntityRepository<ActivityTemplate>
 */
#[AsRepository(entityClass: ActivityTemplate::class)]
class ActivityTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityTemplate::class);
    }

    public function save(ActivityTemplate $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityTemplate $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ActivityTemplate[]
     * @phpstan-return list<ActivityTemplate>
     */
    public function findActiveTemplates(): array
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.usageCount', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityTemplate> */
        return $result;
    }

    /**
     * @return ActivityTemplate[]
     * @phpstan-return list<ActivityTemplate>
     */
    public function findByCategory(string $category): array
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.category = :category')
            ->andWhere('t.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('t.usageCount', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityTemplate> */
        return $result;
    }

    /**
     * @return ActivityTemplate[]
     * @phpstan-return list<ActivityTemplate>
     */
    public function findSystemTemplates(): array
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.isSystem = :system')
            ->andWhere('t.isActive = :active')
            ->setParameter('system', true)
            ->setParameter('active', true)
            ->orderBy('t.category', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityTemplate> */
        return $result;
    }

    public function findByCode(string $code): ?ActivityTemplate
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof ActivityTemplate || null === $result);

        return $result;
    }

    /**
     * @return ActivityTemplate[]
     * @phpstan-return list<ActivityTemplate>
     */
    public function findPopularTemplates(int $limit = 10): array
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityTemplate> */
        return $result;
    }

    /**
     * @return array<string, int>
     */
    public function getCategoryStatistics(): array
    {
        /** @var array<array{category: string, count: string|int}> $result */
        $result = $this->createQueryBuilder('t')
            ->select('t.category, COUNT(t.id) as count')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('t.category')
            ->getQuery()
            ->getResult()
        ;

        $stats = [];
        foreach ($result as $row) {
            $stats[$row['category']] = (int) $row['count'];
        }

        return $stats;
    }

    public function findBySlug(string $slug): ?ActivityTemplate
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof ActivityTemplate || null === $result);

        return $result;
    }

    /**
     * @return ActivityTemplate[]
     * @phpstan-return list<ActivityTemplate>
     */
    public function findByNamePattern(string $pattern): array
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :pattern')
            ->andWhere('t.isActive = :active')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<ActivityTemplate> */
        return $result;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
