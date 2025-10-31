<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityComponent;
use Tourze\TopicActivityBundle\Enum\ActivityStatus;

#[When(env: 'test')]
#[When(env: 'dev')]
class ActivityComponentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get reference to activity from ActivityFixtures
        /** @var Activity $activity */
        $activity = $this->getReference(ActivityFixtures::ACTIVITY_REFERENCE_PREFIX . '0', Activity::class);

        // Create multiple components for testing
        $components = [
            [
                'type' => 'text',
                'config' => ['content' => 'Welcome to our amazing activity!'],
                'position' => 1,
            ],
            [
                'type' => 'image',
                'config' => ['src' => '/images/banner.jpg', 'alt' => 'Activity Banner'],
                'position' => 2,
            ],
            [
                'type' => 'button',
                'config' => ['label' => 'Join Now', 'url' => '/register'],
                'position' => 3,
            ],
        ];

        foreach ($components as $componentData) {
            $component = new ActivityComponent();
            $component->setComponentType($componentData['type']);
            $component->setComponentConfig($componentData['config']);
            $component->setPosition($componentData['position']);
            $component->setIsVisible(true);
            $component->setActivity($activity);

            $manager->persist($component);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ActivityFixtures::class,
        ];
    }
}
