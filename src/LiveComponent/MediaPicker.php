<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\LiveComponent;

use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Repository\MediaRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('media_picker', template: '@SymkitMedia/media/live_component/media_picker.html.twig')]
final class MediaPicker
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?int $mediaId = null;

    #[LiveProp]
    public string $inputName = '';

    #[LiveProp]
    public bool $modalOpen = false;

    public function __construct(
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    public function getMedia(): ?Media
    {
        if (!$this->mediaId) {
            return null;
        }

        return $this->mediaRepository->find($this->mediaId);
    }

    #[LiveAction]
    public function openModal(): void
    {
        $this->modalOpen = true;
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->modalOpen = false;
    }

    #[LiveAction]
    public function removeMedia(): void
    {
        $this->mediaId = null;
    }

    #[LiveListener('media-selected')]
    public function onMediaSelected(#[LiveArg] int $id, #[LiveArg] ?string $context = null): void
    {
        if ($context !== $this->inputName) {
            return;
        }

        $this->mediaId = $id;
        $this->modalOpen = false;

        // If used inside a form, we need to update the form value
        // The template handles the hidden input sync,
        // but if we are binding to a form field, we update the prop.
    }
}
