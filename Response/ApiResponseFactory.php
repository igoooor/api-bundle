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
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResponseFactory
 */
class ApiResponseFactory implements ApiResponseFactoryInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DataHandlerInterface[]
     */
    private $dataHandlers = [];

    /**
     * ApiResponseFactory constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed $data
     * @param array $meta
     * @param array $errors
     * @param int   $status
     * @param array $headers
     *
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    public function createResponse($data = null, array $meta = [], array $errors = [], int $status = Response::HTTP_OK, array $headers = []): ApiResponseInterface
    {
        $response = new ApiResponse($this->serializer, $data, $meta, $errors, $status, $headers);
        $this->handleData($response, $data);

        return $response;
    }

    /**
     * @param array $errors
     * @param int   $status
     * @param array $meta
     * @param array $headers
     *
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    public function createErrorResponse(array $errors = [], int $status = Response::HTTP_BAD_REQUEST, array $meta = [], array $headers = []): ApiResponseInterface
    {
        return new ApiResponse($this->serializer, null, $meta, $errors, $status, $headers);
    }

    /**
     * @param DataHandlerInterface $dataHandler
     */
    public function addDataHandler(DataHandlerInterface $dataHandler): void
    {
        $this->dataHandlers[] = $dataHandler;
    }

    /**
     * @param ApiResponse $response
     * @param mixed       $data
     */
    private function handleData(ApiResponse $response, $data): void
    {
        foreach ($this->dataHandlers as $dataHandler) {
            if ($dataHandler->support($data)) {
                $dataHandler->handle($response, $data);
            }
        }
    }
}
