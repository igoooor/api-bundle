<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:10
 */

namespace Igoooor\ApiBundle\Response\DataHandler;

use Igoooor\ApiBundle\Response\ApiResponseInterface;

/**
 * Interface DataHandlerInterface
 */
interface DataHandlerInterface
{
    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function support($data): bool;

    /**
     * @param ApiResponseInterface $response
     * @param mixed                $data
     */
    public function handle(ApiResponseInterface $response, $data): void;
}
