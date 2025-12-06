<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
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

        // Handle PostTooLargeException gracefully
        $this->renderable(function (PostTooLargeException $e, $request) {
            \Log::warning('Post too large exception', ['size' => $request->server('CONTENT_LENGTH')]);

            return back()
                ->with('error', 'File size exceeds maximum allowed limit of 10MB.')
                ->withInput();
        });
    }
}
