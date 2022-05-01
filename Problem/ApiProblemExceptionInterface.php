<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:55
 */

namespace Igoooor\ApiBundle\Problem;

/**
 * Interface ApiProblemException
 */
interface ApiProblemExceptionInterface
{
    /**
     * @return ApiProblemInterface
     */
    public function getApiProblem(): ApiProblemInterface;
}
