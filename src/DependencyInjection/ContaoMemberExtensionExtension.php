<?php

/**
 * Procuna
 *
 * @link      https://www.oveleon.de/
 * @copyright Copyright (c) 2021  Oveleon GbR (https://www.oveleon.de)
 */


namespace Oveleon\ContaoMemberExtensionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContaoMemberExtensionExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('listener.yml');
    }
}
