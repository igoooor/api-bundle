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
use Igoooor\ApiBundle\Response\ApiResponseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractController extends SymfonyAbstractController
{
    private ApiResponseFactoryInterface $apiResponseFactory;

    #[Required]
    public function setApiResponseFactory(ApiResponseFactoryInterface $apiResponseFactory): void
    {
        $this->apiResponseFactory = $apiResponseFactory;
    }

    protected function createResponse(mixed $data = null, array $meta = [], array $errors = [], int $status = Response::HTTP_OK, array $headers = []): ApiResponseInterface
    {
        return $this->apiResponseFactory->createResponse($data, $meta, $errors, $status, $headers);
    }

    protected function createErrorResponse(array $errors = [], int $status = Response::HTTP_BAD_REQUEST, array $meta = [], array $headers = []): ApiResponseInterface
    {
        return $this->apiResponseFactory->createErrorResponse($errors, $status, $meta, $headers);
    }

    protected function createFormValidationErrorResponse(FormInterface $form): ApiResponseInterface
    {
        $apiFormValidation = new ApiFormValidationError($form);

        return $this->apiResponseFactory->createResponse($apiFormValidation);
    }

    protected function getRequestData(Request $request, bool $nullAllowed = false): array
    {
        $content = $request->getContent();
        if ('' === $content && $nullAllowed) {
            return [];
        }

        try {
            return $request->toArray();
        } catch (JsonException $e) {
            throw $this->createInvalidRequestBodyFormatApiProblemException();
        }
    }

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

    protected function getApiResponseFactory(): ApiResponseFactoryInterface
    {
        return $this->apiResponseFactory;
    }

    private function createInvalidRequestBodyFormatApiProblemException(): ApiProblemException
    {
        return new ApiProblemException(
            new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT)
        );
    }
}
