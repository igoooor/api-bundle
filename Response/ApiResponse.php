<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 20:46
 */

namespace Igoooor\ApiBundle\Response;

use Igoooor\ApiBundle\Exception\InvalidApiResponseException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * Class ApiResponse
 *
 * @link https://jsonapi.org/
 */
class ApiResponse extends Response implements ApiResponseInterface
{
    private const RESPONSE_DEFAULT_FORMAT = 'json';

    /**
     * @var array
     */
    private $meta = [];

    /**
     * @var array
     *
     * @link https://tools.ietf.org/html/rfc7807
     */
    private $errors = [];

    /**
     * @var array
     */
    private $links = [];

    /**
     * @var array
     */
    private $included = [];

    /**
     * @var mixed
     *
     * @link http://jsonapi.org/format/#document-resource-objects
     */
    private $data;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SerializationContext
     */
    private $context;

    /**
     * @var array
     */
    private $accessControlAllowedHeaders = [];

    /**
     * @param SerializerInterface $serializer
     * @param mixed               $data
     * @param array               $meta
     * @param array               $errors
     * @param int                 $status
     * @param array               $headers
     *
     * @throws InvalidApiResponseException
     */
    public function __construct(SerializerInterface $serializer, $data = null, array $meta = [], array $errors = [], int $status = Response::HTTP_OK, array $headers = [])
    {
        parent::__construct('', $status, $headers);

        $this->addDefaultAccessControlAllowedHeaders($headers);
        $this->serializer = $serializer;
        $this->context = SerializationContext::create();
        $this->setSerializeNull(true);
        $this->setMeta($meta);
        $this->setData($data);
        $this->setErrors($errors);
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * @param mixed $data
     *
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    public function setData($data = []): ApiResponseInterface
    {
        $this->data = $data;

        return $this->update();
    }

    /**
     * @param array $meta
     *
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    public function setMeta(array $meta): ApiResponseInterface
    {
        if (array_key_exists('serializerGroups', $meta)) {
            $this->setSerializerGroups($meta['serializerGroups']);
            unset($meta['serializerGroups']);
        }
        if (array_key_exists('serializeNull', $meta)) {
            $this->setSerializeNull($meta['serializeNull']);
            unset($meta['serializeNull']);
        }
        $this->meta = $meta;

        return $this->update();
    }

    /**
     * @param array $errors
     *
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    public function setErrors(array $errors): ApiResponseInterface
    {
        $this->errors = $errors;

        return $this->update();
    }

    /**
     * @param string|array $error
     *
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    public function addError($error): ApiResponseInterface
    {
        $this->errors[] = $error;

        return $this->update();
    }

    /**
     * @param array $links
     *
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    public function setLinks(array $links): ApiResponseInterface
    {
        $this->links = $links;

        return $this->update();
    }

    /**
     * @param array $included
     *
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    public function setIncluded(array $included): ApiResponseInterface
    {
        $this->included = $included;

        return $this->update();
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array $headers
     */
    public function addAccessControlAllowedHeaders(array $headers): void
    {
        $headers = array_unique(array_filter(array_merge($this->accessControlAllowedHeaders, $headers)));
        $this->setAccessControlAllowedHeaders($headers);
    }

    /**
     * @param array $headers
     */
    public function setAccessControlAllowedHeaders(array $headers): void
    {
        $this->headers->set('Access-Control-Allow-Headers', implode(', ', $headers));
        $this->accessControlAllowedHeaders = $headers;
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this;
    }

    /**
     * Add default Access-Control-Allow-Headers
     *
     * @param array $headers
     */
    private function addDefaultAccessControlAllowedHeaders(array $headers): void
    {
        $defaultHeaders = [];
        if (isset($headers['Access-Control-Allow-Headers'])) {
            $defaultHeaders = array_merge($defaultHeaders, explode(', ', $headers['Access-Control-Allow-Headers']));
        }
        $this->addAccessControlAllowedHeaders($defaultHeaders);
    }

    /**
     * @return ApiResponse
     *
     * @throws InvalidApiResponseException
     */
    private function update(): ApiResponseInterface
    {
        if ($this->hasErrors()) {
            $this->headers->set('Content-Type', 'application/problem+json');
        } else {
            $this->headers->set('Content-Type', 'application/vnd.api+json');
        }
        $content = $this->prepareContent();
        try {
            $encodedContent = $this->encodeContent($content);

            return $this->setContent($encodedContent);
        } catch (NotEncodableValueException $e) {
            throw new InvalidApiResponseException('Something went wrong with the response content');
        }
    }

    /**
     * @param array $content
     *
     * @return string
     */
    private function encodeContent(array $content): string
    {
        return $this->serializer->serialize($content, self::RESPONSE_DEFAULT_FORMAT, clone $this->context);
    }

    /**
     * @return array
     */
    private function prepareContent(): array
    {
        $content = [
            'meta' => $this->meta,
        ];
        if (!empty($this->errors)) {
            $content['errors'] = $this->errors;
        } else {
            $content['data'] = $this->data;
        }
        if (!empty($this->links)) {
            $content['links'] = $this->links;
        }
        if (!empty($this->included)) {
            $content['included'] = $this->included;
        }

        return $content;
    }

    /**
     * @param array|string $serializerGroups
     */
    private function setSerializerGroups($serializerGroups): void
    {
        $this->context->setGroups($serializerGroups);
    }

    /**
     * @param bool $serializeNull
     */
    private function setSerializeNull(bool $serializeNull): void
    {
        $this->context->setSerializeNull($serializeNull);
    }
}
