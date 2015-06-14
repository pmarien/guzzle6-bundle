<?php

namespace Emoe\GuzzleBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;

class MonologCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->has('monolog.logger') || false === $container->getParameter('guzzle.log.enabled')) {
            return;
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('monolog.yml');

        $monologMiddleware = $container->findDefinition('emoe_guzzle.request_monolog_middleware');

        foreach ($container->findTaggedServiceIds('guzzle.client') as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $arguments = $definition->getArguments();
            if (!isset($arguments[0]['handler'])) {
                continue;
            }
            $stack = $arguments[0]['handler'];
            $monologMiddleware->addMethodCall('attachMiddleware', [$stack]);
            $arguments[0]['monolog_middleware'] = $monologMiddleware;
            $definition->setArguments($arguments);
        }
    }
}