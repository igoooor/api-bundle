<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 06.04.19
 * Time: 07:47
 */

namespace Igoooor\ApiBundle\Tests\Api;

use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class DummySerializer
 */
class DummySerializer implements SerializerInterface
{
    /**
     * @param mixed  $data
     * @param string $type
     * @param string $format
     * @param array  $context
     *
     * @return void
     */
    public function deserialize($data, $type, $format, array $context = []): void
    {
        throw new \RuntimeException('Should not be called');
    }

    /**
     * @param mixed  $data
     * @param string $format
     * @param array  $context
     *
     * @return string
     */
    public function serialize($data, $format, array $context = []): string
    {
        return serialize($data);
    }
}
