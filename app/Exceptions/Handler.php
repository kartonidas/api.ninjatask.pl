<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Throwable;

use App\Exceptions\AccessDenied;
use App\Exceptions\Exception;
use App\Exceptions\ObjectNotExist;
use App\Exceptions\Unauthorized;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        AccessDenied::class,
        Exception::class,
        ObjectNotExist::class,
        Unauthorized::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
        
        $this->renderable(function (ValidationException $exception, $request) {
            if (!$request->wantsJson())
                return null;
            
            return new JsonResponse([
                'error' => true,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        });
        
        $this->renderable(function (AuthenticationException $exception, $request) {
            if (!$request->wantsJson())
                return null;
            
            return new JsonResponse([
                'error' => true,
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        });
        
    }
}
