<?php

namespace KHerGe\Box\Config;

use Phar;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the expected configuration tree for normalization and validation.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Definition implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('box');

        $this->buildBasic($root);
        $this->buildObservers($root);
        $this->buildGit($root);
        $this->buildSources($root);
        $this->buildStub($root);

        return $builder;
    }

    /**
     * Builds the basic configuration tree.
     *
     * This part of the configuration tree builder will define very basic
     * options used to build the archive, as well as some default values for
     * those options. With the default options, we should be able to simply
     * iterate through each option and apply them.
     *
     * @param ArrayNodeDefinition|NodeDefinition $root The root node.
     */
    private function buildBasic($root)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $root
            ->children()
                ->enumNode('compression')
                    ->defaultValue('NONE')
                    ->treatNullLike('NONE')
                    ->values(
                        array(
                            'BZ2',
                            'GZ',
                            'NONE'
                        )
                    )
                ->end()
                ->variableNode('metadata')->end()
                ->integerNode('mode')
                    ->defaultValue(644)
                ->end()
                ->scalarNode('output')
                    ->defaultValue('output.phar')
                ->end()
                ->arrayNode('signature')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifString()->then(
                            function ($value) {
                                return array('type' => $value);
                            }
                        )
                    ->end()
                    ->children()
                        ->scalarNode('file')->cannotBeEmpty()->end()
                        ->enumNode('type')
                            ->defaultValue('SHA1')
                            ->isRequired()
                            ->values(
                                array(
                                    'MD5',
                                    'SHA1',
                                    'SHA256',
                                    'SHA512',
                                    'OPENSSL'
                                )
                            )
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Builds the Git information configuration tree.
     *
     * This part of the configuration tree builder will define how Git tags and
     * commit hashes can be used to replace a placeholder value in files that
     * are added, as well as some default options. With the default options, we
     * should be able to simply apply them with the user given values.
     *
     * @param ArrayNodeDefinition|NodeDefinition $root The root node.
     */
    private function buildGit($root)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $root
            ->children()
                ->arrayNode('git')
                    ->beforeNormalization()
                        ->ifString()->then(
                            function ($value) {
                                return array('replace' => $value);
                            }
                        )
                    ->end()
                    ->children()
                        ->scalarNode('path')->cannotBeEmpty()->end()
                        ->scalarNode('replace')->isRequired()->cannotBeEmpty()->end()
                        ->enumNode('value')
                            ->defaultValue('tag/commit')
                            ->values(
                                array(
                                    'commit',
                                    'commit-long',
                                    'tag',
                                    'tag/commit',
                                    'tag/commit-long',
                                )
                            )
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Builds the observers configuration tree.
     *
     * This part of the configuration tree builder will define how observer
     * class are loaded and configured, as well as some default options. With
     * the default options, we should be able to iterate the settings and be
     * able to instantiate and configure each observer that will be registered
     * with the builder.
     *
     * @param ArrayNodeDefinition|NodeDefinition $root The root node.
     */
    private function buildObservers($root)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $root
            ->children()
                ->arrayNode('observers')
                    ->children()
                        ->scalarNode('loader')->cannotBeEmpty()->end()
                        ->arrayNode('setup')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                                    ->enumNode('event')
                                        ->isRequired()
                                        ->values(
                                            array(
                                                'ADD_DIR',
                                                'ADD_FILE',
                                                'ADD_STRING',
                                                'BUILD_DIR',
                                                'BUILD_ITERATOR',
                                                'SET_STUB',
                                            )
                                        )
                                    ->end()
                                    ->arrayNode('arguments')
                                        ->prototype('variable')->end()
                                    ->end()
                                    ->arrayNode('methods')
                                        ->prototype('array')
                                            ->beforeNormalization()
                                                ->ifString()->then(
                                                    function ($value) {
                                                        return array('name' => $value);
                                                    }
                                                )
                                            ->end()
                                            ->children()
                                                ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                                ->arrayNode('arguments')
                                                    ->prototype('variable')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Builds the sources configuration tree.
     *
     * This part of the configuration tree builder will define the possible
     * sources used to build the archive, as well as some default values for
     * those options. With the default options, we should be able to simply
     * iterate through each source and use its value.
     *
     * @param ArrayNodeDefinition|NodeDefinition $root The root node.
     */
    private function buildSources($root)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $root
            ->children()
                ->arrayNode('sources')
                    ->addDefaultsIfNotSet()
                    ->children()

                        // base directory path
                        ->scalarNode('base')->defaultNull()->end()

                        // directories
                        ->arrayNode('dirs')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()->then(
                                        function ($value) {
                                            return array('path' => $value);
                                        }
                                    )
                                ->end()
                                ->children()
                                    ->booleanNode('binary')->defaultFalse()->end()
                                    ->arrayNode('extension')
                                        ->beforeNormalization()
                                            ->ifString()->then(
                                                function ($value) {
                                                    return array($value);
                                                }
                                            )
                                        ->end()
                                        ->prototype('scalar')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->defaultValue(array('php'))
                                    ->end()
                                    ->arrayNode('ignore')
                                        ->beforeNormalization()
                                            ->ifString()->then(
                                                function ($value) {
                                                    return array($value);
                                                }
                                            )
                                        ->end()
                                        ->prototype('scalar')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                    ->end()
                                    ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('rename')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()

                        // files
                        ->arrayNode('files')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()->then(
                                        function ($value) {
                                            return array('path' => $value);
                                        }
                                    )
                                ->end()
                                ->children()
                                    ->booleanNode('binary')->defaultFalse()->end()
                                    ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('rename')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()

                    ->end()
                ->end()
            ->end();
    }

    /**
     * Builds the stub configuration tree.
     *
     * This part of the tree builder will define the possible stub configuration
     * options, as well as some of the default values for those options. With
     * the default options, we should be able to simply iterate through each
     * configuration option and use its value.
     *
     * @param ArrayNodeDefinition|NodeDefinition $root The root node.
     */
    private function buildStub($root)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $root
            ->children()
                ->arrayNode('stub')
                    ->children()

                        // addRequire()
                        ->arrayNode('require')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()->then(
                                        function ($value) {
                                            return array('file' => $value);
                                        }
                                    )
                                ->end()
                                ->children()
                                    ->scalarNode('file')->isRequired()->cannotBeEmpty()->end()
                                    ->booleanNode('internal')->defaultTrue()->end()
                                ->end()
                            ->end()
                        ->end()

                        // addSource()
                        ->arrayNode('source')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()->then(
                                        function ($value) {
                                            return array('source' => $value);
                                        }
                                    )
                                ->end()
                                ->children()
                                    ->booleanNode('after')->defaultTrue()->end()
                                    ->scalarNode('source')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()

                        // interceptFileFuncs()
                        ->booleanNode('intercept')->defaultFalse()->end()

                        // loadPhar()
                        ->arrayNode('load')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()->then(
                                        function ($value) {
                                            return array('file' => $value);
                                        }
                                    )
                                ->end()
                                ->children()
                                    ->scalarNode('alias')->defaultNull()->end()
                                    ->scalarNode('file')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()

                        // mapPhar()
                        ->scalarNode('map')->defaultNull()->end()

                        // mount()
                        ->arrayNode('mount')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('external')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('internal')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()

                        // mungServer()
                        ->arrayNode('mung')
                            ->prototype('enum')
                                ->values(
                                    array(
                                        'PHP_SELF',
                                        'REQUEST_URI',
                                        'SCRIPT_FILENAME',
                                        'SCRIPT_NAME'
                                    )
                                )
                            ->end()
                        ->end()

                        // selfExtracting()
                        ->booleanNode('extractable')->defaultFalse()->end()

                        // setBanner()
                        ->scalarNode('banner')->defaultNull()->end()

                        // setShebang()
                        ->scalarNode('shebang')->defaultValue('#!/usr/bin/env php')->end()

                        // webPhar()
                        ->arrayNode('web')
                            ->children()
                                ->scalarNode('alias')->defaultNull()->end()
                                ->scalarNode('index')->defaultValue('index.php')->end()
                                ->scalarNode('not_found')->defaultNull()->end()
                                ->arrayNode('mime')
                                    ->useAttributeAsKey('extension')
                                    ->prototype('scalar')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->defaultValue(
                                        array(
                                            'avi' => 'video/avi',
                                            'bmp' => 'image/bmp',
                                            'c' => 'text/plain',
                                            'c++' => 'text/plain',
                                            'cc' => 'text/plain',
                                            'cpp' => 'text/plain',
                                            'css' => 'text/css',
                                            'dtd' => 'text/plain',
                                            'gif' => 'image/gif',
                                            'h' => 'text/plain',
                                            'htm' => 'text/html',
                                            'html' => 'text/html',
                                            'htmls' => 'text/html',
                                            'ico' => 'image/x-ico',
                                            'inc' => Phar::PHP,
                                            'jpe' => 'image/jpeg',
                                            'jpeg' => 'image/jpeg',
                                            'jpg' => 'image/jpeg',
                                            'js' => 'application/x-javascript',
                                            'log' => 'text/plain',
                                            'mid' => 'audio/midi',
                                            'midi' => 'audio/midi',
                                            'mod' => 'audio/mod',
                                            'mov' => 'movie/quicktime',
                                            'mp3' => 'audio/mp3',
                                            'mpeg' => 'video/mpeg',
                                            'mpg' => 'video/mpeg',
                                            'pdf' => 'application/pdf',
                                            'php' => Phar::PHP,
                                            'phps' => Phar::PHPS,
                                            'png' => 'image/png',
                                            'rng' => 'text/plain',
                                            'swf' => 'application/shockwave-flash',
                                            'tif' => 'image/tiff',
                                            'tiff' => 'image/tiff',
                                            'txt' => 'text/plain',
                                            'wav' => 'audio/wav',
                                            'xbm' => 'image/xbm',
                                            'xml' => 'text/xml',
                                            'xsd' => 'text/plain',
                                        )
                                    )
                                ->end()
                                ->scalarNode('rewrite')->defaultNull()->end()
                            ->end()
                        ->end()

                    ->end()
                ->end()
            ->end();
    }
}
