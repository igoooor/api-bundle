<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 05.04.19
 * Time: 21:51
 */

namespace Igoooor\ApiBundle\Problem;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiProblem
 *
 * @link https://tools.ietf.org/html/rfc7807
 */
class ApiProblem implements ApiProblemInterface
{
    public const TYPE_VALIDATION_ERROR = 'validation_error';
    public const TYPE_INVALID_REQUEST_BODY_FORMAT = 'invalid_body_format';

    private const TYPE_TITLES = [
        self::TYPE_VALIDATION_ERROR            => 'There was a validation error',
        self::TYPE_INVALID_REQUEST_BODY_FORMAT => 'Invalid JSON format sent',
    ];

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $title;

    /**
     * @var array
     */
    private $extraData = [];

    /**
     * ApiProblem constructor.
     *
     * @param int         $statusCode
     * @param string|null $type
     */
    public function __construct(int $statusCode, ?string $type = null)
    {
        $this->statusCode = $statusCode;
        if (null === $type) {
            $type = 'about:blank';
            $this->title = isset(Response::$statusTexts[$statusCode])
                ? Response::$statusTexts[$statusCode]
                : 'Unknown status code :(';
        } else {
            if (!isset(self::TYPE_TITLES[$type])) {
                throw new \InvalidArgumentException(sprintf('No title for type %s', $type));
            }
            $this->title = self::TYPE_TITLES[$type];
        }

        $this->type = $type;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(
            $this->extraData,
            [
                'status' => $this->statusCode,
                'type'   => $this->type,
                'title'  => $this->title,
            ]
        );
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function set(string $name, $value): void
    {
        $this->extraData[$name] = $value;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
