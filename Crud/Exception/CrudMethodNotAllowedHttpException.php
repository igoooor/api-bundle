<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 17.06.2020
 * Time: 23:32
 */

namespace Igoooor\ApiBundle\Crud\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class CrudMethodNotAllowedHttpException
 */
class CrudMethodNotAllowedHttpException extends HttpException
{
    /**
     * CrudMethodNotAllowedHttpException constructor.
     *
     * @param string          $message
     * @param \Throwable|null $previous
     * @param array           $headers
     * @param int|null        $code
     */
    public function __construct(string $message = 'null', \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        parent::__construct(405, $message, $previous, $headers, $code);
    }
}
