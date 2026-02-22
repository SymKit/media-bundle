<?php

declare(strict_types=1);

namespace Symkit\MediaBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symkit\MediaBundle\Controller\Admin\MediaController;
use Symkit\MediaBundle\Controller\Api\UploadController;
use Symkit\MediaBundle\Entity\Media;
use Symkit\MediaBundle\EventListener\MediaSubscriber;
use Symkit\MediaBundle\Form\MediaAdminType;
use Symkit\MediaBundle\Form\MediaType;
use Symkit\MediaBundle\Form\Type\MediaUploadType;
use Symkit\MediaBundle\LiveComponent\MediaLibrary;
use Symkit\MediaBundle\LiveComponent\MediaPicker;
use Symkit\MediaBundle\Processor\ExifStripperProcessor;
use Symkit\MediaBundle\Repository\MediaRepository;
use Symkit\MediaBundle\Repository\MediaRepositoryInterface;
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

class SymkitMediaBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('public_dir')
                    ->defaultValue('%kernel.project_dir%/public')
                ->end()
                ->scalarNode('media_prefix')
                    ->defaultValue('/uploads/media/')
                ->end()
                ->scalarNode('alt_text_strategy')
                    ->defaultValue(FilenameAltTextStrategy::class)
                ->end()
                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->info('Register admin controller and routes')->end()
                        ->scalarNode('route_prefix')->defaultValue('admin')->info('Route prefix for admin routes (e.g. /admin/media)')->end()
                    ->end()
                ->end()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->info('Register upload API controller and route')->end()
                    ->end()
                ->end()
                ->arrayNode('doctrine')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('entity')
                            ->defaultValue(Media::class)
                            ->info('FQCN of the Media entity')
                        ->end()
                        ->scalarNode('repository')
                            ->defaultValue(MediaRepository::class)
                            ->info('FQCN of the repository (must implement MediaRepositoryInterface)')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('search')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->info('Register MediaSearchProvider for symkit/search-bundle')->end()
                        ->scalarNode('engine')->defaultValue('default')->info('Search engine name to attach the provider to')->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array{
     *     public_dir: string,
     *     media_prefix: string,
     *     alt_text_strategy: string,
     *     admin: array{enabled: bool, route_prefix: string},
     *     api: array{enabled: bool},
     *     doctrine: array{entity: string, repository: string},
     *     search: array{enabled: bool, engine: string}
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()
            ->set('symkit_media.entity', $config['doctrine']['entity'])
            ->set('symkit_media.repository', $config['doctrine']['repository'])
            ->set('symkit_media.admin.route_prefix', $config['admin']['route_prefix']);

        $services = $container->services();
        $services->defaults()->autowire()->autoconfigure();

        $repositoryClass = $config['doctrine']['repository'];
        $services->set($repositoryClass)
            ->arg('$entityClass', '%symkit_media.entity%');

        $services->alias(MediaRepositoryInterface::class, $repositoryClass);

        $services->set(FilenameAltTextStrategy::class);
        $services->alias(AltTextStrategyInterface::class, $config['alt_text_strategy']);

        $services->set(NativeImageMetadataReader::class);
        $services->alias(ImageMetadataReaderInterface::class, NativeImageMetadataReader::class);

        $services->set(FilenameSecurityRule::class)->tag('symkit_media.security_rule');
        $services->set(MagicBytesSecurityRule::class)->tag('symkit_media.security_rule');
        $services->set(ArchiveSecurityRule::class)->tag('symkit_media.security_rule');
        $services->set(ArchiveBombRule::class)->tag('symkit_media.security_rule');
        $services->set(ImagePixelBombRule::class)->tag('symkit_media.security_rule');
        $services->set(ImageSpoofingRule::class)->tag('symkit_media.security_rule');
        $services->set(MimeTypeConsistencyRule::class)->tag('symkit_media.security_rule');
        $services->set(SvgSecurityRule::class)->tag('symkit_media.security_rule');

        $services->set(FileSecurityScanner::class)
            ->arg('$rules', tagged_iterator('symkit_media.security_rule'));
        $services->alias(FileSecurityScannerInterface::class, FileSecurityScanner::class);

        $services->set(ExifStripperProcessor::class)->tag('symkit_media.processor');

        $services->set(MediaManager::class)
            ->arg('$processors', tagged_iterator('symkit_media.processor'));

        $uploadDir = mb_rtrim($config['public_dir'], '/').'/'.mb_ltrim($config['media_prefix'], '/');
        $services->set(LocalStorage::class)->arg('$targetDirectory', $uploadDir);
        $services->alias(StorageInterface::class, LocalStorage::class);

        $publicDir = $config['public_dir'];
        $mediaPrefix = $config['media_prefix'];
        $services->set(MediaUrlGenerator::class)
            ->arg('$publicDir', $publicDir)
            ->arg('$mediaPrefix', $mediaPrefix);

        $services->set(MediaType::class);
        $services->set(MediaUploadType::class);
        $services->set(MediaSubscriber::class);
        $services->set(TwigMediaExtension::class);

        if ($config['search']['enabled']) {
            $services->set(MediaSearchProvider::class)
                ->tag('symkit_search.provider', ['engine' => $config['search']['engine']]);
        }

        $liveComponentTag = [
            'name' => 'media_library',
            'template' => '@SymkitMedia/media/live_component/media_library.html.twig',
            'live' => true,
            'route' => 'ux_live_component',
            'method' => 'post',
            'url_reference_type' => UrlGeneratorInterface::ABSOLUTE_PATH,
        ];
        $services->set(MediaLibrary::class)
            ->tag('twig.component', $liveComponentTag)
            ->tag('controller.service_arguments');

        $pickerTag = [
            'name' => 'media_picker',
            'template' => '@SymkitMedia/media/live_component/media_picker.html.twig',
            'live' => true,
            'route' => 'ux_live_component',
            'method' => 'post',
            'url_reference_type' => UrlGeneratorInterface::ABSOLUTE_PATH,
        ];
        $services->set(MediaPicker::class)
            ->tag('twig.component', $pickerTag)
            ->tag('controller.service_arguments');

        if ($config['admin']['enabled']) {
            $services->set(MediaAdminType::class)->arg('$entityClass', '%symkit_media.entity%');
            $services->set(MediaController::class)
                ->arg('$entityClass', '%symkit_media.entity%')
                ->tag('controller.service_arguments');
        }

        if ($config['api']['enabled']) {
            $services->set(UploadController::class)
                ->arg('$entityClass', '%symkit_media.entity%')
                ->tag('controller.service_arguments');
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $path = $this->getPath();
        $container->extension('framework', [
            'asset_mapper' => [
                'paths' => [
                    $path.'/assets/controllers' => 'media',
                ],
            ],
            'translator' => [
                'paths' => [
                    $path.'/translations',
                ],
            ],
        ], true);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
