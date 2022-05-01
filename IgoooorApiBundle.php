<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 06.10.2019
 * Time: 20:23
 */

namespace Igoooor\ApiBundle;

use Igoooor\ApiBundle\DependencyInjection\Compiler\ResponseDataHandlerPass;
use Igoooor\ApiBundle\Response\DataHandler\DataHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class IgoooorApiBundle
 */
class IgoooorApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(DataHandlerInterface::class)->addTag('igoooor.api.response_data_handler');
        $container->addCompilerPass(new ResponseDataHandlerPass());
    }
}
