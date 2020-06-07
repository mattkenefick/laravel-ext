<?php namespace PolymerMallard\Database\Query;

use Illuminate\Support\Collection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;


class Builder extends \Illuminate\Database\Query\Builder {

    /**
     * whereWithPayload
     *
     * Creates a where statement based on a payload of parameters.
     *
     * Can convert input field names to different names... such as:
     *     array("name" => "display_name")
     *     Will convert user input "name" to variable "display_name"
     *
     * @param array A modifier payload that contains > < = and fieldnames
     * @param string Whether we're using AND statements or OR statements
     *
     * @return PolymerMallard\Data\Model
     */
    public function whereWithPayload($payload, $connector = 'and')
    {
        // modifier payload
        if (isset($payload->conditions) && count($payload->conditions) > 0) {

            // apply params
            return $this->whereRaw(
                implode(" $connector ", $payload->conditions),
                $payload->values
            );
        }

        // regular payload
        else if (count($payload) > 0 && !isset($payload->conditions)) {
            $source = $this;

            foreach ($payload as $key => $value) {
                $source = $this->where($key, $value);
            }

            return $source;
        }

        return $this;
    }

    /**
     * where
     *
     * Accept an array of where params to pass to the collection
     *
     * @param array List of where params to apply
     *
     * @return PolymerMallard\Data\Model
     */
    public function where($column, $operator = NULL, $value = NULL, $boolean = 'and')
    {
        // non-array
        if (!is_array($column)) {
            return parent::where($column, $operator, $value, $boolean);
        }

        if (is_array($column)) {
            foreach ($column as $innerKey => $innerValue) {
               $this->where($innerKey, '=', $innerValue, $boolean);
            }
        }

        return $this;
    }

    /**
     * age
     *
     * ID ranges of the content we want to get
     *
     * @param int $since_id  ID > number
     * @param int $max_id    ID < number
     *
     * @return PolymerMallard\Data\Model
     */
    public function age($since_id = null, $max_id = null, $key = "")
    {
        $source = $this;

        if (!empty($key)) {
            $key = $key . ".";
        }

        if (isset($since_id)) {
            $source = $this->where($key . 'id', '>', $since_id);
        }

        if (isset($max_id)) {
            $source = $this->where($key . 'id', '<', $max_id);
        }

        return $source;
    }

}
