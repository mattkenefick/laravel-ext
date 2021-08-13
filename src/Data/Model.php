<?php

namespace PolymerMallard\Data;

use App;
use Cache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Fractal;
use League\Fractal\Serializer;
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
     * For SurrogateKeys Fastly / XKEY Varnish
     *
     * This is a static field because it's okay to compound on this array
     * per request.
     *
     * @var array
     */
    public static $surrogateKeys = [];

    /**
     * Prefix of our cache key e.g. 'cmp' for 'cmp.5', 'cmp.10'
     * Default: get_class(...)
     *
     * @var string
     */
    public $cachePrefix = null;

    /**
     * Used for surrogate keys that we can be appended to the list
     */
    public $canCache = true;

    /**
     * If we should exclude the .N from keys
     */
    public $excludeNewFromSurrogateKeys = false;

    /**
     * @var $dates
     */
    protected $dates = [
        'deleted_at'
    ];

    /**
     * Requirements
     *
     * @var array
     */
    protected $rules = [
        // no rules
    ];

    /**
     * Validator instance
     *
     * @var Validator
     */
    protected $validator;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array $attributes
     * @return void
     */
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    /**
     * Get objects by slug
     *
     * @param string $slug [description]
     *
     * @return [type] [description]
     */
    public static function bySlug(string $slug, int $type = 0)
    {
        $model = static::where('slug', $slug);

        if ($type) {
            $model = $model->where('type', $type);
        }

        return $model->first();
    }

    /**
     * Return count and save it into cache for an extended period of time
     *
     * Example:
     *     $n = Film::count();
     *
     * @return int
     */
    // public static function count()
    // {
    //     // // Use cached value if possible
    //     // $ttl = 60 * 60 * 24;
    //     // $value = static::useCache('count', $ttl, function() {
    //     //     return self::all()->count();
    //     // });

    //     // return $value;
    //     //
    //     return static::all()->count();
    // }

    /**
     * The version of firstOrCreate we actually want.
     * Returns rows if integrity constraint found.
     *
     * @param  array $attributes
     * @return Model
     */
    public static function lazyCreate(array $attributes) {
        try {
            return self::firstOrCreate($attributes);
        } catch (QueryException $e) {
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
    public static function useCache($partialKey, $ttl, $onNotFound)
    {
        $instance = self::instance();
        $key = $instance->table . '-' . $partialKey;

        if (Cache::store('redis')->has($key)) {
            $storedValue = Cache::store('redis')->get($key);
            $value = json_decode($storedValue);
        }
        else {
            $value = call_user_func_array($onNotFound, []);
            $storedValue = json_encode($value);
            Cache::store('redis')->put($key, $storedValue, $ttl);
        }

        return $value;
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted() {
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

        static::retrieved(function ($model) {
            $model->Handle_OnRetrieved($model);
        });

        // static::restoring(function ($model) {
        //     $model->Handle_OnRestoring($model);
        // });

        // static::restored(function ($model) {
        //     $model->Handle_OnRestored($model);
        // });

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
     * @param string  $title
     * @param integer $id
     *
     * @return string
     */
    // public function createSlug(string $title, int $type = 0, int $id = 0): string
    public function createSlug(string $title, array $uniqueConstraints = [], int $idToExclude = 0): string
    {
        // Refuse to create empty slug
        if (empty($title)) {
            throw new \Exception('Cannot create slug from empty string.');
        }

        // Normalize the title
        $slug = Str::slug($title);

        // Use this models ID if one has not been supplied
        if ($idToExclude === 0 && $this->id > 0) {
            $idToExclude = $this->id;
        }

        return $this->createIncrementalField('slug', $slug, $uniqueConstraints, $idToExclude);
    }

    /**
     * Create a field that increments as a string
     * like a slug/username/etc
     *
     * @param  string  $title
     * @param  integer $id
     *
     * @return string
     */
    public function createIncrementalField(string $field = 'slug', string $string = '', array $uniqueConstraints = [], int $idToExclude = 0): string
    {
        // Get any that could possibly be related.
        // This cuts the queries down by doing it once.
        $allItems = $this->getRelatedFields($field, $string, $uniqueConstraints, $idToExclude);

        // If we haven't used it before then we are all good.
        if (!$allItems->contains($field, $string)) {
            return $string;
        }

        // Just append numbers like a savage until we find not used.
        for ($i = 1; $i <= 30; $i++) {
            $newString = $string . '-' . $i;

            if (!$allItems->contains($field, $newString)) {
                return $newString;
            }
        }

        throw new \Exception('Can not create a unique ' . $field . '. We tried 30 times and ' . $newString . ' wasnt good enough.');
    }

    /**
     * Attempts to add this model to our list of surrogate keys
     * which are used for invalidation.
     *
     * $cacheKey.N is used for new items. When we create new items,
     * there's no item to purge. Just because the key is on this
     * doesn't mean we have to purge it.
     */
    public function addToSurrogateKeys($excludeNew = false, $withPrefix = '')
    {
        // Usually looks like "f", "md", or "App\Models\Media"
        $cacheKey = $this->getSurrogateKey($withPrefix, false);

        // Some classes can be excluded
        if ($this->canCache) {
            static::$surrogateKeys[] = $cacheKey . '.' . $this->id;

            //
            if ($excludeNew === false && $this->excludeNewFromSurrogateKeys === false) {
                static::$surrogateKeys[] = $cacheKey . '.N';
            }
        }

        return static::$surrogateKeys;
    }

    /**
     * Returns surrogate key for use in `addToSurrogateKeys` but also for
     * custom purge requests
     */
    public function getSurrogateKey($withPrefix = '', $includeId = true)
    {
        // Usually looks like "f", "md", or "App\Models\Media"
        $cacheKey = $this->cachePrefix != null
            ? $this->cachePrefix
            : get_class($this);

        // Apply prefix
        $cacheKey = $withPrefix != ''
            ? $withPrefix . '.' . $cacheKey
            : $cacheKey;

        // Get key
        if ($this->canCache && $includeId) {
            return $cacheKey . '.' . $this->id;
        }

        return $cacheKey;
    }

    /**
     * Override save to prevent writing to DB in emergencies
     */
    public function save(array $options = []) {
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
     * @param ModelInterface $modelInterface
     * @param string         $includes
     *
     * @return array
     */
    public function transformToArray(ModelInterface $modelInterface, string $includes = ''): array {
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
     * @param ModelInterface $modelInterface
     * @param string         $includes
     *
     * @return object
     */
    public function transformToJSON(ModelInterface $modelInterface, string $includes = ''): object {
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
     * @param ModelInterface $modelInterface Object describing how to transform model
     *
     * @return League\Fractal\Resource\Item
     */
    public function transform(ModelInterface $modelInterface): Fractal\Resource\Item {
        $resource = new Fractal\Resource\Item($this, $modelInterface);

        return $resource;
    }

    /**
     * Attempt to validate
     *
     * @param array $data
     *
     * @return boolean
     */
    public function validate($data = null): bool {
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
     * Get fields of type
     *
     * @param  string  $string
     * @param  integer $id
     * @param  integer $type
     *
     * @return Collection
     */
    protected function getRelatedFields(string $field, string $string, array $uniqueConstraints = [], int $idToExclude = 0)
    {
        $model = self::select($field)
            ->where($field, 'like', $string . '%') // Should we use the "-"?
            ->where('id', '<>', $idToExclude);

        if ($uniqueConstraints) {
            foreach ($uniqueConstraints as $key => $value) {
                $model = $model->where($key, $value);
            }
        }

        return $model->get();
    }

    /**
     * Get slugs of type
     *
     * @param  string  $slug
     * @param  integer $id
     * @param  integer $type
     *
     * @return Collection
     */
    protected function getRelatedSlugs(string $slug, array $uniqueConstraints = [], int $idToExclude = 0)
    {
        return $this->getRelatedFields('slug', $slug, $uniqueConstraints, $idToExclude);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \PolymerMallard\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        return new Builder($conn, $grammar, $conn->getPostProcessor());
    }

    /**
     * custom collection
     */
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }

    /**
     * OnCreating or OnSaving handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnCreatingOrSaving($model): void
    {
        // creating or saving
    }

    /**
     * OnCreated or OnSaved handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnCreatedOrSaved($model): void
    {
        // created or saved
    }

    /**
     * OnCreating handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnCreating($model): void
    {
        $this->Handle_OnCreatingOrSaving($model);

        // creating
    }

    /**
     * OnCreated handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnCreated($model): void
    {
        $this->Handle_OnCreatedOrSaved($model);

        // created
    }

    /**
     * OnDeleting handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnDeleting($model): void
    {
        // deleting
    }

    /**
     * OnDeleted handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnDeleted($model): void
    {
        // deleted
    }

    /**
     * OnRetrieved handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnRetrieved($model): void
    {
        // retrieved
    }

    /**
     * OnRestoring handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnRestoring($model): void
    {
        // restoring
    }

    /**
     * OnRestored handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnRestored($model): void
    {
        // restored
    }

    /**
     * Handle_OnSaving handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnSaving($model): void
    {
        $this->Handle_OnCreatingOrSaving($model);

        // saved
    }

    /**
     * OnSaved handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnSaved($model): void
    {
        $this->Handle_OnCreatedOrSaved($model);

        // saved
    }

    /**
     * OnUpdated handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnUpdating($model): void
    {
        // updated
    }

    /**
     * OnUpdated handler from `booted`
     *
     * @param void
     */
    protected function Handle_OnUpdated($model): void
    {
        // updated
    }
}
