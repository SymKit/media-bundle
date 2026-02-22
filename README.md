# Symkit Media Bundle

[![CI](https://github.com/symkit/media-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/symkit/media-bundle/actions)
[![Latest Version](https://img.shields.io/packagist/v/symkit/media-bundle.svg)](https://packagist.org/packages/symkit/media-bundle)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)

A powerful, modern media management bundle for Symfony applications, designed for performance, flexibility, and a premium UI/UX.

## Features

- **Async Uploads**: Fast, asynchronous file uploads via dedicated API endpoint.
- **Advanced Security Layer**: Modular security strategy pattern to block threats (Magic Bytes, SVG sanitization, pixel/archive bomb protection, MIME consistency).
- **File Processing**: EXIF stripping, strict file permissions.
- **Media Library**: Grid-based library with search and pagination (Live Component).
- **Media Picker**: Form field for selecting and replacing media assets.
- **SOLID & Configurable**: Activable admin/API, configurable entity and repository, routes via YAML.
- **Translations**: FR and EN included; domain `SymkitMediaBundle`.
- **Hotwired Stack**: Symfony UX Live Components and Stimulus.

## Installation

### 1. Install via Composer

```bash
composer require symkit/media-bundle
```

### 2. Register the Bundle

In `config/bundles.php`:

```php
return [
    // ...
    Symkit\MediaBundle\SymkitMediaBundle::class => ['all' => true],
];
```

### 3. Configuration

Create `config/packages/symkit_media.yaml`:

```yaml
symkit_media:
    public_dir: '%kernel.project_dir%/public'
    media_prefix: '/uploads/media/'
    alt_text_strategy: 'Symkit\MediaBundle\Strategy\FilenameAltTextStrategy'

    admin:
        enabled: true
        route_prefix: 'admin'   # URL prefix for admin routes (e.g. /admin/media)

    api:
        enabled: true          # Async upload endpoint

    doctrine:
        entity: 'Symkit\MediaBundle\Entity\Media'
        repository: 'Symkit\MediaBundle\Repository\MediaRepository'

    search:
        enabled: true           # Register MediaSearchProvider for symkit/search-bundle
        engine: 'default'       # Search engine to attach the provider to
```

### 4. Routes

Include the bundle routes in `config/routes.yaml`:

```yaml
# Admin (list, create, edit, delete)
symkit_media_admin:
    resource: '@SymkitMediaBundle/config/routing_admin.yaml'
    prefix: '%symkit_media.admin.route_prefix%'

# API (async upload)
symkit_media_api:
    resource: '@SymkitMediaBundle/config/routing_api.yaml'
    prefix: /admin/medias/api
```

If `admin.enabled` or `api.enabled` is `false`, do not include the corresponding route block so that those URLs are not exposed.

### 5. Assets

The bundle prepends its Stimulus controllers to AssetMapper. Ensure your app discovers them (e.g. via `assets/bootstrap.js` and Stimulus).

## Activation / Deactivation

- **Disable admin**: Set `admin.enabled: false` and remove or do not load the `symkit_media_admin` routes.
- **Disable API**: Set `api.enabled: false` and remove or do not load the `symkit_media_api` routes.
- **Disable search**: Set `search.enabled: false` to avoid registering the media search provider with [symkit/search-bundle](https://packagist.org/packages/symkit/search-bundle). No attributes are used; the provider is registered via the bundle’s PHP config (tag `symkit_search.provider`).

Only the controllers, routes, and search provider are conditional; core services (MediaManager, forms, Live Components, etc.) remain available when the bundle is enabled.

## Custom Entity and Repository

You can use your own Media entity and repository:

1. Create an entity (e.g. extend `Symkit\MediaBundle\Entity\Media` or implement the same contract) and map it with Doctrine.
2. Create a repository that implements `Symkit\MediaBundle\Repository\MediaRepositoryInterface` (or extend `Symkit\MediaBundle\Repository\MediaRepository` with a constructor accepting `ManagerRegistry` and `string $entityClass`).
3. Configure:

```yaml
symkit_media:
    doctrine:
        entity: 'App\Entity\Media'
        repository: 'App\Repository\MediaRepository'
```

## Translations (FR / EN)

The bundle ships with English and French in `translations/` (domain `SymkitMediaBundle`). The translation path is registered automatically.

- Set your app locale in `config/packages/framework.yaml` (`default_locale`, `enabled_locales`) and use the translator as usual.
- All admin labels, form labels, library and picker UI, and API error messages use this domain.

## Usage

### In Entities

```php
use Symkit\MediaBundle\Entity\Media;

#[ORM\ManyToOne(targetEntity: Media::class)]
#[ORM\JoinColumn(onDelete: 'SET NULL')]
private ?Media $image = null;
```

### In Forms

```php
use Symkit\MediaBundle\Form\MediaType;

$builder->add('image', MediaType::class);
```

### In Twig

```twig
<img src="{{ article.image|media_url }}" alt="{{ article.image.altText }}">
```

## Architecture & SOLID

- **MediaManager**: Orchestrates upload (Security → Processing → Storage → Metadata).
- **MediaRepositoryInterface**: Implement or extend the default repository for custom entity/repository.
- **SecurityRuleInterface**: Tag with `symkit_media.security_rule` for custom security rules.
- **FileProcessorInterface**: Tag with `symkit_media.processor` for custom processors.
- **StorageInterface**: Swap storage backends (Local, S3, etc.).
- **AltTextStrategyInterface**: Customize alt text generation.

## Testing

### Bundle test suite

From the bundle root, run `make test`. After `composer install`, you can also run `make quality` for the full pipeline (cs-check, phpstan, deptrac, lint, test, infection).

The suite includes unit tests (services, form transformer, security rules, Live Components, search provider), integration tests (bundle boot, config, services), and functional tests (API upload).

### Testing in an application

To validate the bundle in a real application (e.g. an app that uses this bundle via Composer):

1. **Run the application** (e.g. `symfony serve` or `php -S localhost:8000 -t public` from the app root).
2. **Admin media**  
   - Open the media list (e.g. `/admin/media` if `route_prefix` is `admin`).  
   - Check: list, create (upload), edit (replace file), delete.
3. **API upload**  
   - POST a file to the async upload endpoint (e.g. `/admin/medias/api/upload` with `file`).  
   - Expect JSON `{ "id", "url", "filename" }` on success.
4. **Media Library / Picker**  
   - Use a form that embeds the media library or picker Live Component; run search, pagination, and selection.
5. **Search**  
   - If the app uses symkit/search-bundle, trigger global search and confirm media results appear.

These steps can be automated with E2E tools (e.g. Playwright, Mink) or run as a manual checklist.

## Contributing

- Run the quality pipeline: `make quality` (or `make cs-fix`, `make phpstan`, `make test`).

## License

MIT
