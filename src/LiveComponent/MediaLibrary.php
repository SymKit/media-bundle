<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\LiveComponent;

use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Repository\MediaRepository;
use Symkit\MediaBundle\Service\MediaManager;
use Symkit\MediaBundle\Service\MediaUrlGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('media_library', template: '@SymkitMedia/media/live_component/media_library.html.twig')]
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
        private readonly MediaRepository $mediaRepository,
        private readonly MediaManager $mediaManager,
        private readonly MediaUrlGenerator $urlGenerator,
    ) {
    }

    public function getMedias(): \Doctrine\ORM\Tools\Pagination\Paginator
    {
        return $this->mediaRepository->search($this->query, $this->page, $this->limit);
    }

    #[LiveListener('filterUpdated')]
    public function onFilterUpdated(#[LiveArg('q')] ?string $receivedQ = null): void
    {
        $this->query = $receivedQ ?? '';
        $this->page = 1;
    }

    #[LiveListener('media:uploaded')]
    public function onMediaUploaded(#[LiveArg] ?array $payload = null): void
    {
        // Refresh the list.
        $this->page = 1;

        // If we want to auto-select, we can use the payload
        // $payload contains ['id' => ..., 'url' => ...] from the controller
        if ($payload && isset($payload['id'])) {
            // We can optionally select it.
            // $this->selectMedia($payload['id']);
            // But let's just refresh for now as per plan,
            // user might want to see it first.
            // Actually, "Auto-Select" was in the plan.
            $this->selectMedia((int) $payload['id']);
        }
    }

    #[LiveAction]
    public function selectMedia(#[LiveArg] int $id): void
    {
        $media = $this->mediaRepository->find($id);
        if (!$media) {
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
