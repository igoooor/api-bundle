<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 06.04.19
 * Time: 19:58
 */

namespace Igoooor\ApiBundle\Tests\Api\Problem;

use Igoooor\ApiBundle\Problem\ApiProblem;
use Igoooor\ApiBundle\Problem\ApiProblemException;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiProblemExceptionTest
 */
class ApiProblemExceptionTest extends TestCase
{
    /**
     *
     */
    public function testException(): void
    {
        $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR);
        $apiProblemException = new ApiProblemException($apiProblem);

        $this->assertEquals($apiProblem, $apiProblemException->getApiProblem());
        $this->assertEquals($apiProblem->getStatusCode(), $apiProblemException->getStatusCode());
        $this->assertEquals($apiProblem->getTitle(), $apiProblemException->getMessage());
    }
}
