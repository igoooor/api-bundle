<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 20.05.2020
 * Time: 10:33
 */

namespace Igoooor\ApiBundle\Response\DataHandler;

use AppBundle\Response\VueResponse;
use Igoooor\ApiBundle\Problem\ApiFormValidationError;
use Igoooor\ApiBundle\Response\ApiResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiFormValidationErrorHandler
 */
class ApiFormValidationErrorHandler implements DataHandlerInterface
{
    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function support($data): bool
    {
        return $data instanceof ApiFormValidationError;
    }

    /**
     * @param VueResponse            $response
     * @param ApiFormValidationError $data
     *
     * @throws \Exception
     */
    public function handle(ApiResponseInterface $response, $data): void
    {
        $response->setData($data->getForm());
        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    }
}
