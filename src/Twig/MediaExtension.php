<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Twig;

use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Service\MediaUrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class MediaExtension extends AbstractExtension
{
    public function __construct(
        private readonly MediaUrlGenerator $urlGenerator,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('media_url', [$this, 'getMediaUrl']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('media_url', [$this, 'getMediaUrl']),
        ];
    }

    public function getMediaUrl(?Media $media): ?string
    {
        if (!$media) {
            return null;
        }

        return $this->urlGenerator->generateUrl($media);
    }
}
