<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:55
 */

namespace Igoooor\ApiBundle\Problem;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ApiProblemException
 */
class ApiProblemException extends HttpException implements ApiProblemExceptionInterface
{
    /**
     * @var ApiProblemInterface
     */
    private $apiProblem;

    /**
     * ApiProblemException constructor.
     *
     * @param ApiProblemInterface $apiProblem
     * @param \Exception|null     $previous
     * @param array               $headers
     * @param int                 $code
     */
    public function __construct(ApiProblemInterface $apiProblem, ?\Exception $previous = null, array $headers = [], int $code = 0)
    {
        $this->apiProblem = $apiProblem;

        $statusCode = $apiProblem->getStatusCode();
        $message = $apiProblem->getTitle();
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    /**
     * @return ApiProblemInterface
     */
    public function getApiProblem(): ApiProblemInterface
    {
        return $this->apiProblem;
    }
}
