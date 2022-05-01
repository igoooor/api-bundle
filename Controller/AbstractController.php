<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:33
 */

namespace Igoooor\ApiBundle\Controller;

use Igoooor\ApiBundle\Problem\ApiFormValidationError;
use Igoooor\ApiBundle\Problem\ApiProblem;
use Igoooor\ApiBundle\Problem\ApiProblemException;
use Igoooor\ApiBundle\Response\ApiResponseFactoryInterface;
use Igoooor\ApiBundle\Exception\InvalidApiResponseException;
use Igoooor\ApiBundle\Response\ApiResponseInterface;
use Igoooor\UserBundle\Model\UserInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class AbstractController
 */
abstract class AbstractController
{
    /**
     * @var ApiResponseFactoryInterface
     */
    private $apiResponseFactory;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * AbstractController constructor.
     *
     * @param ApiResponseFactoryInterface   $apiResponseFactory
     * @param RouterInterface               $router
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param FormFactoryInterface          $formFactory
     */
    public function __construct(ApiResponseFactoryInterface $apiResponseFactory, RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage, FormFactoryInterface $formFactory)
    {
        $this->apiResponseFactory = $apiResponseFactory;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->formFactory = $formFactory;
    }

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
    protected function createResponse($data = null, array $meta = [], array $errors = [], int $status = Response::HTTP_OK, array $headers = []): ApiResponseInterface
    {
        return $this->apiResponseFactory->createResponse($data, $meta, $errors, $status, $headers);
    }

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
    protected function createErrorResponse(array $errors = [], int $status = Response::HTTP_BAD_REQUEST, array $meta = [], array $headers = []): ApiResponseInterface
    {
        return $this->apiResponseFactory->createErrorResponse($errors, $status, $meta, $headers);
    }

    /**
     * @param FormInterface $form
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    protected function createFormValidationErrorResponse(FormInterface $form): ApiResponseInterface
    {
        $apiFormValidation = new ApiFormValidationError($form);

        return $this->apiResponseFactory->createResponse($apiFormValidation);
    }

    /**
     * @param Request $request
     * @param bool    $nullAllowed
     *
     * @return array
     *
     * @throws ApiProblemException
     */
    protected function getRequestData(Request $request, bool $nullAllowed = false): array
    {
        $content = $request->getContent();
        if ('' === $content && $nullAllowed) {
            return [];
        }

        $data = null;
        $requestContent = $request->getContent();
        if (!is_string($requestContent)) {
            throw $this->createInvalidRequestBodyFormatApiProblemException();
        }

        $data = json_decode($requestContent, true);
        if (null === $data) {
            throw $this->createInvalidRequestBodyFormatApiProblemException();
        }

        return $data;
    }

    /**
     * @param string $route         The name of the route
     * @param array  $parameters    An array of parameters
     * @param int    $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     */
    protected function generateUrl(string $route, array $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    /**
     * @param mixed $attributes The attributes
     * @param mixed $object     The object
     *
     * @return bool
     *
     * @throws \LogicException
     */
    protected function isGranted($attributes, $object = null): bool
    {
        return $this->authorizationChecker->isGranted($attributes, $object);
    }

    /**
     * @param string          $message  A message
     * @param \Exception|null $previous The previous exception
     *
     * @return NotFoundHttpException
     */
    protected function createNotFoundException($message = 'Not Found', \Exception $previous = null): NotFoundHttpException
    {
        return new NotFoundHttpException($message, $previous);
    }

    /**
     * @param string          $message  A message
     * @param \Exception|null $previous The previous exception
     *
     * @return AccessDeniedException
     */
    protected function createAccessDeniedException($message = 'Access Denied.', \Exception $previous = null): AccessDeniedException
    {
        return new AccessDeniedException($message, $previous);
    }

    /**
     * @return UserInterface|null
     */
    protected function getUser(): ?UserInterface
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser()) || !$user instanceof UserInterface) {
            return null;
        }

        return $user;
    }


    /**
     * @param string $type
     * @param mixed  $data
     * @param array  $options
     *
     * @return FormInterface
     */
    protected function createForm(string $type, $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * @param Request       $request
     * @param FormInterface $form
     */
    protected function processForm(Request $request, FormInterface $form): void
    {
        $data = $this->getRequestData($request, true);
        if (!array_key_exists($form->getName(), $data)) {
            $data = $request->request->get($form->getName());
            if (null !== $data) {
                $form->handleRequest($request);
            }
            return;
        }

        $clearMissing = $request->getMethod() !== Request::METHOD_PATCH;
        $form->submit($data[$form->getName()], $clearMissing);
    }

    /**
     * @return ApiResponseFactoryInterface
     */
    protected function getApiResponseFactory(): ApiResponseFactoryInterface
    {
        return $this->apiResponseFactory;
    }

    /**
     * @return ApiProblemException
     */
    private function createInvalidRequestBodyFormatApiProblemException(): ApiProblemException
    {
        return new ApiProblemException(
            new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT)
        );
    }
}
