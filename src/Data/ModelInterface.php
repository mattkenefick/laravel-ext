<?php

namespace PolymerMallard\Data;

use League\Fractal;

/**
 *
 * The booting and initialization of traits was taken from the
 * Database/Eloquent/Model from Laravel
 */
class ModelInterface extends Fractal\TransformerAbstract
{
    /**
     * For SurrogateKeys Fastly / XKEY Varnish
     *
     * @var array
     */
    public static $surrogateKeys = [];

    /**
     * The array of booted models.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of trait initializers that will be called on each new instance.
     *
     * @var array
     */
    protected static $traitInitializers = [];

    /**
     * [$properties description]
     * @var array
     */
    protected $properties = array();

    /**
     * Load and decide on model / collection
     *
     * @param  array $rows
     * @return mixed
     */
    public static function load($rows = [])
    {
        $instance = new static;

        // Collection
        if ($rows && count($rows)) {
            return $instance->collection($rows, $instance);
        }
        else if (is_object($rows)) {
            return $instance->model($rows, $instance);
        }
        else {
            return $instance;
        }
    }

    /**
     * Model alias
     *
     * @param [type] $model       [description]
     * @param [type] $transformer [description]
     *
     * @return [type] [description]
     */
    public function model($model, $transformer) {
        if ($model == null) {
            return $this->item(null, function () {
                return [];
            });
        }

        // Add to keys
        if (method_exists($model, 'addToSurrogateKeys')) {
            $model->addToSurrogateKeys();
        }

        return $this->item($model, $transformer);
    }

    /**
     * Collection alias
     *
     * @param [type] $models      [description]
     * @param [type] $transformer [description]
     *
     * @return [type] [description]
     */
    public function collection($models, $transformer, $resourceKey = null) {
        if ($models == null) {
            return parent::collection(null, function () {
                return [];
            });
        }

        // Add to keys
        foreach ($models as $model) {
            if (method_exists($model, 'addToSurrogateKeys')) {
                $model->addToSurrogateKeys();
            }
        }

        return parent::collection($models, $transformer, $resourceKey);
    }

    // Actionable
    // ----------------------------------------------------------------------

    public function transform($model) {
        $response = [];

        foreach ($this->properties as $value) {
            $response[$value] = $model->{$value};
        }

        return $response;
    }

    public function toArray($resource) {
        $fractal = new Fractal\Manager;
        $content = $fractal->createData($resource)->toArray();

        return $content;
    }

    public function toJson($resource) {
        $fractal = new Fractal\Manager;
        $content = $fractal->createData($resource)->toJson();

        return $content;
    }

    // Internal
    // ----------------------------------------------------------------------

    public function __construct(array $data = []) {
        $this->properties = array_merge($this->properties, $data);

        $this->bootIfNotBooted();

        $this->initializeTraits();
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            static::booting();
            static::boot();
            static::booted();
        }
    }
    /**
     * Perform any actions required before the model boots.
     *
     * @return void
     */
    protected static function booting()
    {
        //
    }

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function booted()
    {
        //
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        $booted = [];

        static::$traitInitializers[$class] = [];

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot' . class_basename($trait);

            if (method_exists($class, $method) && !in_array($method, $booted)) {
                forward_static_call([$class, $method]);

                $booted[] = $method;
            }

            if (method_exists($class, $method = 'initialize' . class_basename($trait))) {
                static::$traitInitializers[$class][] = $method;

                static::$traitInitializers[$class] = array_unique(
                    static::$traitInitializers[$class]
                );
            }
        }
    }

    /**
     * Initialize any initializable traits on the model.
     *
     * @return void
     */
    protected function initializeTraits()
    {
        foreach (static::$traitInitializers[static::class] as $method) {
            $this->{$method}();
        }
    }
}
