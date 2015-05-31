<?php namespace Appkr\Fractal;

use League\Fractal\Manager as Fractal;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait ApiHelper {

    /**
     * Default http response code
     *
     * @var integer
     */
    protected $statusCode = 200;

    /**
     * Http response headers
     *
     * @var array
     */
    protected $customHeaders = [];

    /**
     * Generic response
     *
     * @param mixed $payload
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respond($payload) {
        return response()->json(
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
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondCollection(EloquentCollection $collection, $transformer = null, $headers = []) {
        $resource = new FractalCollection($collection, $this->getTransformer($transformer));
        $payload  = app(Fractal::class)->createData($resource)->toArray();

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Respond single item
     *
     * @param EloquentModel $model
     * @param               $transformer
     * @param array         $headers
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondItem(EloquentModel $model, $transformer = null, $headers = []) {
        $resource = new FractalItem($model, $this->getTransformer($transformer));
        $payload  = app(Fractal::class)->createData($resource)->toArray();

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Respond collection of resources with pagination
     *
     * @param LengthAwarePaginator $paginator
     * @param                      $transformer
     * @param array                $headers
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondWithPagination(LengthAwarePaginator $paginator, $transformer = null, $headers = []) {
        $collection = $paginator->getCollection();

        $resource = new FractalCollection($collection, $this->getTransformer($transformer));
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $payload = app(Fractal::class)->createData($resource)->toArray();

        return $this->setHeaders($headers)->respond($payload);
    }

    public function respondSuccess($message, $headers = []) {
        $payload = $this->formatPayload($message, config('fractal.successFormat'));

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Respond 201
     *
     * @param mixed $primitive
     * @param array $headers
     * @return $this
     */
    public function respondCreated($primitive, $headers = []) {
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
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondNoContent($headers = []) {
        return $this->setHeaders($headers)->setStatusCode(204)->respond(null);
    }

    /**
     * Generic error response
     *
     * @param mixed $message
     * @param array $headers
     * @return $this
     */
    public function respondWithError($message = 'Unknown Error', $headers = []) {
        if ($message instanceof \Exception) {
            $this->statusCode = $this->translateExceptionCode($message);
            $message = $message->getMessage();
        }

        $payload = $this->formatPayload($message, config('fractal.errorFormat'));

        return $this->setHeaders($headers)->respond($payload);
    }

    /**
     * Respond 401
     *
     * @param mixed $message
     * @param array $headers
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondUnauthorized($message = 'Unauthorized', $headers = []) {
        return $this->setHeaders($headers)->setStatusCode(401)->respondWithError($message);
    }

    /**
     * Respond 403
     *
     * @param mixed $message
     * @param array $headers
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondForbidden($message = 'Forbidden', $headers = []) {
        return $this->setHeaders($headers)->setStatusCode(403)->respondWithError($message);
    }

    /**
     * Respond 404
     *
     * @param mixed $message
     * @param array $headers
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondNotFound($message = 'Not Found', $headers = []) {
        return $this->setHeaders($headers)->setStatusCode(404)->respondWithError($message);
    }

    /**
     * Respond 406
     *
     * @param mixed $message
     * @param array $headers
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondNotAcceptable($message = 'Not Acceptable', $headers = []) {
        return $this->setHeaders($headers)->setStatusCode(406)->respondWithError($message);
    }

    /**
     * Respond 422
     *
     * @param mixed $message
     * @param array $headers
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondUnprocessableError($message = 'Unprocessable Entity', $headers = []) {
        return $this->setHeaders($headers)->setStatusCode(422)->respondWithError($message);
    }

    /**
     * Respond 500
     *
     * @param mixed $message
     * @param array $headers
     * @return \Illuminate\Contracts\Http\Response
     */
    public function respondInternalError($message = 'Internal Server Error', $headers = []) {
        return $this->setHeaders($headers)->setStatusCode(500)->respondWithError($message);
    }

    /**
     * Getter for statusCode
     *
     * @return mixed
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * Setter for statusCode
     *
     * @param mixed $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Getter for headers
     *
     * @return array
     */
    public function getHeaders() {
        $defaultHeaders = config('fractal.defaultHeaders');

        return $defaultHeaders
            ? array_merge($defaultHeaders, $this->customHeaders)
            : $this->customHeaders;
    }

    /**
     * Setter for headers
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers) {
        if ($headers) {
            $this->customHeaders = array_merge($this->customHeaders, $headers);
        }

        return $this;
    }

    /**
     * Build response payload array based on configured format
     *
     * @param mixed $message
     * @param array $format
     * @return array
     */
    public function formatPayload($message, array $format) {
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
     * @return \Illuminate\Foundation\Application|mixed
     */
    private function getTransformer($transformer) {
        return $transformer ?: app(SimpleArrayTransformer::class);
    }

    /**
     * Translate http status code based on
     *
     * @param $e
     * @return int
     */
    private function translateExceptionCode($e) {
        if ($e->getCode() !== -1) {
            return $e->getCode();
        }

        if (($statusCode = $this->getStatusCode()) != 200) {
            return $statusCode;
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            or $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return 404;
        }

        return 400;
    }

}