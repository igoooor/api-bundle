<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 09.04.19
 * Time: 10:20
 */

namespace Igoooor\ApiBundle\Tests\Api\Listener;

use Igoooor\ApiBundle\Listener\ApiExceptionSubscriber;
use Igoooor\ApiBundle\Problem\ApiProblem;
use Igoooor\ApiBundle\Problem\ApiProblemException;
use Igoooor\ApiBundle\Response\ApiResponse;
use Igoooor\ApiBundle\Response\ApiResponseFactory;
use App\Kernel;
use Igoooor\ApiBundle\Tests\Api\DummySerializer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ApiExceptionSubscriberTest
 */
class ApiExceptionSubscriberTest extends TestCase
{
    /**
     * Test subscribed events
     */
    public function testSubscribedEvents()
    {
        $subscribedEvents = ApiExceptionSubscriber::getSubscribedEvents();
        $excpectedSubscribedEvents = [
            KernelEvents::EXCEPTION => [['onKernelException', 0]],
        ];
        $this->assertEquals($excpectedSubscribedEvents, $subscribedEvents);
    }

    /**
     * @dataProvider onKernelExceptionProvider
     *
     * @param \Exception  $exception
     * @param bool        $debug
     *
     * @param null|string $expectedInstanceOf
     * @param int         $expectedStatusCode
     * @param string      $expectedType
     * @param string      $expectedDetail
     *
     * @throws \Igoooor\ApiBundle\Exception\InvalidApiResponseException
     */
    public function testOnKernelException(\Exception $exception, bool $debug, ?string $expectedInstanceOf, int $expectedStatusCode, string $expectedType, ?string $expectedDetail): void
    {
        $serializer = new DummySerializer();
        $apiResponseFactory = new ApiResponseFactory($serializer);

        $apiExceptionSubscriber = new ApiExceptionSubscriber($apiResponseFactory, $debug);

        $event = new ExceptionEvent(
            $this->createKernelMock(),
            $this->createRequestMock(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $apiExceptionSubscriber->onKernelException($event);
        $response = $event->getResponse();
        if (null !== $expectedInstanceOf) {
            $this->assertInstanceOf($expectedInstanceOf, $response);

            $errors = unserialize($response->getContent())['errors'];
            $this->assertEquals($expectedStatusCode, $errors['status']);
            $this->assertEquals($expectedType, $errors['type']);
            if (null !== $expectedDetail) {
                $this->assertEquals($expectedDetail, $errors['detail']);
            } else {
                $this->assertArrayNotHasKey('detail', $errors);
            }
        } else {
            $this->assertNull($response);
        }
    }

    /**
     * @return array
     */
    public function onKernelExceptionProvider(): array
    {
        $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR);

        return [
            'no_debug_api_problem_exception' => [
                new ApiProblemException($apiProblem),
                false,
                ApiResponse::class,
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_VALIDATION_ERROR,
                null,
            ],
            'no_debug_http_exception' => [
                new HttpException(Response::HTTP_UNAUTHORIZED, 'custom error message'),
                false,
                ApiResponse::class,
                Response::HTTP_UNAUTHORIZED,
                'about:blank',
                'custom error message',
            ],
            'no_debug_exception' => [
                new \Exception('custom error message', Response::HTTP_PAYMENT_REQUIRED),
                false,
                ApiResponse::class,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'about:blank',
                null,
            ],
            'debug_api_problem_exception' => [
                new ApiProblemException($apiProblem),
                true,
                ApiResponse::class,
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_VALIDATION_ERROR,
                null,
            ],
            'debug_http_exception' => [
                new HttpException(Response::HTTP_UNAUTHORIZED, 'custom error message'),
                true,
                ApiResponse::class,
                Response::HTTP_UNAUTHORIZED,
                'about:blank',
                'custom error message',
            ],
            'debug_exception' => [
                new \Exception('custom error message', Response::HTTP_PAYMENT_REQUIRED),
                true,
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'about:blank',
                null,
            ],
        ];
    }

    /**
     * @return Kernel|MockObject
     */
    private function createKernelMock(): MockObject
    {
        $kernelMock = $this->getMockBuilder(Kernel::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $kernelMock;
    }

    /**
     * @return Request|MockObject
     */
    private function createRequestMock(): MockObject
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPathInfo',
            ])
            ->getMock();

        $requestMock
            ->method('getPathInfo')
            ->willReturn('/api/fake');

        return $requestMock;
    }
}
