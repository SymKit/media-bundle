<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Unit\Search;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;
use Symkit\MediaBundle\Search\MediaSearchProvider;
use Symkit\SearchBundle\Model\SearchResult;

final class MediaSearchProviderTest extends TestCase
{
    public function testSearchYieldsSearchResultsFromRepository(): void
    {
        $media = new Media();
        $media->setFilename('stored.jpg');
        $media->setOriginalFilename('photo.jpg');
        $media->setAltText('A photo');
        $media->setMimeType('image/jpeg');

        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('findForGlobalSearch')->with('photo')->willReturn([$media]);

        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->with('admin_media_list')->willReturn('/admin/media');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Media');

        $provider = new MediaSearchProvider($repo, $urlGen, $translator);
        $results = iterator_to_array($provider->search('photo'));

        self::assertCount(1, $results);
        self::assertInstanceOf(SearchResult::class, $results[0]);
        self::assertSame('photo.jpg', $results[0]->title);
        self::assertSame('A photo', $results[0]->subtitle);
        self::assertSame('/admin/media', $results[0]->url);
        self::assertSame('heroicons:photo-20-solid', $results[0]->icon);
    }

    public function testGetCategoryReturnsTranslatedString(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with('search.category', [], 'SymkitMediaBundle')
            ->willReturn('Media');

        $provider = new MediaSearchProvider(
            $this->createMock(MediaRepositoryInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $translator,
        );
        self::assertSame('Media', $provider->getCategory());
    }

    public function testGetPriorityReturns30(): void
    {
        $provider = new MediaSearchProvider(
            $this->createMock(MediaRepositoryInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
        );
        self::assertSame(30, $provider->getPriority());
    }
}
