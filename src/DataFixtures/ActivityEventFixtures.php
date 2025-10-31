<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TopicActivityBundle\Entity\ActivityEvent;

class ActivityEventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $event = ActivityEvent::create(1);
        $event->setSessionId('test-session-123');
        $event->setEventType('view');
        $event->setEventData(['page' => '/activity/test']);
        $event->setClientIp('127.0.0.1');
        $event->setUserAgent('Mozilla/5.0 (Test Browser)');
        $event->setReferer('https://test.local');

        $manager->persist($event);
        $manager->flush();
    }
}
