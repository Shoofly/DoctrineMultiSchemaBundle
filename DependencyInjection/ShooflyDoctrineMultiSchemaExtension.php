<?php

namespace Shoofly\DoctrineMultiSchemaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Shoofly\DoctrineMultiSchemaBundle\DBAL\MySql;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ShooflyDoctrineMultiSchemaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter( 'shoofly.doctrine_multi_schema.schemas', $config[ 'schemas' ] );

        $this->addClassesToCompile(array(
            MySql\Platform::class,
            MySql\Driver::class,
            MySql\SchemaManager::class 
        ));
        
    }
}
