<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;

final class MediaToIdTransformer implements DataTransformerInterface
{
    private const TRANSLATION_DOMAIN = 'SymkitMediaBundle';

    public function __construct(
        private readonly MediaRepositoryInterface $repository,
        private readonly TranslatorInterface $translator,
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
            $message = $this->translator->trans('transformer.media_not_found', ['%id%' => $value], self::TRANSLATION_DOMAIN);
            throw new TransformationFailedException($message);
        }

        return $media;
    }
}
