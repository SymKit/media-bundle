<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\LiveComponent;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;
use Symkit\MediaBundle\Service\MediaUrlGenerator;

final class MediaLibrary
{
    use DefaultActionTrait;
    use \Symfony\UX\LiveComponent\ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp(writable: true)]
    public ?int $page = 1;

    #[LiveProp(writable: true)]
    public int $limit = 24;

    #[LiveProp]
    public ?int $selectedMediaId = null;

    #[LiveProp]
    public bool $selectionMode = false;

    #[LiveProp]
    public string $context = '';

    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly MediaUrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @return Paginator<object>
     */
    public function getMedias(): Paginator
    {
        $page = $this->page ?? 1;

        return $this->mediaRepository->search($this->query, $page, $this->limit);
    }

    #[LiveListener('filterUpdated')]
    public function onFilterUpdated(#[LiveArg('q')] ?string $receivedQ = null): void
    {
        $this->query = $receivedQ ?? '';
        $this->page = 1;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    #[LiveListener('media:uploaded')]
    public function onMediaUploaded(#[LiveArg] ?array $payload = null): void
    {
        // Refresh the list.
        $this->page = 1;

        // If we want to auto-select, we can use the payload
        // $payload contains ['id' => ..., 'url' => ...] from the controller
        if ($payload && isset($payload['id']) && is_numeric($payload['id'])) {
            $this->selectMedia((int) $payload['id']);
        }
    }

    #[LiveAction]
    public function selectMedia(#[LiveArg] int $id): void
    {
        $media = $this->mediaRepository->find($id);
        if (!$media instanceof Media) {
            return;
        }

        $url = $this->urlGenerator->generateUrl($media);

        $this->selectedMediaId = $id;
        $this->emit('media-selected', ['id' => $id, 'context' => $this->context, 'url' => $url]);
        $this->dispatchBrowserEvent('media-selected', ['id' => $id, 'url' => $url, 'context' => $this->context]);
    }

    #[LiveAction]
    public function upload(#[LiveArg] ?UploadedFile $file): void
    {
        // Note: Direct file upload in LiveComponent action usually requires a specific setup
        // with LiveProp(useSerializerForHydration: true) or standard controller handling.
        // For 'ux-dropzone', it's often easier to handle via a standard form submission
        // or a specific file upload endpoint that returns the new media ID.
        // However, let's assume we use a standard form inside the component or a
        // dedicated controller action for the upload, then refresh the list.
    }
}
