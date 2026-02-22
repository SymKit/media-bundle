<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Search;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;
use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Model\SearchResult;

final readonly class MediaSearchProvider implements SearchProviderInterface
{
    private const TRANSLATION_DOMAIN = 'SymkitMediaBundle';

    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function search(string $query): iterable
    {
        $mediaItems = $this->mediaRepository->findForGlobalSearch($query);

        foreach ($mediaItems as $media) {
            if (!$media instanceof Media) {
                continue;
            }
            yield new SearchResult(
                title: $media->getOriginalFilename() ?? $media->getFilename() ?? '',
                subtitle: $media->getAltText() ?? $media->getMimeType() ?? '',
                url: $this->urlGenerator->generate('admin_media_list'),
                icon: 'heroicons:photo-20-solid',
                badge: null,
            );
        }
    }

    public function getCategory(): string
    {
        return $this->translator->trans('search.category', [], self::TRANSLATION_DOMAIN);
    }

    public function getPriority(): int
    {
        return 30;
    }
}
