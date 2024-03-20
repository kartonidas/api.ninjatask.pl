<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

use App\Exceptions\SubscriptionRequired;
use App\Models\Subscription;

class SubscriptionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if(!Auth::user()->hasActiveSubscription())
            throw new SubscriptionRequired(__("Subscription required"));
        
        return $next($request);
    }
}
