<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 17.06.2020
 * Time: 22:07
 */

namespace Igoooor\ApiBundle\Crud\ListWrapper;

/**
 * Class GenericCrudWrapper
 */
class GenericCrudWrapper implements CrudWrapperInterface
{
    /**
     * @var array
     */
    private array $data;

    /**
     * GenericCrudWrapper constructor.
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
