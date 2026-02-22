<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Search;

use Symkit\MediaBundle\Repository\MediaRepository;
use Sedie\SearchBundle\Model\SearchResult;
use Sedie\SearchBundle\Provider\SearchProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class MediaSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private MediaRepository $mediaRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function search(string $query): iterable
    {
        $mediaItems = $this->mediaRepository->findForGlobalSearch($query);

        foreach ($mediaItems as $media) {
            yield new SearchResult(
                title: $media->getOriginalFilename() ?? $media->getFilename(),
                subtitle: $media->getAltText() ?? $media->getMimeType(),
                url: $this->urlGenerator->generate('admin_media_list'),
                icon: 'heroicons:photo-20-solid',
                badge: null,
            );
        }
    }

    public function getCategory(): string
    {
        return 'Media';
    }

    public function getPriority(): int
    {
        return 30;
    }
}
