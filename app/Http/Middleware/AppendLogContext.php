<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AppendLogContext
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string)Str::uuid();

        Log::shareContext([
            'request_id' => $requestId,
            'user_id' => $request->user()?->id ?? 'guest',
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
