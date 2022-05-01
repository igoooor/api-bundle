<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 20:46
 */

namespace Igoooor\ApiBundle\Response;

use Igoooor\ApiBundle\Exception\InvalidApiResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ApiResponseInterface
 *
 * @link https://jsonapi.org/
 */
interface ApiResponseInterface
{
    /**
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * @param mixed $data
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    public function setData($data = []): ApiResponseInterface;

    /**
     * @param array $meta
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    public function setMeta(array $meta): ApiResponseInterface;

    /**
     * @param array $errors
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    public function setErrors(array $errors): ApiResponseInterface;

    /**
     * @param string|array $error
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    public function addError($error): ApiResponseInterface;

    /**
     * @param array $links
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    public function setLinks(array $links): ApiResponseInterface;

    /**
     * @param array $included
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     */
    public function setIncluded(array $included): ApiResponseInterface;

    /**
     * @return array
     */
    public function getMeta(): array;

    /**
     * @param array $headers
     */
    public function addAccessControlAllowedHeaders(array $headers): void;

    /**
     * @param array $headers
     */
    public function setAccessControlAllowedHeaders(array $headers): void;

    /**
     * @return Response
     */
    public function getResponse(): Response;
}
