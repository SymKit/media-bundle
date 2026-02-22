<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('symkit_media');

        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('public_dir')
            ->defaultValue('%kernel.project_dir%/public')
            ->end()
            ->scalarNode('media_prefix')
            ->defaultValue('/uploads/media/')
            ->end()
            ->scalarNode('alt_text_strategy')
            ->defaultValue('Symkit\MediaBundle\Strategy\FilenameAltTextStrategy')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
