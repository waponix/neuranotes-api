<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            return match(true) {
                $e instanceof AuthenticationException => response()->json(['error' => 'invalid token'], 401),
                true => response()->json(['error' => 'system error', 'file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage()], 500),
            };
        }

        return $this->render($request, $e);
    }
}
