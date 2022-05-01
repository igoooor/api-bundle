<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 20:48
 */

namespace Igoooor\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class InvalidApiResponseException
 */
final class InvalidApiResponseException extends \Exception
{
    /**
     * InvalidApiResponseException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = 'Something went wrong with the response content', int $code = Response::HTTP_INTERNAL_SERVER_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
