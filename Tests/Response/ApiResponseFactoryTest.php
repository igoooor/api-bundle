<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 06.04.19
 * Time: 07:14
 */

namespace Igoooor\ApiBundle\Tests\Api\Response;

use Igoooor\ApiBundle\Response\ApiResponse;
use Igoooor\ApiBundle\Response\ApiResponseFactory;
use Igoooor\ApiBundle\Tests\Api\DummySerializer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResponseFactoryTest
 */
class ApiResponseFactoryTest extends TestCase
{
    /**
     * @param mixed $data
     * @param array $meta
     * @param array $errors
     * @param int   $status
     * @param array $headers
     *
     * @dataProvider createResponseProvider
     *
     * @throws \Igoooor\ApiBundle\Exception\InvalidApiResponseException
     */
    public function testCreateResponse($data, array $meta = [], array $errors = [], int $status = Response::HTTP_OK, array $headers = []): void
    {
        $serializer = new DummySerializer();
        $apiResponseFactory = new ApiResponseFactory($serializer);
        $response = $apiResponseFactory->createResponse($data, $meta, $errors, $status, $headers);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $expectedSerializedContent = $serializer->serialize([
            'meta' => $meta,
            'data' => $data,
        ], 'json');
        $this->assertEquals($expectedSerializedContent, $response->getContent());
    }

    /**
     * @return array
     */
    public function createResponseProvider(): array
    {
        return [
            'only_data_string' => [
                'some data',
            ],
            'only_data_array' => [
                [
                    'key' => 'value',
                ],
            ],
            'full_case' => [
                [
                    'dataKey' => 'value',
                ],
                [
                    'count' => 12,
                ],
                [],
                Response::HTTP_CREATED,
                [
                    'Authorization' => 'Bearer xad',
                ],
            ],
        ];
    }
    /**
     * @param mixed $errors
     * @param int   $status
     * @param array $meta
     * @param array $headers
     *
     * @dataProvider createErrorResponseProvider
     *
     * @throws \Igoooor\ApiBundle\Exception\InvalidApiResponseException
     */
    public function testCreateErrorResponse($errors = [], int $status = Response::HTTP_BAD_REQUEST, array $meta = [], array $headers = []): void
    {
        $serializer = new DummySerializer();
        $apiResponseFactory = new ApiResponseFactory($serializer);
        $response = $apiResponseFactory->createErrorResponse($errors, $status, $meta, $headers);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $expectedSerializedContent = $serializer->serialize([
            'meta' => $meta,
            'errors' => $errors,
        ], 'json');
        $this->assertEquals($expectedSerializedContent, $response->getContent());
    }

    /**
     * @return array
     */
    public function createErrorResponseProvider(): array
    {
        return [
            'only_error_array' => [
                [
                    'key' => 'value',
                ],
            ],
            'full_case' => [
                [
                    'errorKey' => 'value',
                ],
                Response::HTTP_CREATED,
                [
                    'count' => 12,
                ],
                [
                    'Authorization' => 'Bearer xad',
                ],
            ],
        ];
    }
}
