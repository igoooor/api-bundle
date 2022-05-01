<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 06.04.19
 * Time: 20:02
 */

namespace Igoooor\ApiBundle\Tests\Api\Problem;

use Igoooor\ApiBundle\Problem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiProblemTest
 */
class ApiProblemTest extends TestCase
{
    /**
     * @param null|string $type
     * @param string      $expectedType
     * @param string      $expectedTitle
     *
     * @dataProvider typeProvider
     */
    public function testType(?string $type, string $expectedType, string $expectedTitle): void
    {
        $apiProblem = new ApiProblem(Response::HTTP_BAD_REQUEST, $type);
        $this->assertEquals($expectedType, $apiProblem->toArray()['type']);
        $this->assertEquals($expectedTitle, $apiProblem->getTitle());
    }

    /**
     * @return array
     */
    public function typeProvider(): array
    {
        return [
            'null' => [
                null,
                'about:blank',
                Response::$statusTexts[Response::HTTP_BAD_REQUEST],
            ],
            'validatio_error' => [
                'validation_error',
                ApiProblem::TYPE_VALIDATION_ERROR,
                'There was a validation error',
            ],
            'invalid_request_body_format' => [
                'invalid_body_format',
                ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT,
                'Invalid JSON format sent',
            ],
        ];
    }
}
