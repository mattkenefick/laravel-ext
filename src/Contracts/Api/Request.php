<?php namespace PolymerMallard\Contracts\Api;


interface Request {

    /**
     * GET based route
     *
     * Returns a collection of models. Should return with a method
     * not allowed unless overriden.
     *
     * @return \Response
     */
    public function get_index();

    /**
     * GET based route
     *
     * Returns a collection of models. Should return with a method
     * not allowed unless overriden.
     *
     * @return \Response
     */
    public function getWithCode($code);

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
    public function get_single($id);

    /**
     * GET based route
     *
     * Returns specific model. Should return with a method
     * not allowed unless overriden.
     *
     * @return \Response
     */
    public function getWithCode_single($code, $single);

    /**
     * POST based route
     *
     * Creates a new model. Should return with a method
     * not allowed unless overriden.
     *
     * @return \Response
     */
    public function post_index();

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
    public function put_single($id);

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
    public function delete_single($id);

}
