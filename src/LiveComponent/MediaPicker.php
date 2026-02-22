<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\LiveComponent;

use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;

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
        private readonly MediaRepositoryInterface $mediaRepository,
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
