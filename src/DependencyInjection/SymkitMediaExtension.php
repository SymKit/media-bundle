<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\DependencyInjection;

use Symkit\MediaBundle\Controller\Admin\MediaController;
use Symkit\MediaBundle\Controller\Api\UploadController;
use Symkit\MediaBundle\EventListener\MediaSubscriber;
use Symkit\MediaBundle\Form\MediaAdminType;
use Symkit\MediaBundle\Form\MediaType;
use Symkit\MediaBundle\Form\Type\MediaUploadType;
use Symkit\MediaBundle\LiveComponent\MediaLibrary;
use Symkit\MediaBundle\LiveComponent\MediaPicker;
use Symkit\MediaBundle\Processor\ExifStripperProcessor;
use Symkit\MediaBundle\Repository\MediaRepository;
use Symkit\MediaBundle\Search\MediaSearchProvider;
use Symkit\MediaBundle\Security\FileSecurityScanner;
use Symkit\MediaBundle\Security\FileSecurityScannerInterface;
use Symkit\MediaBundle\Security\Rule\ArchiveBombRule;
use Symkit\MediaBundle\Security\Rule\ArchiveSecurityRule;
use Symkit\MediaBundle\Security\Rule\FilenameSecurityRule;
use Symkit\MediaBundle\Security\Rule\ImagePixelBombRule;
use Symkit\MediaBundle\Security\Rule\ImageSpoofingRule;
use Symkit\MediaBundle\Security\Rule\MagicBytesSecurityRule;
use Symkit\MediaBundle\Security\Rule\MimeTypeConsistencyRule;
use Symkit\MediaBundle\Security\Rule\SvgSecurityRule;
use Symkit\MediaBundle\Service\ImageMetadataReaderInterface;
use Symkit\MediaBundle\Service\MediaManager;
use Symkit\MediaBundle\Service\MediaUrlGenerator;
use Symkit\MediaBundle\Service\NativeImageMetadataReader;
use Symkit\MediaBundle\Storage\LocalStorage;
use Symkit\MediaBundle\Storage\StorageInterface;
use Symkit\MediaBundle\Strategy\AltTextStrategyInterface;
use Symkit\MediaBundle\Strategy\FilenameAltTextStrategy;
use Symkit\MediaBundle\Twig\MediaExtension as TwigMediaExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

final class SymkitMediaExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->register(MediaRepository::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(FilenameAltTextStrategy::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->setAlias(AltTextStrategyInterface::class, $config['alt_text_strategy']);

        $container->register(NativeImageMetadataReader::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->setAlias(ImageMetadataReaderInterface::class, NativeImageMetadataReader::class);

        $container->register(FilenameSecurityRule::class)
            ->setAutowired(true)
            ->addTag('symkit_media.security_rule')
        ;

        $container->register(MagicBytesSecurityRule::class)
            ->setAutowired(true)
            ->addTag('symkit_media.security_rule')
        ;

        $container->register(ArchiveSecurityRule::class)
            ->setAutowired(true)
            ->addTag('symkit_media.security_rule')
        ;

        $container->register(ArchiveBombRule::class)
            ->setAutowired(true)
            ->addTag('symkit_media.security_rule')
        ;

        $container->register(ImagePixelBombRule::class)
            ->setAutowired(true)
            ->addTag('symkit_media.security_rule')
        ;

        $container->register(ImageSpoofingRule::class)
            ->setAutowired(true)
            ->addTag('symkit_media.security_rule')
        ;

        $container->register(MimeTypeConsistencyRule::class)
            ->setAutowired(true)
            ->addTag('symkit_media.security_rule')
        ;

        $container->register(SvgSecurityRule::class)
            ->setAutowired(true)
            ->addTag('symkit_media.security_rule')
        ;

        $container->register(FileSecurityScanner::class)
            ->setArgument('$rules', new \Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument('symkit_media.security_rule'))
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->setAlias(FileSecurityScannerInterface::class, FileSecurityScanner::class);

        $container->register(ExifStripperProcessor::class)
            ->setAutowired(true)
            ->addTag('symkit_media.processor')
        ;

        $container->register(MediaManager::class)
            ->setArgument('$processors', new \Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument('symkit_media.processor'))
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $uploadDir = mb_rtrim($config['public_dir'], '/') . '/' . mb_ltrim($config['media_prefix'], '/');

        $container->register(LocalStorage::class)
            ->setArgument('$targetDirectory', $uploadDir)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->setAlias(StorageInterface::class, LocalStorage::class);

        $container->register(MediaUrlGenerator::class)
            ->setArguments([
                $config['public_dir'],
                $config['media_prefix'],
            ])
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(MediaAdminType::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(MediaType::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(MediaUploadType::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(MediaSubscriber::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(TwigMediaExtension::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(MediaSearchProvider::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(MediaLibrary::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(MediaPicker::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
        ;

        $container->register(MediaController::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
        ;

        $container->register(UploadController::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
        ;
    }

    public function prepend(ContainerBuilder $container): void
    {
        $bundleDir = \dirname(__DIR__, 2);

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    $bundleDir . '/assets/controllers' => 'media',
                ],
            ],
        ]);
    }

    public function getAlias(): string
    {
        return 'symkit_media';
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }
}
