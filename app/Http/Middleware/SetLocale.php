<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->input("_locale", config("api.default_language"));
        if(!in_array($locale, config("api.allowed_languages")))
            $locale = config("api.default_language");
        app()->setLocale($locale);
        
        return $next($request);
    }
}
