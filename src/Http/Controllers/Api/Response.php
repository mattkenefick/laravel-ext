<?php

namespace PolymerMallard\Http\Controllers\Api;

use PolymerMallard\Exception\ApiException;
use Cache;
use Carbon\Carbon;
use DB;
use League\Fractal;
use League\Fractal\Serializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as Codes;
use Input;
use Request;
use DateTime;


abstract class Response extends BaseController
{
    /**
     * Resource key attached to JSON output
     * @var $resourceKey
     */
    protected $resourceKey = null;

    /**
     * Additional ModelInterface embeds to include
     * @var $includes
     */
    protected $includes = null;

    /**
     * Additional Metadata to include
     */
    protected $responseMeta = null;


    // Actionable
    // ----------------------------------------------------------------------

    /**
     * model
     *
     * Returns an API Response containing transformed model data.
     *
     * @param Model Singular model to respond with
     * @param ModelInterface Transformer that selects visible data
     *
     * @return \Response
     */
    public function model($model, $transformer, $key = null)
    {
        $key = $key ?: $this->resourceKey;
        $resource = new Fractal\Resource\Item($model, $transformer, $key);

        return $this->response($resource);
    }

    /**
     * model
     *
     * Returns an API Response containing a collection of models.
     *
     * @param Collection Multiple models in a collection
     * @param ModelInterface Transformer that selects visible data
     *
     * @return \Response
     */
    public function collection($collection, $transformer, $key = null)
    {
        $key = $key ?: $this->resourceKey;
        $resource = new Fractal\Resource\Collection($collection, $transformer, $key);

        return $this->response($resource);
    }

    /**
     * paginate
     *
     * Returns a paginated API Response based on a collection paginator.
     *
     * @param Paginator The paginator owned by a collection
     * @param ModelInterface Transformer that selects visible data
     *
     * @return \Response
     */
    public function paginate($paginator, $transformer, $key = null)
    {
        $key = $key ?: $this->resourceKey;
        $collection = $paginator->getCollection();

        // print_r($paginator);exit;
        $currentPage = 1;
        $lastPage = (int) $paginator->lastPage();

        // $queryParams = array_diff_key($_GET, array_flip(['page']));

        // foreach ($queryParams as $key => $value) {
        //     $paginator->addQuery($key, $value);
        // }

        $resource = new Fractal\Resource\Collection($collection, $transformer, $key);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return $this->response($resource);
    }

    /**
     * noContent
     *
     * Returns a successful 204 no content
     *
     * @return \Response
     */
    public function noContent()
    {
        return $this->simpleResponse(null, Codes::HTTP_NO_CONTENT);
    }

    /**
     * created
     *
     * Returns a successful 201 created
     *
     * @return \Response
     */
    public function created($model = null, $transformer = null)
    {
        if ($model) {
            return $this->model($model, $transformer);
        }

        return $this->simpleResponse(null, Codes::HTTP_CREATED);
    }

    /**
     * errorDatabase
     *
     * Returns a 400 Bad Request based on a DB error, such as integrity
     * violations.
     *
     * @param QueryException Error thrown
     *
     * @return \Response
     */
    public function errorDatabase($exception)
    {
        $code = is_numeric($exception) ? $exception : $exception->getCode();

        switch ($code) {
            case "1062":
            case "23000":
                $content = "Duplicate entry found.";
                break;

            default:
                $content = "Couldn't create entry.";
                break;
        }

        return $this->errorBadRequest($content);
    }

