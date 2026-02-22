<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Unit\Form\DataTransformer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Form\DataTransformer\MediaToIdTransformer;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;

final class MediaToIdTransformerTest extends TestCase
{
    public function testTransformReturnsNullForNull(): void
    {
        $transformer = new MediaToIdTransformer(
            $this->createMock(MediaRepositoryInterface::class),
            $this->createMock(TranslatorInterface::class),
        );
        self::assertNull($transformer->transform(null));
    }

    public function testTransformReturnsIdForMedia(): void
    {
        $media = new Media();
        $ref = new \ReflectionClass($media);
        $prop = $ref->getProperty('id');
        $prop->setValue($media, 42);

        $transformer = new MediaToIdTransformer(
            $this->createMock(MediaRepositoryInterface::class),
            $this->createMock(TranslatorInterface::class),
        );
        self::assertSame(42, $transformer->transform($media));
    }

    public function testTransformReturnsNullForNonMedia(): void
    {
        $transformer = new MediaToIdTransformer(
            $this->createMock(MediaRepositoryInterface::class),
            $this->createMock(TranslatorInterface::class),
        );
        self::assertNull($transformer->transform(new \stdClass()));
    }

    public function testReverseTransformReturnsNullForEmptyValue(): void
    {
        $transformer = new MediaToIdTransformer(
            $this->createMock(MediaRepositoryInterface::class),
            $this->createMock(TranslatorInterface::class),
        );
        self::assertNull($transformer->reverseTransform(null));
        self::assertNull($transformer->reverseTransform(''));
        self::assertNull($transformer->reverseTransform(0));
    }

    public function testReverseTransformReturnsMediaWhenFound(): void
    {
        $media = new Media();
        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('find')->with(1)->willReturn($media);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Media not found');

        $transformer = new MediaToIdTransformer($repo, $translator);
        self::assertSame($media, $transformer->reverseTransform(1));
    }

    public function testReverseTransformThrowsWhenMediaNotFound(): void
    {
        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('find')->with(999)->willReturn(null);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Media not found.');

        $transformer = new MediaToIdTransformer($repo, $translator);

        $this->expectException(TransformationFailedException::class);
        $transformer->reverseTransform(999);
    }
}
