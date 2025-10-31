<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TopicActivityBundle\Entity\Activity;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;
use Tourze\TopicActivityBundle\Repository\ActivityRepository;
use Tourze\TopicActivityBundle\Repository\ActivityTemplateRepository;
use Tourze\TopicActivityBundle\Service\TemplateManager;

/**
 * @internal
 */
#[CoversClass(TemplateManager::class)]
#[RunTestsInSeparateProcesses]
class TemplateManagerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // No special setup needed
    }

    private function getTemplateManager(): TemplateManager
    {
        return self::getService(TemplateManager::class);
    }

    public function testCreateSystemTemplates(): void
    {
        $templateManager = $this->getTemplateManager();
        $templateRepository = self::getService(ActivityTemplateRepository::class);

        // Clean existing templates first
        $existingTemplates = $templateRepository->findAll();
        foreach ($existingTemplates as $template) {
            $templateRepository->remove($template, true);
        }

        $templateManager->createSystemTemplates();

        // Verify templates were created
        $templates = $templateRepository->findAll();
        $this->assertCount(5, $templates);
    }

    public function testCreateSystemTemplatesSkipsExisting(): void
    {
        $templateManager = $this->getTemplateManager();
        $templateRepository = self::getService(ActivityTemplateRepository::class);

        // Create one template first
        $templateManager->createSystemTemplates();
        $initialCount = count($templateRepository->findAll());

        // Run again, should not create duplicates
        $templateManager->createSystemTemplates();
        $finalCount = count($templateRepository->findAll());

        $this->assertEquals($initialCount, $finalCount);
    }

    public function testCreateActivityFromTemplate(): void
    {
        $templateManager = $this->getTemplateManager();
        $templateRepository = self::getService(ActivityTemplateRepository::class);

        $template = new ActivityTemplate();
        $template->setName('Test Template');
        $template->setDescription('Test Description');
        $template->setCode('test_template');
        $template->setLayoutConfig([
            'components' => [
                ['type' => 'text', 'props' => ['content' => 'Hello']],
            ],
        ]);

        $templateRepository->save($template, true);

        $result = $templateManager->createActivityFromTemplate($template);

        $this->assertInstanceOf(Activity::class, $result);
        $this->assertEquals(1, $template->getUsageCount());
    }

    public function testCreateTemplateFromActivity(): void
    {
        $templateManager = $this->getTemplateManager();
        $activityRepository = self::getService(ActivityRepository::class);

        $activity = new Activity();
        $activity->setTitle('Test Activity');
        $activity->setDescription('Test Description');
        $activity->setSlug('test-activity-' . uniqid());
        $activity->setLayoutConfig(['version' => '1.0']);
        $activity->setCoverImage('test.jpg');

        $activityRepository->save($activity, true);

        $template = $templateManager->createTemplateFromActivity(
            $activity,
            'Test Template',
            'test_template'
        );

        $this->assertEquals('Test Template', $template->getName());
        $this->assertEquals('test_template', $template->getCode());
        $this->assertEquals('Test Description', $template->getDescription());
        $this->assertEquals('custom', $template->getCategory());
        $this->assertFalse($template->isSystem());
        $this->assertTrue($template->isActive());
    }

    public function testGetAvailableTemplatesWithCategory(): void
    {
        $templateManager = $this->getTemplateManager();
        $templateRepository = self::getService(ActivityTemplateRepository::class);

        // Create test templates
        $template1 = new ActivityTemplate();
        $template1->setName('Promotion Template 1');
        $template1->setCode('promo1');
        $template1->setCategory('promotion');
        $template1->setActive(true);

        $template2 = new ActivityTemplate();
        $template2->setName('Promotion Template 2');
        $template2->setCode('promo2');
        $template2->setCategory('promotion');
        $template2->setActive(true);

        $templateRepository->save($template1, true);
        $templateRepository->save($template2, true);

        $result = $templateManager->getAvailableTemplates('promotion');

        $this->assertGreaterThanOrEqual(2, count($result));
    }

    public function testGetAvailableTemplatesWithoutCategory(): void
    {
        $templateManager = $this->getTemplateManager();

        $result = $templateManager->getAvailableTemplates();

        $this->assertIsArray($result);
    }

    public function testGetTemplateCategories(): void
    {
        $templateManager = $this->getTemplateManager();
        $categories = $templateManager->getTemplateCategories();

        $this->assertIsArray($categories);
        $this->assertArrayHasKey('promotion', $categories);
        $this->assertArrayHasKey('new_product', $categories);
        $this->assertArrayHasKey('holiday', $categories);
        $this->assertArrayHasKey('brand', $categories);
        $this->assertArrayHasKey('event', $categories);
        $this->assertArrayHasKey('custom', $categories);
        $this->assertEquals('通用促销', $categories['promotion']);
        $this->assertEquals('新品发布', $categories['new_product']);
        $this->assertEquals('节日专题', $categories['holiday']);
    }

    public function testTemplateUsageTracking(): void
    {
        $templateManager = $this->getTemplateManager();
        $templateRepository = self::getService(ActivityTemplateRepository::class);

        $template = new ActivityTemplate();
        $template->setName('Usage Tracking Test Template');
        $template->setCode('usage_tracking_test_' . uniqid());
        $template->setCategory('test');
        $template->setActive(true);
        $template->setSystem(false);
        $template->setUsageCount(0);

        $templateRepository->save($template, true);

        $initialUsageCount = $template->getUsageCount();

        // Use template to create activity
        $result = $templateManager->createActivityFromTemplate($template);

        $this->assertInstanceOf(Activity::class, $result);
        // Verify usage count increased
        $this->assertEquals($initialUsageCount + 1, $template->getUsageCount());
    }
}
