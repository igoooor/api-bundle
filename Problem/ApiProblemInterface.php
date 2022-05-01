<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:51
 */

namespace Igoooor\ApiBundle\Problem;

/**
 * Interface ApiProblemInterface
 */
interface ApiProblemInterface
{

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function set(string $name, $value): void;

    /**
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * @return string
     */
    public function getTitle(): string;
}
