<?php

namespace App\Http\Middleware;

use Closure;

class AuthAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       if ( auth('api')->check() && auth('api')->user()->isAdmin() )
        {
            return $next($request);
        }else{
            return response()->json("Forbidden");
        }
    }
}