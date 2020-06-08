<?php namespace PolymerMallard\Http\Controllers\Api;

use App\Data\Models;
use PolymerMallard\Exception\ApiException;
use PolymerMallard\Contracts\Api\Request;
use PolymerMallard\Http\Controllers\Api\Response;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Input;
use Log;
use Validator;


abstract class Controller extends Response implements Request
{
    /**
     * Data to send down to document
     * @var $domData
     */
    protected $domData = array();

    /**
     * Additional ModelInterface embeds to include
     * @var $includes
     */
    protected $includes = null;

    /**
     * Max amount of results per page for paginated returns.
     *
     * @var int
     */
    protected $limit = 20;

    /**
     * Returns results with an ID greater than (that is, more
     * recent than) the specified ID. There are limits to the
     * number of Tweets which can be accessed through the API.
     * If the limit of Tweets has occured since the since_id,
     * the since_id will be forced to the oldest ID available.
     *
     * @var int
     */
    protected $since_id = null;

    /**
     * Returns results with an ID less than (that is, older than)
     * or equal to the specified ID.
     *
     * @var int
     */
    protected $max_id = null;

    /**
     * Default page
     *
     * @var int
     */
    protected $page = 1;

    /**
     * Resource key attached to JSON output
     * @var $resourceKey
     */
    protected $resourceKey = null;

    /**
     * GET based route
     *
     * Returns a collection of models. Should return with a method
     * not allowed unless overriden.
     *
     * @return \Response
     */
    public function get_index()
    {
        return $this->errorNotAllowed();
    }

    /**
     * GET based route
     *
     * Returns a collection of models. Should return with a method
     * not allowed unless overriden.
     *
     * @return \Response
     */
    public function getWithCode($code)
    {
        return $this->errorNotAllowed();
    }

    /**
     * GET based route
     *
     * Returns specific model. Should return with a method
     * not allowed unless overriden.
     *
     * @param int $id
     *
     * @return \Response
     */
    public function get_single($id)
    {
        return $this->errorNotAllowed();
    }

    /**
     * GET based route
     *
     * Returns specific model. Should return with a method
     * not allowed unless overriden.
     *
     * @param int $id
     *
     * @return \Response
     */
    public function getWithCode_single($code, $id)
    {
        return $this->errorNotAllowed();
    }

    /**
     * PUT based route
     *
     * Updates a specific model. Should return with a method
     * not allowed unless overriden.
     *
     * @param int $id
     *
     * @return \Response
     */
    public function put_single($id)
    {
        return $this->errorNotAllowed();
    }

    /**
     * POST based route
     *
     * Creates a new model. Should return with a method
     * not allowed unless overriden.
     *
     * @return \Response
     */
    public function post_index()
    {
        return $this->errorNotAllowed();
    }

    /**
     * DELETE based route
     *
     * Deletes a specific model. Should return with a method
     * not allowed unless overriden.
     *
     * @param int $id
     *
     * @return void
     */
    public function delete_single($id)
    {
        return $this->errorNotAllowed();
    }

    /**
     * payload
     *
     * Compares user input to suggested fields and rules. Errors out on
     * missing required fields, mismatched rules, etc.
     *
     * Can convert input field names to different names... such as:
     *     array("name" => "display_name")
     *     Will convert user input "name" to variable "display_name"
     *
     * @param array Field names + rules, for validator
     * @param array Converter of field names
     *
     * @return \Response
     */
    public function payload(array $rules, array $defaults = null)
    {
        $fields    = array_keys($rules);
        $payload   = call_user_func_array('\Request::only', $fields);
        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            return $this->errorUnprocessable('Could not validate content.', $validator->errors());
        }

        if (isset($defaults)) {
            foreach ($defaults as $key => $value) {
                $payload[$key] = isset($payload[$key]) ? $payload[$key] : $value;
            }
        }

        return array_filter($payload, function($var) {
            return ($var !== NULL && $var !== FALSE && $var !== '');
        });
    }

    /**
     * modifierPayload
     *
     * Compares user input to suggested fields and rules. Errors out on
     * missing required fields, mismatched rules, etc.
     *
     * Can convert input field names to different names... such as:
     *     array("name" => "display_name")
     *     Will convert user input "name" to variable "display_name"
     *
     * @param array Field names + rules, for validator
     * @param array Converter of field names
     *
     * @return \Response
     */
    public function modifierPayload(array $rules)
    {
        $fields  = array_keys($rules);
        $input   = call_user_func_array('\Request::only', $fields);
        $payload = array_filter($input, function($var) {
            return ($var !== NULL && $var !== FALSE && $var !== '');
        });
        $object  = (object) array(
            'conditions' => [],
            'values'     => [],
            'modifiers'  => []
        );

        // convert modifiers
        foreach ($payload as $field => $value) {
            $modifier = $this->getModifier($value);
            $value = $this->getModifierValue($value);

            $field_converted = $rules[$field];

            $object->values[]     = $value;
            $object->modifiers[]  = $modifier;
            $object->conditions[] = "`$field_converted` $modifier ?";
        }

        return $object;
    }

    /**
     * checkPermissions
     *
     * @param string Type of permission to check
     * @param User Optional user to check for, or Auth'd user
     *
     * @return bool
     */
    public function checkPermissions($do, User $user = null)
    {
        if (getenv("API_REQUIRE_PERMISSIONS") === "false") {
            Log::info("Bypass API Required Permissions. " . \Request::path());

            return true;
        }

        $user = $user ?: \Auth::user();

        if (!$user) {
            return $this->errorUnauthorized();
        }

        return $user->can($do);
    }


    // Internal
    // ----------------------------------------------------------------------

    public function __construct()
    {
        // GET variables
        $this->page = \Request::get('page') ?: $this->page;
        $this->limit = \Request::get('limit') ?: $this->limit;
        $this->max_id = \Request::get('max_id') ?: $this->max_id;
        $this->since_id = \Request::get('since_id') ?: $this->since_id;

        // throttle limit
        $this->limit = max(0, min(static::LIMIT_MAX, $this->limit));
    }

    protected function getModifier($value)
    {
        $modifier = $value[0];

        switch ($modifier) {
            case '<':
            case '>':

                // gt, lt or equal to
                if ($value[1] === '=')
                    return $modifier . '=';

                // gt, lt
                return $modifier;

            // equal to
            case '=':
            default:
                return '=';
        }
    }

    protected function getModifierValue($value)
    {
        $modifiers = ['<', '>', '<=', '>=', '=', '-', '+'];

        if (in_array($value[0], $modifiers)) {
            // or equal to
            if ($value[1] === '=')
                return substr($value, 2);

            // regular modifier
            return substr($value, 1);
        }
        else {
            return $value;
        }
    }

    /**
     * Check if we're allowed to do something
     *
     * @return  boolean
     */
    public function can($permission)
    {
        if ($user = Models\Users::fromJWT()) {

            // only Admin user ID #1 can access
            if ( $user->can($permission) ) {
                return true;
            }

        }

        return false;
    }

    /**
     * Check if we're allowed to do something
     *
     * @return boolean|User
     */
    public function adminCan($permission)
    {
        if ($this->isAdminRequest()) {
            if ($user = Models\Users::fromJWT()) {
                if ($user->can($permission)) {
                    return $user;
                }
            }
        }

        return false;
    }


    public function isAdminRequest()
    {
        return \Request::has('admin');
    }

}
