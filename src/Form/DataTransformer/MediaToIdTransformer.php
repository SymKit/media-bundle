<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Form\DataTransformer;

use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Repository\MediaRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class MediaToIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly MediaRepository $repository,
    ) {
    }

    /**
     * @param Media|null $value
     */
    public function transform(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Media) {
            return $value->getId();
        }

        return null;
    }

    /**
     * @param int|string|null $value
     */
    public function reverseTransform(mixed $value): ?Media
    {
        if (!$value) {
            return null;
        }

        $media = $this->repository->find($value);

        if (null === $media) {
            throw new TransformationFailedException(\sprintf('Media with id "%s" not found', $value));
        }

        return $media;
    }
}
