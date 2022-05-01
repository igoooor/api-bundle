<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 06.04.19
 * Time: 19:52
 */

namespace Igoooor\ApiBundle\Tests\Api\Response\DataHandler;

use Igoooor\ApiBundle\Response\ApiResponse;
use Igoooor\ApiBundle\Response\ApiResponseFactory;
use Igoooor\ApiBundle\Response\DataHandler\UserHandler;
use App\Entity\User;
use Igoooor\ApiBundle\Tests\Api\DummySerializer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

/**
 * Class UserHandlerTest
 */
class UserHandlerTest extends TestCase
{
    /**
     * @throws \Igoooor\ApiBundle\Exception\InvalidApiResponseException
     */
    public function testDataHandler(): void
    {
        $serializer = new DummySerializer();
        $apiResponseFactory = new ApiResponseFactory($serializer);
        $additionResultHandler = new UserHandler();
        $apiResponseFactory->addDataHandler($additionResultHandler);

        $user = new User();
        $user->setEmail('email@example.com');
        $user->setRoles([
            'ROLE_TEST',
        ]);
        $response = $apiResponseFactory->createResponse($user);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $expectedSerializedContent = $serializer->serialize([
            'meta' => [],
            'data' => [
                'username' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ], 'json');
        $this->assertEquals($expectedSerializedContent, $response->getContent());
    }
}
