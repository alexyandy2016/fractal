<?php 

namespace Appkr\Fractal;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use League\Fractal\Manager as Fractal;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class Response 
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var ResponseFactory
     */
    private $response;

    /**
     * Http status code
     *
     * @var integer
     */
    protected $statusCode = 200;

    /**
     * Http response headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * List of meta data to append to the response body
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Response.
     *
     * @param Request         $request
     * @param ResponseFactory $response
     */
    public function __construct(Request $request, ResponseFactory $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Generic response
     *
     * @param mixed $payload
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respond($payload)
    {
        if ($meta = $this->getMeta()) {
            $payload = array_merge(
                $payload,
                ['meta' => $meta]
            );
        }

        return (! $callback = $this->request->input('callback'))
            ? $this->response->json(
                $payload,
                $this->getStatusCode(),
                $this->getHeaders()
            )
            : $this->response->jsonp(
                $callback,
                $payload,
                $this->getStatusCode(),
                $this->getHeaders()
            );
    }

    /**
     * Respond collection of resources
     *
     * @param EloquentCollection $collection
     * @param                    $transformer
     * @param array              $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function withCollection(EloquentCollection $collection, $transformer = null, $headers = [])
    {
        $payload = $this->getCollection($collection, $transformer);

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Create FractalCollection payload
     *
     * @param EloquentCollection $collection
     * @param null               $transformer
     *
     * @return mixed
     */
    public function getCollection(EloquentCollection $collection, $transformer = null)
    {
        $resource = new FractalCollection($collection, $this->getTransformer($transformer));

        if ($meta = $this->getMeta()){
            $resource->setMeta($meta);
            $this->setMeta([]);
        }

        return app(Fractal::class)->createData($resource)->toArray();
    }

    /**
     * Respond single item
     *
     * @param EloquentModel $model
     * @param               $transformer
     * @param array         $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function withItem(EloquentModel $model, $transformer = null, $headers = [])
    {
        $payload = $this->getItem($model, $transformer);

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Create FractalItem payload
     *
     * @param EloquentModel $model
     * @param null          $transformer
     *
     * @return mixed
     */
    public function getItem(EloquentModel $model, $transformer = null)
    {
        $resource = new FractalItem($model, $this->getTransformer($transformer));
        if ($meta = $this->getMeta()){
            $resource->setMeta($meta);
            $this->setMeta([]);
        }

        return app(Fractal::class)->createData($resource)->toArray();
    }

    /**
     * Respond collection of resources with pagination
     *
     * @param LengthAwarePaginator $paginator
     * @param                      $transformer
     * @param array                $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function withPagination(LengthAwarePaginator $paginator, $transformer = null, $headers = [])
    {
        $payload = $this->getPagination($paginator, $transformer);

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Create FractalCollection payload with pagination
     *
     * @param LengthAwarePaginator $paginator
     * @param null                 $transformer
     *
     * @return mixed
     */
    public function getPagination(LengthAwarePaginator $paginator, $transformer = null)
    {
        // Append existing query parameter to pagination link
        // Refer to http://fractal.thephpleague.com/pagination/#including-existing-query-string-values-in-pagination-links
        $queryParams = array_diff_key($_GET, array_flip(['page']));

        foreach($queryParams as $key => $value) {
            $paginator->addQuery($key, $value);
        }

        $collection = $paginator->getCollection();

        $resource = new FractalCollection($collection, $this->getTransformer($transformer));
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        if ($meta = $this->getMeta()){
            $resource->setMeta($meta);
            $this->setMeta([]);
        }

        return app(Fractal::class)->createData($resource)->toArray();
    }

    /**
     * Respond json formatted success message
     *
     * @param       $message
     * @param array $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function success($message, $headers = [])
    {
        $payload = $this->formatPayload($message, config('fractal.successFormat'));

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Respond 201
     *
     * @param mixed $primitive
     * @param array $headers
     *
     * @return $this
     */
    public function created($primitive, $headers = [])
    {
        $payload = null;

        if ($primitive instanceof EloquentModel) {
            // In case an Eloquent Model was passed as the $primitive argument,
            // it just defer the job to respondItem() method.
            // On receiving the job, respondItem() method does its best
            // to transform the given Elequent Model with SimpleArrayTransformer.
            return $this->setStatusCode(201)->respondItem($primitive, null);
        }

        $payload = $this->formatPayload($primitive, config('fractal.successFormat'));

        return $this->setHeaders($headers)->setStatusCode(201)->respond($payload);
    }

    /**
     * Respond 204
     *
     * @param array $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function noContent($headers = [])
    {
        return $this->setHeaders($headers)->setStatusCode(204)->respond(null);
    }

    /**
     * Generic error response
     *
     * @param mixed $message
     * @param array $headers
     *
     * @return $this
     */
    public function error($message = 'Unknown Error', $headers = [])
    {
        if ($message instanceof \Exception) {
            $this->statusCode = $this->translateExceptionCode($message);
            $message            = $message->getMessage();
        }

        $payload = $this->formatPayload($message, config('fractal.errorFormat'));

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Respond 401
     *
     * @param mixed $message
     * @param array $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function unauthorizedError($message = 'Unauthorized', $headers = [])
    {
        return $this->setStatusCode(401)->error($message, $headers);
    }

    /**
     * Respond 403
     *
     * @param mixed $message
     * @param array $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function forbiddenError($message = 'Forbidden', $headers = [])
    {
        return $this->setStatusCode(403)->error($message, $headers);
    }

    /**
     * Respond 404
     *
     * @param mixed $message
     * @param array $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function notFoundError($message = 'Not Found', $headers = [])
    {
        return $this->setStatusCode(404)->error($message, $headers);
    }

    /**
     * Respond 406
     *
     * @param mixed $message
     * @param array $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function notAcceptableError($message = 'Not Acceptable', $headers = [])
    {
        return $this->setStatusCode(406)->error($message, $headers);
    }

    /**
     * Respond 422
     *
     * @param mixed $message
     * @param array $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function unprocessableError($message = 'Unprocessable Entity', $headers = [])
    {
        return $this->setStatusCode(422)->error($message, $headers);
    }

    /**
     * Respond 500
     *
     * @param mixed $message
     * @param array $headers
     *
     * @return \Illuminate\Contracts\Http\Response
     */
    public function internalError($message = 'Internal Server Error', $headers = [])
    {
        return $this->setStatusCode(500)->error($message, headers);
    }

    /**
     * Getter for statusCode property
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Setter for statusCode property
     *
     * @param mixed $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Getter for headers property
     *
     * @return array
     */
    public function getHeaders()
    {
        $defaultHeaders = config('fractal.defaultHeaders');

        return $defaultHeaders
            ? array_merge($defaultHeaders, $this->headers)
            : $this->headers;
    }

    /**
     * Setter for headers property
     *
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        if ($headers) {
            $this->headers = array_merge($this->headers, $headers);
        }

        return $this;
    }

    /**
     * Getter for meta property
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Setter for meta property
     *
     * @param $meta
     *
     * @return $this
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Build response payload array based on configured format
     *
     * @param mixed $message
     * @param array $format
     *
     * @return array
     */
    public function formatPayload($message, array $format)
    {
        $replace = [
            ':message' => $message,
            ':code'    => $this->getStatusCode()
        ];

        array_walk_recursive($format, function (&$value, $key) use ($replace) {
            if (isset($replace[$value])) {
                $value = $replace[$value];
            }
        });

        return $format;
    }

    /**
     * Replace transformer to SimpleArrayTransformer
     * if nothing/null is passed
     *
     * @param $transformer
     *
     * @return \Illuminate\Foundation\Application|mixed
     */
    private function getTransformer($transformer)
    {
        return $transformer ?: app(SimpleArrayTransformer::class);
    }

    /**
     * Translate http status code based on the given exception
     *
     * @param $e
     *
     * @return int
     */
    private function translateExceptionCode($e)
    {
        if (! in_array($e->getCode(), [0, -1, null, ''])) {
            return $e->getCode();
        }

        if (($statusCode = $this->getStatusCode()) != 200) {
            return $statusCode;
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            or $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
        ) {
            return 404;
        }

        return 400;
    }
}