    /**
     * errorBadRequest
     *
     * Returns a 400 Bad Request error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorBadRequest($content = "")
    {
        return $this->error($content, Codes::HTTP_BAD_REQUEST);
    }

    /**
     * errorConflict
     *
     * Returns a 409 Conflict error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorConflict($content = "")
    {
        return $this->error($content, Codes::HTTP_CONFLICT);
    }

    /**
     * errorUnauthorized
     *
     * Returns a 401 Unauthorized error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorUnauthorized($content = "")
    {
        return $this->error($content, Codes::HTTP_UNAUTHORIZED);
    }

    /**
     * errorPermissions
     *
     * Returns a 401 Unauthorized error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorPermissions($content = "")
    {
        return $this->error($content ?: "User does not have permissions to do this.", Codes::HTTP_UNAUTHORIZED);
    }

    /**
     * errorNotFound
     *
     * Returns a 404 Not Found error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorNotFound($content = "")
    {
        return $this->error($content, Codes::HTTP_NOT_FOUND);
    }

    /**
     * errorNotAllowed
     *
     * Returns a 405 Method Not Allowed error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorNotAllowed($content = "")
    {
        return $this->error($content, Codes::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * errorNotAcceptable
     *
     * Returns a 406 Not Acceptable error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorNotAcceptable($content = "")
    {
        return $this->error($content, Codes::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * errorUnprocessable
     *
     * Returns a 422 Unprocessable Entity error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorUnprocessable($content = "")
    {
        return $this->error($content, Codes::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * errorInternal
     *
     * Returns a 500 Internal Server Error
     *
     * @param $string Optional message to throw with error
     *
     * @return \Response
     */
    public function errorInternal($content = "")
    {
        return $this->error($content, Codes::HTTP_INTERNAL_SERVER_ERROR);
    }


    // Getters / Setters
    // ----------------------------------------------------------------------

    public function addInclude()
    {
        $args = func_get_args();

        foreach ($args as $name) {
            $this->includes[] = $name;
        }
    }

    public function getIncludes()
    {
        return isset($_GET['include'])
            ? $_GET['include']
            : [];
    }

    protected function getCacheKey()
    {
        $input = \Request::all();
        ksort($input);
        $queryString = http_build_query($input);

        return Request::path() . "?$queryString";
    }

    protected function hasCache()
    {
        return Cache::has($this->getCacheKey());
    }


    // Internal
    // ----------------------------------------------------------------------

    protected function error($content = "", $code)
    {
        $response = new \Illuminate\Http\Response($content, $code);
        $response->header('Content-Type', 'application/json');
        $response->header('Cache-Control', 'public');

        $response->setContent([
            'status'  => 'error',
            'error'   => true,
            'message' => $content
        ]);

        $response->sendHeaders();
        $response->sendContent();

        throw new ApiException($content, null, null, [], $code);
    }

    protected function response($resource, $code = 200)
    {
        $resource->setResourceKey('data');

        if (isset($this->responseMeta)) {
            // $resource->setMetaValue('foo', 'bar');
            $resource->setMeta($this->responseMeta);
        }

        $manager = new Fractal\Manager;
        $manager->setSerializer(new Serializer\ArraySerializer());
        $manager->parseIncludes($this->getIncludes());

        $content = $manager->createData($resource)->toJson();

        // set cache
        if (getenv('API_CACHE') == 'true') {
            $key = $this->getCacheKey();
            $expiry = _const('MEMCACHE_EXPIRY');
            $expiresAt = Carbon::now()->addMinutes($expiry);

            Cache::put($key, $content, $expiresAt);
            Cache::put($key . '-expiry', $expiresAt, $expiresAt);
        }

        return $this->simpleResponse($content, 200);
    }

    protected function simpleResponse($content = "", $code = 200, $headers = array())
    {
        $response = new \Illuminate\Http\Response($content, $code);
        $response->header('Content-Type', 'application/json');
        $response->header('Cache-Control', 'public');
        $response->setExpires(@$headers['X-MCACHE-EXPIRY'] ?: new DateTime("+5 minutes"));

        // additional headers
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

    protected function json($object, $code = 200, $headers = array())
    {
        $content = json_encode($object);

        return $this->simpleResponse($content, $code, $headers);
    }

    protected function useCache()
    {
        $key = $this->getCacheKey();
        $content = Cache::get($key);
        $expiry = Cache::get($key . '-expiry');

        return $this->simpleResponse($content, 200, array(
            'X-MCACHE' => 'HIT',
            'X-MCACHE-KEY' => getenv('API_DEBUG') == 'true' ? $key : null,
            'X-MCACHE-EXPIRY' => $expiry,
        ));
    }

    public function __construct()
    {
        $this->includes = isset($_GET['include'])
            ? $_GET['include']
            : [];
    }


    // Compatibility
    // ----------------------------------------------------------------------

    public function item($model, $transformer)
    {
        return $this->model($model, $transformer);
    }

    public function items($model, $transformer)
    {
        return $this->collection($model, $transformer);
    }

}
