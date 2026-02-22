<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Dropzone\DropzoneBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;
use Symkit\MediaBundle\Security\FileSecurityScannerInterface;
use Symkit\MediaBundle\Service\MediaManager;
use Symkit\MediaBundle\Service\MediaUrlGenerator;
use Symkit\MediaBundle\Storage\StorageInterface;
use Symkit\MediaBundle\SymkitMediaBundle;

final class BundleBootTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(FrameworkBundle::class);
        $kernel->addTestBundle(DoctrineBundle::class);
        $kernel->addTestBundle(TwigBundle::class);
        $kernel->addTestBundle(DropzoneBundle::class);
        $kernel->addTestBundle(StimulusBundle::class);
        $kernel->addTestBundle(TwigComponentBundle::class);
        $kernel->addTestBundle(LiveComponentBundle::class);
        $kernel->addTestBundle(SymkitMediaBundle::class);
        $kernel->addTestConfig(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'test' => true,
                'form' => ['enabled' => true],
                'csrf_protection' => false,
            ]);
            $container->loadFromExtension('doctrine', [
                'dbal' => ['url' => 'sqlite:///:memory:'],
                'orm' => [
                    'mappings' => [
                        'SymkitMediaBundle' => [
                            'type' => 'attribute',
                            'dir' => __DIR__.'/../../src/Entity',
                            'prefix' => 'Symkit\MediaBundle\Entity',
                        ],
                    ],
                ],
            ]);
            $container->loadFromExtension('symkit_media', [
                'admin' => ['enabled' => false],
                'api' => ['enabled' => false],
                'search' => ['enabled' => false],
            ]);
        });
        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testBundleBootsWithoutError(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        self::assertNotNull($container);
    }

    public function testCoreServicesAreRegistered(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        self::assertTrue($container->has(MediaRepositoryInterface::class));
        self::assertTrue($container->has(MediaManager::class));
        self::assertTrue($container->has(StorageInterface::class));
        self::assertTrue($container->has(FileSecurityScannerInterface::class));
        self::assertTrue($container->has(MediaUrlGenerator::class));
    }

    public function testConfigParametersAreSet(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        self::assertTrue($container->hasParameter('symkit_media.entity'));
        self::assertSame('Symkit\MediaBundle\Entity\Media', $container->getParameter('symkit_media.entity'));
        self::assertTrue($container->hasParameter('symkit_media.repository'));
    }

    public function testTwigAndAssetMapperPrependApplied(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        self::assertTrue($container->has('twig'));
        $twig = $container->get('twig');
        self::assertInstanceOf(\Twig\Environment::class, $twig);
    }
}
