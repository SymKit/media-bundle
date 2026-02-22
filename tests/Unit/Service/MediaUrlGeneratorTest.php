<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\Service\MediaUrlGenerator;

final class MediaUrlGeneratorTest extends TestCase
{
    private const PUBLIC_DIR = '/var/www/public';
    private const MEDIA_PREFIX = '/uploads/media/';

    private MediaUrlGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new MediaUrlGenerator(self::PUBLIC_DIR, self::MEDIA_PREFIX);
    }

    public function testGenerateUrlReturnsNullForNullMedia(): void
    {
        self::assertNull($this->generator->generateUrl(null));
    }

    public function testGenerateUrlReturnsNullForMediaWithoutFilename(): void
    {
        $media = new Media();
        self::assertNull($this->generator->generateUrl($media));
    }

    public function testGenerateUrlReturnsPrefixPlusFilename(): void
    {
        $media = new Media();
        $media->setFilename('abc123.jpg');
        self::assertSame(self::MEDIA_PREFIX.'abc123.jpg', $this->generator->generateUrl($media));
    }

    public function testGetAbsolutePathReturnsNullForNullMedia(): void
    {
        self::assertNull($this->generator->getAbsolutePath(null));
    }

    public function testGetAbsolutePathReturnsNullForMediaWithoutFilename(): void
    {
        $media = new Media();
        self::assertNull($this->generator->getAbsolutePath($media));
    }

    public function testGetAbsolutePathReturnsPublicDirPlusPrefixPlusFilename(): void
    {
        $media = new Media();
        $media->setFilename('xyz.png');
        self::assertSame(self::PUBLIC_DIR.self::MEDIA_PREFIX.'xyz.png', $this->generator->getAbsolutePath($media));
    }
}
