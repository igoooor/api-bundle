<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 20.05.2020
 * Time: 10:35
 */

namespace Igoooor\ApiBundle\Problem;

use Symfony\Component\Form\FormInterface;

/**
 * Class ApiFormValidationError
 */
class ApiFormValidationError
{
    /**
     * @var FormInterface
     */
    private $form;

    /**
     * ApiFormValidation constructor.
     *
     * @param FormInterface $form
     */
    public function __construct(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }
}
