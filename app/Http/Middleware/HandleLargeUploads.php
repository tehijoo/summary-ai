<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleLargeUploads
{
    const MAX_UPLOAD_SIZE = 10 * 1024 * 1024; // 10MB

    public function handle(Request $request, Closure $next): Response
    {
        // Check Content-Length header BEFORE Laravel processes it
        $contentLength = (int)$request->server('CONTENT_LENGTH');
        
        if ($contentLength > self::MAX_UPLOAD_SIZE) {
            return back()
                ->with('error', 'File size exceeds maximum allowed limit of 10MB.')
                ->withInput();
        }

        return $next($request);
    }
}