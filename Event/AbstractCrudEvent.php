<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 18.06.2020
 * Time: 10:00
 */

namespace Igoooor\ApiBundle\Event;

use Igoooor\ApiBundle\Response\ApiResponseFactoryInterface;
use Igoooor\ApiBundle\Response\ApiResponseInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AbstractCrudEvent
 */
abstract class AbstractCrudEvent implements CrudEventInterface
{
    /**
     * @var bool
     */
    private bool $propagationStopped = false;
    /**
     * @var FormInterface|null
     */
    private ?FormInterface $form = null;
    /**
     * @var Request|null
     */
    private ?Request $request = null;

    /**
     * @return FormInterface|null
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    /**
     * @param FormInterface|null $form
     */
    public function setForm(?FormInterface $form): void
    {
        $this->form = $form;
    }

    /**
     * @return Request|null
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @param bool $propagationStopped
     *
     * @return AbstractCrudEvent
     */
    public function setPropagationStopped(bool $propagationStopped): AbstractCrudEvent
    {
        $this->propagationStopped = $propagationStopped;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * @param ApiResponseFactoryInterface $apiResponseFactory
     *
     * @return ApiResponseInterface|null
     */
    public function getResponse(ApiResponseFactoryInterface $apiResponseFactory): ?ApiResponseInterface
    {
        return null;
    }


}
