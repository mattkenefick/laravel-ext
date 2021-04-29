<?php

namespace PolymerMallard\Data;

// class Collection extends \Illuminate\Support\Collection
class Collection extends \Illuminate\Database\Eloquent\Collection
{
    /**
     * If we should exclude the .N from keys
     */
    public $excludeNewFromSurrogateKeys = false;

    /**
     * Attempts to add this model to our list of surrogate keys
     * which are used for invalidation
     */
    public function addToSurrogateKeys($excludeNew = false, $withPrefix = '')
    {
        foreach ($this as $model) {
            if (is_a($model, Model::class)) {
                $model->addToSurrogateKeys($excludeNew || $this->excludeNewFromSurrogateKeys, $withPrefix);
            }
        }
    }

    /**
     * Get model at index
     *
     * @param  int $index
     * @return Model
     */
    public function at(int $index)
    {
        return $this[$index];
    }

    /**
     * delete
     *
     * Delete all models in collection
     */
    public function delete() {
        foreach (static::all() as $model) {
            $model->delete();
        }
    }

    /**
     * findWhere
     *
     * Search a collection's model field for a value
     *
     * @param string $key   Model column to search
     * @param string $value Value to compare to column
     *
     * @return
     */
    public function findWhere($key, $value) {
        return $this->filter(function ($model) use ($key, $value) {
            if ($model->{$key} == $value) {
                return true;
            }
        });
    }

    /**
     * firstWhere
     *
     * Run findWhere and return singular, first, model
     *
     * @param string $key   Model column to search
     * @param string $value Value to compare to column
     *
     * @return
     */
    public function firstWhere($key, $operator = null, $value = null) {
        return $this->findWhere($key, $operator, $value)->first();
    }
}
