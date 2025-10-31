<?php

declare(strict_types=1);

namespace Tourze\TopicActivityBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TopicActivityBundle\Entity\ActivityTemplate;
use Tourze\TopicActivityBundle\Repository\ActivityTemplateRepository;

/**
 * @internal
 */
#[CoversClass(ActivityTemplateRepository::class)]
#[RunTestsInSeparateProcesses]
final class ActivityTemplateRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // No setup required - using self::getService() directly in tests
    }

    protected function createNewEntity(): object
    {
        $template = new ActivityTemplate();
        $template->setName('Test Template');
        $template->setSlug('test-template-' . uniqid());
        $template->setLayoutConfig(['test' => 'data']);

        return $template;
    }

    /**
     * @return ServiceEntityRepository<ActivityTemplate>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(ActivityTemplateRepository::class);
    }

    public function testSaveAndFindTemplateShouldWorkCorrectly(): void
    {
        $template = new ActivityTemplate();
        $template->setName('Test Template');
        $template->setSlug('test-template-' . uniqid());
        $template->setLayoutConfig(['layout' => 'grid', 'columns' => 3]);

        $repository = self::getService(ActivityTemplateRepository::class);
        $repository->save($template, true);

        $this->assertNotNull($template->getId());

        $foundTemplate = $repository->find($template->getId());

        $this->assertNotNull($foundTemplate);
        $this->assertEquals($template->getId(), $foundTemplate->getId());
        $this->assertEquals('Test Template', $foundTemplate->getName());
        $this->assertEquals($template->getSlug(), $foundTemplate->getSlug());
        $this->assertEquals(['layout' => 'grid', 'columns' => 3], $foundTemplate->getLayoutConfig());
    }

    public function testFindBySlugShouldReturnCorrectTemplate(): void
    {
        $template1 = new ActivityTemplate();
        $template1->setName('First Template');
        $template1->setSlug('first-template-' . uniqid());
        $template1->setLayoutConfig(['type' => 'list']);

        $template2 = new ActivityTemplate();
        $template2->setName('Second Template');
        $template2->setSlug('second-template-' . uniqid());
        $template2->setLayoutConfig(['type' => 'grid']);

        $repository = self::getService(ActivityTemplateRepository::class);
        $repository->save($template1);
        $repository->save($template2, true);

        $slug = $template1->getSlug();
        $this->assertNotNull($slug);
        $foundTemplate = $repository->findBySlug($slug);

        $this->assertNotNull($foundTemplate);
        $this->assertEquals('First Template', $foundTemplate->getName());
        $this->assertEquals($template1->getSlug(), $foundTemplate->getSlug());
    }

    public function testFindBySlugShouldReturnNullForNonExistent(): void
    {
        $repository = self::getService(ActivityTemplateRepository::class);

        $foundTemplate = $repository->findBySlug('non-existent-template');

        $this->assertNull($foundTemplate);
    }

    public function testFindActiveTemplatesShouldReturnOnlyActiveTemplates(): void
    {
        $activeTemplate = new ActivityTemplate();
        $activeTemplate->setName('Active Template');
        $activeTemplate->setSlug('active-template-' . uniqid());
        $activeTemplate->setIsActive(true);
        $activeTemplate->setLayoutConfig(['status' => 'active']);

        $inactiveTemplate = new ActivityTemplate();
        $inactiveTemplate->setName('Inactive Template');
        $inactiveTemplate->setSlug('inactive-template-' . uniqid());
        $inactiveTemplate->setIsActive(false);
        $inactiveTemplate->setLayoutConfig(['status' => 'inactive']);

        $repository = self::getService(ActivityTemplateRepository::class);
        $repository->save($activeTemplate);
        $repository->save($inactiveTemplate, true);

        $activeTemplates = $repository->findActiveTemplates();

        $this->assertGreaterThanOrEqual(1, count($activeTemplates));

        foreach ($activeTemplates as $template) {
            $this->assertTrue($template->isActive());
        }
    }

    public function testTemplateWithComplexLayoutConfigShouldPersistCorrectly(): void
    {
        $complexConfig = [
            'layout' => 'masonry',
            'settings' => [
                'columns' => 4,
                'gap' => '16px',
                'responsive' => [
                    'mobile' => ['columns' => 1],
                    'tablet' => ['columns' => 2],
                    'desktop' => ['columns' => 4],
                ],
            ],
            'components' => [
                [
                    'type' => 'header',
                    'position' => 'top',
                    'config' => ['title' => 'Welcome', 'subtitle' => 'Get started'],
                ],
                [
                    'type' => 'content',
                    'position' => 'main',
                    'config' => ['blocks' => ['text', 'image', 'button']],
                ],
            ],
            'styles' => [
                'theme' => 'modern',
                'colors' => ['primary' => '#007bff', 'secondary' => '#6c757d'],
                'fonts' => ['heading' => 'Roboto', 'body' => 'Open Sans'],
            ],
        ];

        $template = new ActivityTemplate();
        $template->setName('Complex Template');
        $template->setSlug('complex-template-' . uniqid());
        $template->setLayoutConfig($complexConfig);

        $repository = self::getService(ActivityTemplateRepository::class);
        $repository->save($template, true);

        $foundTemplate = $repository->find($template->getId());

        $this->assertNotNull($foundTemplate);
        $this->assertEquals($complexConfig, $foundTemplate->getLayoutConfig());
    }

    public function testRemoveTemplateShouldDeleteFromDatabase(): void
    {
        $template = new ActivityTemplate();
        $template->setName('Removable Template');
        $template->setSlug('removable-template-' . uniqid());
        $template->setLayoutConfig(['removable' => true]);

        $repository = self::getService(ActivityTemplateRepository::class);
        $repository->save($template, true);

        $templateId = $template->getId();
        $this->assertNotNull($templateId);

        $repository->remove($template, true);

        $foundTemplate = $repository->find($templateId);
        $this->assertNull($foundTemplate);
    }

    public function testFindByNamePatternShouldFilterCorrectly(): void
    {
        $template1 = new ActivityTemplate();
        $template1->setName('Marketing Template A');
        $template1->setSlug('marketing-a-' . uniqid());
        $template1->setLayoutConfig(['category' => 'marketing']);

        $template2 = new ActivityTemplate();
        $template2->setName('Marketing Template B');
        $template2->setSlug('marketing-b-' . uniqid());
        $template2->setLayoutConfig(['category' => 'marketing']);

        $template3 = new ActivityTemplate();
        $template3->setName('Event Template');
        $template3->setSlug('event-template-' . uniqid());
        $template3->setLayoutConfig(['category' => 'event']);

        $repository = self::getService(ActivityTemplateRepository::class);
        $repository->save($template1);
        $repository->save($template2);
        $repository->save($template3, true);

        $marketingTemplates = $repository->findByNamePattern('Marketing%');

        $this->assertGreaterThanOrEqual(2, count($marketingTemplates));
        foreach ($marketingTemplates as $template) {
            $this->assertStringStartsWith('Marketing', $template->getName());
        }
    }

    public function testFindByCategoryShouldGroupCorrectly(): void
    {
        $ecommerceTemplate = new ActivityTemplate();
        $ecommerceTemplate->setName('E-commerce Template');
        $ecommerceTemplate->setSlug('ecommerce-template-' . uniqid());
        $ecommerceTemplate->setCategory('ecommerce');
        $ecommerceTemplate->setLayoutConfig(['type' => 'product-showcase']);

        $eventTemplate = new ActivityTemplate();
        $eventTemplate->setName('Event Template');
        $eventTemplate->setSlug('event-template-2-' . uniqid());
        $eventTemplate->setCategory('event');
        $eventTemplate->setLayoutConfig(['type' => 'event-listing']);

        $repository = self::getService(ActivityTemplateRepository::class);
        $repository->save($ecommerceTemplate);
        $repository->save($eventTemplate, true);

        $ecommerceTemplates = $repository->findByCategory('ecommerce');
        $eventTemplates = $repository->findByCategory('event');

        $this->assertCount(1, $ecommerceTemplates);
        $this->assertCount(1, $eventTemplates);
        $this->assertEquals('ecommerce', $ecommerceTemplates[0]->getCategory());
        $this->assertEquals('event', $eventTemplates[0]->getCategory());
    }

    public function testFindByCode(): void
    {
        $repository = self::getService(ActivityTemplateRepository::class);

        $template = new ActivityTemplate();
        $template->setName('Find By Code Template');
        $template->setSlug('find-by-code-template-' . uniqid());
        $template->setCode('TEST_CODE_' . uniqid());
        $template->setCategory('test');
        $template->setLayoutConfig(['type' => 'test']);
        $repository->save($template, true);

        $foundTemplate = $repository->findByCode($template->getCode());

        $this->assertInstanceOf(ActivityTemplate::class, $foundTemplate);
        $this->assertEquals($template->getId(), $foundTemplate->getId());
        $this->assertEquals($template->getCode(), $foundTemplate->getCode());

        // Test with non-existent code
        $notFoundTemplate = $repository->findByCode('NON_EXISTENT_CODE');
        $this->assertNull($notFoundTemplate);
    }

    public function testFindPopularTemplates(): void
    {
        $repository = self::getService(ActivityTemplateRepository::class);

        // Create templates with different usage counts
        $popularTemplate = new ActivityTemplate();
        $popularTemplate->setName('Popular Template');
        $popularTemplate->setSlug('popular-template-' . uniqid());
        $popularTemplate->setCategory('popular');
        $popularTemplate->setLayoutConfig(['type' => 'popular']);
        $popularTemplate->setUsageCount(100);
        $repository->save($popularTemplate, true);

        $lessPopularTemplate = new ActivityTemplate();
        $lessPopularTemplate->setName('Less Popular Template');
        $lessPopularTemplate->setSlug('less-popular-template-' . uniqid());
        $lessPopularTemplate->setCategory('popular');
        $lessPopularTemplate->setLayoutConfig(['type' => 'popular']);
        $lessPopularTemplate->setUsageCount(50);
        $repository->save($lessPopularTemplate, true);

        $popularTemplates = $repository->findPopularTemplates(1);

        $this->assertCount(1, $popularTemplates);
        $this->assertEquals($popularTemplate->getId(), $popularTemplates[0]->getId());
        $this->assertEquals(100, $popularTemplates[0]->getUsageCount());
    }

    public function testFindSystemTemplates(): void
    {
        $repository = self::getService(ActivityTemplateRepository::class);

        // Create system template
        $systemTemplate = new ActivityTemplate();
        $systemTemplate->setName('System Template');
        $systemTemplate->setSlug('system-template-' . uniqid());
        $systemTemplate->setCategory('system');
        $systemTemplate->setLayoutConfig(['type' => 'system']);
        $systemTemplate->setIsSystem(true);
        $repository->save($systemTemplate, true);

        // Create non-system template
        $userTemplate = new ActivityTemplate();
        $userTemplate->setName('User Template');
        $userTemplate->setSlug('user-template-' . uniqid());
        $userTemplate->setCategory('user');
        $userTemplate->setLayoutConfig(['type' => 'user']);
        $userTemplate->setIsSystem(false);
        $repository->save($userTemplate, true);

        $systemTemplates = $repository->findSystemTemplates();

        $systemTemplateIds = array_map(fn ($t) => $t->getId(), $systemTemplates);
        $this->assertContains($systemTemplate->getId(), $systemTemplateIds);
        $this->assertNotContains($userTemplate->getId(), $systemTemplateIds);
    }

    public function testFlush(): void
    {
        $repository = self::getService(ActivityTemplateRepository::class);
        $template = new ActivityTemplate();
        $template->setName('Flush Test Template');
        $template->setDescription('Test template for flush');

        $repository->save($template, false);

        // Call flush explicitly
        $repository->flush();

        // Entity should be persisted after flush
        $this->assertNotNull($template->getId());
    }
}
