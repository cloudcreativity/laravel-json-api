<?php

namespace CloudCreativity\LaravelJsonApi\View;

use CloudCreativity\JsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

class Renderer
{

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Encoder|null
     */
    private $encoder;

    /**
     * @param $expression
     * @return string
     */
    public static function compileEncoder($expression)
    {
        $class = self::class;

        return "<?php app('$class')->encoder($expression); ?>";
    }

    /**
     * @param $expression
     * @return string
     */
    public static function compileData($expression)
    {
        $class = self::class;

        return "<?php echo app('$class')->encodeData($expression); ?>";
    }

    /**
     * Directive constructor.
     *
     * @param JsonApiService $service
     * @param Request $request
     */
    public function __construct(JsonApiService $service, Request $request)
    {
        $this->service = $service;
        $this->request = $request;
    }

    /**
     * @param $apiName
     * @param string|null $host
     * @param int $options
     * @param int $depth
     */
    public function encoder($apiName, $host = null, $options = 0, $depth = 512)
    {
        $host = $host ?: $this->request->getSchemeAndHttpHost();

        $this->encoder = $this->service->encoder($apiName, $host, $options, $depth);
    }

    /**
     * @param $data
     * @param string|array|null $includePaths
     * @param array|null $fieldSets
     * @return string
     */
    public function encodeData($data, $includePaths = null, $fieldSets = null)
    {
        if (!$this->encoder) {
            $this->encoder('default');
        }

        $params = new EncodingParameters(
            $includePaths ? (array) $includePaths : $includePaths,
            $fieldSets
        );

        return $this->encoder->encodeData($data, $params);
    }
}
