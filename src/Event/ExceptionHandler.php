<?php

namespace PolymerMallard\Event;

use Exception;
use PolymerMallard\Exception\Handler;
use PolymerMallard\Exception\ApiException;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


class ExceptionHandler extends \Illuminate\Foundation\Exceptions\Handler
{
    protected $handler;
    protected $debug;

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
        $this->debug = getenv('API_DEBUG') == "true" ?: false;
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {

    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $exception)
    {
        // regular exception
        if ($this->handler->willHandle($exception)) {
            return parent::render($request, $exception);
        }

        // api exception
        if (! $message = $exception->getMessage()) {
            $message = sprintf('%d %s', $exception->getStatusCode(), Response::$statusTexts[$exception->getStatusCode()]);
        }

        $statusCode = $exception->getStatusCode();
        $headers = $exception->getHeaders();

        $response = ['message' => $message, 'status_code' => $statusCode];

        if ($exception instanceof ApiException && $exception->hasErrors()) {
            $response['errors'] = $exception->getErrors();
        }

        if ($code = $exception->getCode()) {
            $response['code'] = $code;
        }

        if ($this->debug) {
            $response['debug'] = [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'class' => get_class($exception),
                'trace' => explode("\n", $exception->getTraceAsString())
            ];
        }

        return new Response($response, $statusCode, $headers);
    }

}
