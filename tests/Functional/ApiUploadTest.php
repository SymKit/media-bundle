<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Functional;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Dropzone\DropzoneBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symkit\MediaBundle\SymkitMediaBundle;

final class ApiUploadTest extends KernelTestCase
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
        $kernel->addTestConfig(static function (\Symfony\Component\DependencyInjection\ContainerBuilder $container): void {
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
                'api' => ['enabled' => true],
                'search' => ['enabled' => false],
            ]);
        });
        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testUploadWithoutFileReturns400(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $controller = $container->get(\Symkit\MediaBundle\Controller\Api\UploadController::class);
        $request = Request::create('/upload', 'POST');
        $response = $controller->upload($request);

        self::assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        self::assertIsArray($json);
        self::assertArrayHasKey('error', $json);
    }

    public function testUploadWithInvalidMimeReturns400(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $controller = $container->get(\Symkit\MediaBundle\Controller\Api\UploadController::class);
        $path = tempnam(sys_get_temp_dir(), 'symkit_media_');
        self::assertNotFalse($path);
        file_put_contents($path, 'not an image');
        $file = new UploadedFile($path, 'script.php', 'application/x-php', \UPLOAD_ERR_OK, true);
        $request = Request::create('/upload', 'POST', [], ['file' => $file]);
        $response = $controller->upload($request);

        self::assertSame(400, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        self::assertIsArray($json);
        self::assertArrayHasKey('error', $json);
    }
}
