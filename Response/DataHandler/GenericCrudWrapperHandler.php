<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 18.06.2020
 * Time: 10:08
 */

namespace Igoooor\ApiBundle\Response\DataHandler;

use Igoooor\ApiBundle\Crud\ListWrapper\GenericCrudWrapper;
use Igoooor\ApiBundle\Response\ApiResponseInterface;
use Igoooor\ApiBundle\Response\DataHandler\DataHandlerInterface;

/**
 * Class GenericCrudWrapperHandler
 */
class GenericCrudWrapperHandler implements DataHandlerInterface
{
    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function support($data): bool
    {
        return $data instanceof GenericCrudWrapper;
    }

    /**
     * @param ApiResponseInterface $response
     * @param GenericCrudWrapper   $data
     *
     * @throws \Exception
     */
    public function handle(ApiResponseInterface $response, $data): void
    {
        $response->setData($data->getData());
    }
}
