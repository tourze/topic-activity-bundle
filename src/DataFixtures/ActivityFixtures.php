<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;

#[When(env: 'test')]
#[When(env: 'dev')]
class ActivityFixtures extends Fixture
{
    public const ACTIVITY_REFERENCE_PREFIX = 'activity-';

    public function load(ObjectManager $manager): void
    {
        $activities = [
            [
                'title' => '双十一购物狂欢节',
                'slug' => 'double-eleven-2024',
                'description' => '年度最大促销活动，全场商品超低折扣',
                'status' => ActivityStatus::PUBLISHED,
                'startTime' => new \DateTimeImmutable('2024-11-01'),
                'endTime' => new \DateTimeImmutable('2024-11-11 23:59:59'),
            ],
            [
                'title' => '新年特惠活动',
                'slug' => 'new-year-2025',
                'description' => '新年新气象，精选好礼送不停',
                'status' => ActivityStatus::SCHEDULED,
                'startTime' => new \DateTimeImmutable('2025-01-01'),
                'endTime' => new \DateTimeImmutable('2025-01-31 23:59:59'),
            ],
            [
                'title' => '会员专享日',
                'slug' => 'vip-exclusive-day',
                'description' => '会员专属优惠，积分双倍返还',
                'status' => ActivityStatus::DRAFT,
                'startTime' => null,
                'endTime' => null,
            ],
            [
                'title' => '春季清仓大促',
                'slug' => 'spring-clearance',
                'description' => '换季清仓，低至3折起',
                'status' => ActivityStatus::ARCHIVED,
                'startTime' => new \DateTimeImmutable('2024-03-01'),
                'endTime' => new \DateTimeImmutable('2024-03-31'),
                'archiveTime' => new \DateTimeImmutable('2024-04-01'),
            ],
        ];

        foreach ($activities as $index => $data) {
            $activity = new Activity();
            $activity->setTitle($data['title']);
            // 为slug添加唯一标识符，确保在测试环境中不会冲突
            $uniqueSlug = $data['slug'] . '-' . uniqid();
            $activity->setSlug($uniqueSlug);
            $activity->setDescription($data['description']);

            // 设置状态，处理特殊的状态转换
            if (ActivityStatus::ARCHIVED === $data['status']) {
                // 先设置为 published，然后再 archived
                $activity->setStatus(ActivityStatus::PUBLISHED);
                $activity->setStatus(ActivityStatus::ARCHIVED);
            } elseif (ActivityStatus::DRAFT !== $data['status']) {
                $activity->setStatus($data['status']);
            }
            $activity->setLayoutConfig([
                'theme' => 'default',
                'components' => [],
            ]);

            if (null !== $data['startTime']) {
                $activity->setStartTime($data['startTime']);
            }

            if (null !== $data['endTime']) {
                $activity->setEndTime($data['endTime']);
            }

            // archiveTime 会在状态变更时自动设置

            // publishTime 会在状态变更时自动设置

            $manager->persist($activity);
            $this->addReference(self::ACTIVITY_REFERENCE_PREFIX . $index, $activity);
        }

        $manager->flush();
    }
}
