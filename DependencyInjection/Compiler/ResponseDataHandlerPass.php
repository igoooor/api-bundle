<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:44
 */

namespace Igoooor\ApiBundle\DependencyInjection\Compiler;

use Igoooor\ApiBundle\Response\ApiResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ResponseDataHandlerPass
 */
class ResponseDataHandlerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ApiResponseFactoryInterface::class)) {
            return;
        }

        $definition = $container->findDefinition(ApiResponseFactoryInterface::class);
        $taggedServices = $container->findTaggedServiceIds('igoooor.api.response_data_handler');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addDataHandler', [new Reference($id)]);
        }
    }
}
