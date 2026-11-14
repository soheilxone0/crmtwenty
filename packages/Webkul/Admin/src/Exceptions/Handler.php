<?php

namespace Webkul\Admin\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Json error messages.
     *
     * @var array
     */
    protected $jsonErrorMessages = [];

    /**
     * Create handler instance.
     *
     * @return void
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->jsonErrorMessages = [
            '404' => trans('admin::app.errors.404.title'),
            '403' => trans('admin::app.errors.403.title'),
            '401' => trans('admin::app.errors.401.title'),
            '500' => trans('admin::app.errors.500.title'),
        ];
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @return Response
     */
    public function render($request, Throwable $exception)
    {
        if (config('app.debug') || $this->shouldRenderNatively($exception)) {
            return parent::render($request, $exception);
        }

        return $this->renderCustomResponse($exception);
    }

    /**
     * crmtwenty: exceptions that already carry their own response (a redirect, validation
     * errors, a 419 or a 429) keep it. Mapping them to the generic error page, as happened
     * whenever APP_DEBUG was off, broke rate limiting, "session expired" and redirects.
     */
    protected function shouldRenderNatively(Throwable $exception): bool
    {
        if (
            $exception instanceof HttpResponseException
            || $exception instanceof AuthenticationException
            || $exception instanceof ValidationException
            || $exception instanceof TokenMismatchException
        ) {
            return true;
        }

        return $exception instanceof HttpException
            && ! in_array($exception->getStatusCode(), [401, 403, 404, 500, 503]);
    }

    /**
     * crmtwenty: an expired form (CSRF token mismatch) sends the user back to the form
     * with a message instead of an English "Page Expired" screen.
     */
    protected function prepareResponse($request, Throwable $e)
    {
        if (
            ! config('app.debug')
            && $e instanceof HttpException
            && $e->getStatusCode() === 419
            && ! $request->expectsJson()
        ) {
            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('warning', trans('persian::app.errors.session-expired'));
        }

        return parent::prepareResponse($request, $e);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  Request  $request
     * @return Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->jsonErrorMessages[401]], 401);
        }

        return redirect()->guest(route('admin.session.create'));
    }

    /**
     * Render custom HTTP response.
     *
     * @return Response|null
     */
    private function renderCustomResponse(Throwable $exception)
    {
        if ($exception instanceof HttpException) {
            $statusCode = in_array($exception->getStatusCode(), [401, 403, 404, 503])
                ? $exception->getStatusCode()
                : 500;

            return $this->response($statusCode);
        }

        if ($exception instanceof ValidationException) {
            return parent::render(request(), $exception);
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->response(404);
        } elseif ($exception instanceof PDOException || $exception instanceof \ParseError) {
            return $this->response(500);
        } else {
            return $this->response(500);
        }
    }

    /**
     * Return custom response.
     *
     * @param  string  $path
     * @param  string  $errorCode
     * @return mixed
     */
    private function response($errorCode)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => isset($this->jsonErrorMessages[$errorCode])
                    ? $this->jsonErrorMessages[$errorCode]
                    : trans('admin::app.common.something-went-wrong'),
            ], $errorCode);
        }

        return response()->view('admin::errors.index', compact('errorCode'), (int) $errorCode);
    }
}
