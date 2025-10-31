<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;

class ActivityTemplateFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $template = new ActivityTemplate();
        $template->setName('Marketing Campaign Template');
        $template->setCode('marketing_campaign');
        $template->setDescription('A template for marketing campaigns with lead generation');
        $template->setCategory('marketing');
        $template->setThumbnail('https://test.local/templates/marketing.jpg');
        $template->setLayoutConfig([
            'sections' => [
                ['id' => 'header', 'components' => ['title', 'subtitle']],
                ['id' => 'content', 'components' => ['main-content', 'image', 'form']],
                ['id' => 'footer', 'components' => ['contact-info']],
            ],
        ]);
        $template->setDefaultData([
            'title' => 'Special Offer',
            'subtitle' => 'Limited Time Only',
            'form' => ['fields' => ['name', 'email', 'phone']],
        ]);
        $template->setTags(['marketing', 'lead-generation', 'campaign']);
        $template->setActive(true);
        $template->setSystem(false);
        $template->setUsageCount(5);

        $manager->persist($template);
        $manager->flush();
    }
}
