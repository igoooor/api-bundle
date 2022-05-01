<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 18.06.2020
 * Time: 09:57
 */

namespace Igoooor\ApiBundle\Event;

use Igoooor\ApiBundle\Response\ApiResponseFactoryInterface;
use Igoooor\ApiBundle\Response\ApiResponseInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface CrudEventInterface
 */
interface CrudEventInterface
{
    /**
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form): void;

    /**
     * @return FormInterface|null
     */
    public function getForm(): ?FormInterface;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void;

    /**
     * @return Request|null
     */
    public function getRequest(): ?Request;

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool;

    /**
     * @param ApiResponseFactoryInterface $apiResponseFactory
     *
     * @return ApiResponseInterface
     */
    public function getResponse(ApiResponseFactoryInterface $apiResponseFactory): ?ApiResponseInterface;
}
