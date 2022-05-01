<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 09.04.19
 * Time: 10:12
 */

namespace Igoooor\ApiBundle\Tests\Api\Exception;

use Igoooor\ApiBundle\Exception\InvalidApiResponseException;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

/**
 * Class InvalidApiResponseExceptionTest
 */
class InvalidApiResponseExceptionTest extends TestCase
{
    /**
     * Test well formed exception
     */
    public function testException(): void
    {
        $exception = new InvalidApiResponseException();
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('Something went wrong with the response content', $exception->getMessage());
    }
}
