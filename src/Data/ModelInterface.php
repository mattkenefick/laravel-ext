<?php

namespace PolymerMallard\Data;

use League\Fractal;
use League\Fractal\Scope;

/**
 *
 */
class ModelInterface extends Fractal\TransformerAbstract
{
    /**
     * For SurrogateKeys Fastly / XKEY Varnish
     *
     * @var array
     */
    public static $surrogateKeys = array();

    /**
     * [$properties description]
     * @var array
     */
    protected $properties = array();

    /**
     * Model alias
     *
     * @param  [type] $model       [description]
     * @param  [type] $transformer [description]
     *
     * @return [type]              [description]
     */
    public function model($model, $transformer)
    {
        if ($model == null) {
            return $this->item(null, function() {
                return [];
            });
        }

        // Add to keys
        // @todo
        $cacheKey = $model->cachePrefix != null ? $model->cachePrefix : get_class($model);

        if ($model->canCache) {
            static::$surrogateKeys[] = $cacheKey . '.' . $model->id;
        }

        return $this->item($model, $transformer);
    }

    /**
     * Collection alias
     *
     * @param  [type] $models      [description]
     * @param  [type] $transformer [description]
     *
     * @return [type]              [description]
     */
    public function collection($models, $transformer, $resourceKey = null)
    {
        if ($models == null) {
            return parent::collection(null, function() {
                return [];
            });
        }

        // Add to keys
        // @todo
        $ids = [];

        foreach ($models as $value) {
            $cacheKey = $value->cachePrefix != null ? $value->cachePrefix : get_class($value);

            if ($value->canCache) {
                $ids[] = $cacheKey . '.' . $value->id;
            }
        }

        //
        static::$surrogateKeys = array_merge(static::$surrogateKeys, $ids);

        return parent::collection($models, $transformer, $resourceKey);
    }


    // Actionable
    // ----------------------------------------------------------------------

    public function transform($model)
    {
        $response = [];

        foreach ($this->properties as $value) {
            $response[$value] = $model->{$value};
        }

        return $response;
    }

    public function toArray($resource)
    {
        $fractal = new Fractal\Manager;
        $content = $fractal->createData($resource)->toArray();

        return $content;
    }

    public function toJson($resource)
    {
        $fractal = new Fractal\Manager;
        $content = $fractal->createData($resource)->toJson();

        return $content;
    }


    // Internal
    // ----------------------------------------------------------------------

    public function __construct(array $data = array())
    {
        $this->properties = array_merge($this->properties, $data);
    }
}
