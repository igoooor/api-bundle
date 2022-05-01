<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:45
 */

namespace Igoooor\ApiBundle\Listener;

use Igoooor\ApiBundle\Problem\ApiProblem;
use Igoooor\ApiBundle\Problem\ApiProblemExceptionInterface;
use Igoooor\ApiBundle\Exception\InvalidApiResponseException;
use Igoooor\ApiBundle\Response\ApiResponseFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ApiExceptionSubscriber
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @var ApiResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * ApiExceptionSubscriber constructor.
     *
     * @param ApiResponseFactoryInterface $responseFactory
     * @param bool                        $debug
     */
    public function __construct(ApiResponseFactoryInterface $responseFactory, bool $debug)
    {
        $this->debug = $debug;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [['onKernelException', 0]],
        ];
    }

    /**
     * @param ExceptionEvent $event
     *
     * @throws InvalidApiResponseException
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        if (0 !== strpos($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $e = $event->getThrowable();
        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($this->debug && $statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            return;
        }

        if ($e instanceof ApiProblemExceptionInterface) {
            $apiProblem = $e->getApiProblem();
        } else {
            $apiProblem = new ApiProblem($statusCode);
            /*
             * If it's an HttpException message (e.g. for 404, 403),
             * we'll say as a rule that the exception message is safe
             * for the client. Otherwise, it could be some sensitive
             * low-level exception, which should *not* be exposed
             */
            if ($e instanceof HttpExceptionInterface && $e instanceof \Exception) {
                $apiProblem->set('detail', $e->getMessage());
            }
        }
        $apiResponse = $this->responseFactory->createErrorResponse($apiProblem->toArray(), $apiProblem->getStatusCode());
        $event->setResponse($apiResponse->getResponse());
    }
}
