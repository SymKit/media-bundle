# Symkit Media Bundle

A powerful, modern media management bundle for Symfony applications, designed for performance, flexibility, and a premium UI/UX.

## Features

- **🚀 Async Uploads**: Fast, asynchronous file uploads via dedicated API endpoint.
- **🛡️ Advanced Security Layer**: Modular security strategy pattern to block threats.
    - **Magic Bytes Detection**: Detects ELF, Windows PE, and Mach-O executables even with fake extensions.
    - **SVG Sanitization**: Strips XSS/XXE vectors (scripts, event handlers, external entities).
    - **Anti-DoS**: Pixel bomb protection (max 100MP) and archive bomb detection.
    - **MIME Consistency**: Extensions must match the actual file content.
- **🧹 File Processing**: Automatically post-processes uploads.
    - **EXIF Stripping**: Removes GPS and sensitive camera data from JPEGs and WebP files.
    - **Strict Permissions**: Enforces `chmod 600` on all local storage files.
- **🖼️ Media Library**: Beautiful grid-based library with search and pagination.
- **🔌 Media Picker**: Smart form field for selecting and replacing media assets in any form.
- **🏗️ SOLID & Decoupled**: 
    - **Pluggable Storage**: Switch between local, S3, or custom storage via `StorageInterface`.
    - **Alt Text Strategies**: Configure default alt text generation via `AltTextStrategyInterface`.
    - **Image Dimensions**: Width and height are automatically metadata-extracted.
- **⚡ Hotwired Stack**: Fully integrated with Symfony UX Live Components and Stimulus.

## Installation

### 1. Install via Composer

```bash
composer require symkit/media-bundle
```

### 2. Register the Bundle

Ensure the bundle is registered in `config/bundles.php`:

```php
return [
    // ...
    Symkit\MediaBundle\SymkitMediaBundle::class => ['all' => true],
];
```

### 3. Setup Assets

The bundle uses Symfony AssetMapper and Stimulus. Controllers are automatically prepended to your configuration. You only need to ensure they are discovered:

```javascript
// assets/bootstrap.js or stimulus_bootstrap.js
import { startStimulusApp } from '@symfony/stimulus-bundle';
const app = startStimulusApp();
```

### 4. Configuration

Create `config/packages/symkit_media.yaml`:

```yaml
symkit_media:
    public_dir: '%kernel.project_dir%/public'
    media_prefix: '/uploads/media/'
    alt_text_strategy: 'Symkit\MediaBundle\Strategy\FilenameAltTextStrategy'
```

## Usage

### In Entities

Associate the `Media` entity:

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

## Architecture & SOLID Principles

The bundle uses a highly modular architecture based on tagged services:

- **`MediaManager`**: Orchestrates the multi-stage upload process (Security -> Processing -> Storage -> Metadata).
- **`SecurityRuleInterface`**: Add custom security checks via the `symkit_media.security_rule` tag.
- **`FileProcessorInterface`**: Add file modifiers (optimization, watermarking) via the `symkit_media.processor` tag.
- **`StorageInterface`**: Interchangeable storage backends (Local, S3).
- **`AltTextStrategyInterface`**: Customizable alt text generation logic.

## License

MIT
