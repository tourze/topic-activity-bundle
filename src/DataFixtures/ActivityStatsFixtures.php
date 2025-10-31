<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityStats;

class ActivityStatsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $activity = new Activity();
        $activity->setTitle('Test Activity for Stats');
        $manager->persist($activity);

        $stats = new ActivityStats();
        $stats->setActivity($activity);
        $stats->setPv(100);
        $stats->setUv(80);
        $stats->setShareCount(5);
        $stats->setFormSubmitCount(3);
        $stats->setConversionCount(2);
        $stats->setStayDuration(150.0);
        $stats->setBounceRate(25.0);

        $manager->persist($stats);
        $manager->flush();
    }
}
