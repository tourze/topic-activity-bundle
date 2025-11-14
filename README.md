# Topic Activity Bundle

[English](README.md) | [中文](README.zh-CN.md)

A powerful Symfony bundle for managing visual landing pages and marketing activities with a drag-and-drop editor.

## Features

- **<� Visual Editor** - Intuitive drag-and-drop interface for creating landing pages
- **=� Responsive Design** - Mobile-first approach with multi-device preview
- **>� Component System** - Rich set of reusable components (text, images, buttons, forms, etc.)
- **=� Template Management** - Pre-built templates and custom template creation
- **=� Analytics & Statistics** - Comprehensive tracking and reporting
- **= Activity Lifecycle** - Full control over activity status (draft, published, archived, etc.)
- **= Access Control** - Role-based permissions and access restrictions
- **<� SEO Optimization** - Built-in SEO configuration and metadata management

## Installation

```bash
composer require tourze/topic-activity-bundle
```

## Configuration

### Register the Bundle

Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Tourze\TopicActivityBundle\TopicActivityBundle::class => ['all' => true],
];
```

### Environment Variables

Optional environment variables for customization:

```bash
# .env
TOPIC_ACTIVITY_UPLOAD_DIR=/var/uploads/activities
TOPIC_ACTIVITY_CDN_URL=https://cdn.example.com
TOPIC_ACTIVITY_CACHE_TTL=3600
TOPIC_ACTIVITY_STATS_ENABLED=true
TOPIC_ACTIVITY_STATS_SAMPLE_RATE=1.0
TOPIC_ACTIVITY_MAX_COMPONENTS_PER_PAGE=50
```

## Quick Start

### Initialize Templates

Run the console command to initialize default templates:

```bash
php bin/console topic-activity:init-templates
```

This will create default templates including:
- Basic layouts
- Component sets
- Starter configurations

### Component Types

#### Basic Components

- **Text** (`text`) - Rich text content with formatting options
- **Image** (`image`) - Single or multiple images with links and alt text
- **Button** (`button`) - Clickable buttons with customizable styles
- **Divider** (`divider`) - Visual content separators
- **Video** (`video`) - Embedded video player with controls

#### Marketing Components

- **Banner** (`banner`) - Hero banners with carousel functionality
- **Countdown** (`countdown`) - Event countdown timer with custom styling

#### Form Components

- **Input** (`input`) - Text input fields for user data collection
- **Select** (`select`) - Dropdown selection components
- **File Upload** (`file_upload`) - File upload functionality with validation

### Activity Status Management

Activities follow a complete lifecycle with the following statuses:

- `draft` - Initial state, not visible to public
- `scheduled` - Set for future publication
- `published` - Active and publicly visible
- `archived` - No longer active but preserved
- `deleted` - Soft deleted state

### API Endpoints

#### Activities

- `GET /api/activities` - List all activities
- `GET /api/activities/{id}` - Get specific activity details
- `POST /api/activities` - Create new activity
- `PUT /api/activities/{id}` - Update existing activity
- `DELETE /api/activities/{id}` - Delete activity

#### Templates

- `GET /api/templates` - List all templates
- `GET /api/templates/{id}` - Get specific template details
- `POST /api/templates` - Create new template

## Advanced Usage

### Custom Components

Extend the component system by creating custom components:

```php
namespace App\Component;

use Tourze\TopicActivityBundle\Component\AbstractComponent;

class CustomComponent extends AbstractComponent
{
    protected string $type = 'custom';
    protected string $name = 'Custom Component';

    public function getDefaultConfig(): array
    {
        return [
            'title' => 'Default Title',
            'content' => '',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'title' => [
                'type' => 'string',
                'required' => true,
                'label' => 'Title',
            ],
            'content' => [
                'type' => 'string',
                'required' => false,
                'label' => 'Content',
            ],
        ];
    }
}
```

Register your component:

```yaml
services:
    App\Component\CustomComponent:
        tags:
            - { name: 'topic_activity.component' }
```

Create template `templates/components/custom.html.twig`:

```twig
<div class="custom-component">
    <h3>{{ config.title }}</h3>
    <div>{{ config.content|raw }}</div>
</div>
```

### Event System

The bundle emits events for activity lifecycle management:

- `activity.created` - When a new activity is created
- `activity.updated` - When an activity is modified
- `activity.published` - When an activity goes live
- `activity.archived` - When an activity is archived
- `activity.deleted` - When an activity is deleted

Example event listener:

```php
namespace App\EventListener;

use Tourze\TopicActivityBundle\Event\ActivityLifecycleEvent;

class ActivityListener
{
    public function onActivityPublished(ActivityLifecycleEvent $event): void
    {
        $activity = $event->getActivity();
        // Handle activity publication logic
    }
}
```

## Testing

Run the test suite:

```bash
# Run PHPUnit tests
./vendor/bin/phpunit packages/topic-activity-bundle

# Run PHPStan analysis
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/topic-activity-bundle

# Check coding standards
./vendor/bin/php-cs-fixer fix packages/topic-activity-bundle --dry-run
```

## Performance Optimization

### Caching Strategy

- **Page Caching**: Uses Symfony HTTP Cache with dynamic TTL
- **Component Caching**: Independent caching for static components
- **CDN Integration**: All static resources served via CDN
- **Data Caching**: Redis caching for frequently accessed data

### Async Processing

- **Event Tracking**: Asynchronous processing via message queues
- **Image Processing**: Background thumbnail generation
- **Statistics**: Batch processing for analytics data

## Security Features

- **XSS Protection**: Input sanitization with HTMLPurifier
- **CSRF Protection**: Token-based form protection
- **Access Control**: Role-based permission system
- **Input Validation**: Comprehensive validation using Symfony Validator
- **File Upload**: Secure file handling with type and size restrictions

## License

MIT License

## Contributing

Issues and Pull Requests are welcome! Please ensure to follow the coding standards and include tests for new features.

## Changelog

### v0.0.1
- Initial release
- Basic activity management
- Component system
- Template functionality
- Visual editor integration