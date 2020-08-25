<?php

namespace PolymerMallard\Data;

use App;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Fractal;
use League\Fractal\Serializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use PolymerMallard\Data\Collection;
use PolymerMallard\Data\ModelInterface;
use PolymerMallard\Database\Query\Builder as Builder;
use PolymerMallard\Traits;


abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use Traits\Singleton;

    /**
     * Required fields on create
     *
     * @var array
     */
    protected static $requiredFields = [
        // no requirements
    ];

    /**
     * @var $dates
     */
    protected $dates = ['deleted_at'];

    /**
     * Requirements
     *
     * @var array
     */
    protected $rules = array(
        // no rules
    );

    /**
     * Default table name
     *
     * @var string
     */
    protected $table = 'unknown';

    /**
     * Validator instance
     *
     * @var Validator
     */
    protected $validator;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Get objects by slug
     *
     * @param  [type] $slug [description]
     *
     * @return [type]       [description]
     */
    public static function bySlug(string $slug)
    {
        $model = static::firstWhere('slug', $slug);

        return $model;
    }

    /**
     * Return count and save it into cache for an extended period of time
     *
     * Example:
     *     $n = Film::count();
     *
     * @return int
     */
    public static function count()
    {
        // Use cached value if possible
        $ttl = 60 * 60 * 24;
        $value = static::useCache('count', $ttl, function() {
            return self::all()->count();
        });

        return $value;
    }

    /**
     * The version of firstOrCreate we actually want.
     * Returns rows if integrity constraint found.
     *
     * @param array $attributes
     * @return Model
     */
    public static function lazyCreate(array $attributes)
    {
        try {
            return self::firstOrCreate($attributes);
        }
        catch (QueryException $e) {
            // return $this->assertEquals($e->getCode(), 23000);
        }

        return self::where($attributes)->first();
    }

    /**
     * Return count and save it into cache for an extended period of time
     *
     * Example:
     *     $n = Film::count();
     *
     * @return boolean
     */
    public static function useCache($partialKey, $ttl = 600, $onNotFound)
    {
        $instance = self::instance();
        $key = $instance->table . '-' . $partialKey;

        if (\Cache::has($key)) {
            $value = \Cache::get($key);
        }
        else {
            $value = call_user_func_array($onNotFound, array());
            \Cache::put($key, $value, $ttl); // 10 minutes
        }

        return $value;
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->Handle_OnCreating($model);
        });

        static::created(function ($model) {
            $model->Handle_OnCreated($model);
        });

        // For some reason, these must be "self"
        self::deleting(function ($model) {
            $model->Handle_OnDeleting($model);
        });

        // For some reason, these must be "self"
        self::deleted(function ($model) {
            $model->Handle_OnDeleted($model);
        });

        static::saving(function ($model) {
            $model->Handle_OnSaving($model);
        });

        static::saved(function ($model) {
            $model->Handle_OnSaved($model);
        });

        static::updating(function ($model) {
            $model->Handle_OnUpdating($model);
        });

        static::updated(function ($model) {
            $model->Handle_OnUpdated($model);
        });
    }

    /**
     * Create a slug based on a string
     *
     * @param  string  $title
     * @param  integer $id
     *
     * @return string
     */
    public function createSlug(string $title, int $id = 0): string
    {
        // Normalize the title
        $slug = Str::slug($title);

        // Get any that could possibly be related.
        // This cuts the queries down by doing it once.
        $allSlugs = $this->getRelatedSlugs($slug, $id);

        // If we haven't used it before then we are all good.
        if (!$allSlugs->contains('slug', $slug)) {
            return $slug;
        }

        // Just append numbers like a savage until we find not used.
        for ($i = 1; $i <= 10; $i++) {
            $newSlug = $slug . '-' . $i;

            if (!$allSlugs->contains('slug', $newSlug)) {
                return $newSlug;
            }
        }

        throw new \Exception('Can not create a unique slug. We tried 10 times and ' . $newSlug . ' wasnt good enough.');
    }

    /**
     * custom collection
     */
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }

    /**
     * Purge if we have a getURL method
     */
// public function purge(): bool
// {
//     if (method_exists($this, 'getURL')) {
//         purge($this->getURL());
//         return true;
//     }

//     return false;
// }

    /**
     * Override save to prevent writing to DB in emergencies
     */
    public function save(array $options = [])
    {
        /**
         * @NOTE @IMPORTANT
         *
         * This is used for emergencies where we shouldn't be saving data.
         * If this flag is on, we will be very confused as to why things
         * aren't working.
         *
         */
        if (isReadOnlyMode()) {
            return $this;
        }

        return parent::save($options);
    }

    /**
     * Convert model interface to an Array
     *
     * @param  ModelInterface $modelInterface
     * @param  string $includes
     *
     * @return array
     */
    public function transformToArray(ModelInterface $modelInterface, string $includes = ''): array
    {
        $resource = $this->transform($modelInterface);
        $resource->setResourceKey('data');

        $manager = new Fractal\Manager;
        $manager->setSerializer(new Serializer\ArraySerializer());
        $manager->parseIncludes($includes);

        $content = $manager->createData($resource)->toArray();

        return $content;
    }

    /**
     * Convert model interface to JSON
     *
     * @param  ModelInterface $modelInterface
     * @param  string $includes
     *
     * @return object
     */
    public function transformToJSON(ModelInterface $modelInterface, string $includes = ''): object
    {
        $resource = $this->transform($modelInterface);
        $resource->setResourceKey('data');

        $manager = new Fractal\Manager;
        $manager->setSerializer(new Serializer\ArraySerializer());
        $manager->parseIncludes($includes);

        $content = $manager->createData($resource)->toJSON();

        return $content;
    }

    /**
     * transform
     *
     * Convenience method that transforms this model using a specified
     * ModelInterface
     *
     * @param ModelInterface $modelInterface  Object describing how to transform model
     *
     * @return League\Fractal\Resource\Item
     */
    public function transform(ModelInterface $modelInterface): Fractal\Resource\Item
    {
        $resource = new Fractal\Resource\Item($this, $modelInterface);

        return $resource;
    }

    /**
     * Attempt to validate
     *
     * @param  array $data
     *
     * @return boolean
     */
    public function validate($data = null): bool
    {
        // Prefill data
        if ($data == null) {
            $data = $this->toArray();
        }

        // make a new validator object
        $this->validator = Validator::make($data, $this->rules);

        // return the result
        return $this->validator->passes();
    }

    /**
     * Get slugs of type
     *
     * @param  string  $slug
     * @param  integer $id
     *
     * @return Collection
     */
    protected function getRelatedSlugs(string $slug, int $id = 0)
    {
        return self::select('slug')
            ->where('slug', 'like', $slug . '%')
            ->where('id', '<>', $id)
            ->get();
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \PolymerMallard\Database\Query\Builder
     */
    protected function newBaseQueryBuilder(): Builder
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        return new Builder($conn, $grammar, $conn->getPostProcessor());
    }

    /**
     * OnCreating handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnCreating($model): void {
        // creating
    }

    /**
     * OnCreated handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnCreated($model): void {
        // created
    }

    /**
     * OnDeleting handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnDeleting($model): void {
        // deleting
    }

    /**
     * OnDeleted handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnDeleted($model): void {
        // deleted
    }

    /**
     * Handle_OnSaving handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnSaving($model): void {
        // saved
    }

    /**
     * OnSaved handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnSaved($model): void {
        // saved
    }

    /**
     * OnUpdating handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnUpdating($model): void {
        // updated
    }

    /**
     * OnUpdated handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnUpdated($model): void {
        // updated
    }
}
