<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:06
 */

namespace Igoooor\ApiBundle\Response;

use Igoooor\ApiBundle\Response\DataHandler\DataHandlerInterface;
use Igoooor\ApiBundle\Exception\InvalidApiResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ApiResponseFactoryInterface
 */
interface ApiResponseFactoryInterface
{
    /**
     * @param mixed $data
     * @param array $meta
     * @param array $errors
     * @param int   $status
     * @param array $headers
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    public function createResponse($data = null, array $meta = [], array $errors = [], int $status = Response::HTTP_OK, array $headers = []): ApiResponseInterface;

    /**
     * @param array $errors
     * @param int   $status
     * @param array $meta
     * @param array $headers
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    public function createErrorResponse(array $errors = [], int $status = Response::HTTP_BAD_REQUEST, array $meta = [], array $headers = []): ApiResponseInterface;

    /**
     * @param DataHandlerInterface $dataHandler
     */
    public function addDataHandler(DataHandlerInterface $dataHandler): void;
}
