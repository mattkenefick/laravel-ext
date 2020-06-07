<?php

namespace PolymerMallard\Data;

use League\Fractal;

class ModelInterface extends Fractal\TransformerAbstract
{

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
