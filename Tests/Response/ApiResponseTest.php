<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 09.04.19
 * Time: 11:10
 */

namespace Igoooor\ApiBundle\Tests\Api\Response;

use Igoooor\ApiBundle\Response\ApiResponse;
use Igoooor\ApiBundle\Tests\Api\DummySerializer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResponseTest
 */
class ApiResponseTest extends TestCase
{
    /**
     * @throws \Igoooor\ApiBundle\Exception\InvalidApiResponseException
     */
    public function testApiReponse(): void
    {
        $serializer = new DummySerializer();
        $data = [
            'some' => 'data',
        ];
        $meta = [
            'some' => 'meta',
            'some_more' => 'meta2',
        ];
        $errors = [];
        $status = Response::HTTP_OK;
        $headers = [
            'X-TEST' => '42.0',
        ];

        $apiResponse = new ApiResponse(
            $serializer,
            $data,
            $meta,
            $errors,
            $status,
            $headers
        );

        $expectedContent = $serializer->serialize([
            'meta' => $meta,
            'data' => $data,
        ], 'json');
        $this->assertEquals($expectedContent, $apiResponse->getContent());
        $this->assertFalse($apiResponse->hasErrors());
        $this->assertEquals($meta, $apiResponse->getMeta());
        $this->assertEquals($headers['X-TEST'], $apiResponse->headers->get('x-test'));
        $this->assertEquals('application/vnd.api+json', $apiResponse->headers->get('content-type'));
        $this->assertArrayHasKey('access-control-allow-headers', $apiResponse->headers->all());
    }

    /**
     * @throws \Igoooor\ApiBundle\Exception\InvalidApiResponseException
     */
    public function testApiReponseWithErrors(): void
    {
        $serializer = new DummySerializer();
        $data = [];
        $meta = [
            'some' => 'meta',
            'some_more' => 'meta2',
        ];
        $errors = [
            'any' => 'error',
        ];
        $status = Response::HTTP_OK;
        $headers = [
            'X-TEST' => '42.0',
        ];

        $apiResponse = new ApiResponse(
            $serializer,
            $data,
            $meta,
            $errors,
            $status,
            $headers
        );

        $expectedContent = $serializer->serialize([
            'meta' => $meta,
            'errors' => $errors,
        ], 'json');
        $this->assertEquals($expectedContent, $apiResponse->getContent());
        $this->assertTrue($apiResponse->hasErrors());
        $this->assertEquals($headers['X-TEST'], $apiResponse->headers->get('x-test'));
        $this->assertEquals('application/problem+json', $apiResponse->headers->get('content-type'));
        $this->assertArrayHasKey('access-control-allow-headers', $apiResponse->headers->all());
    }
}
