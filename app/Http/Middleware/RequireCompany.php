<?php

namespace App\Http\Middleware;

use Closure;

class RequireCompany
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
        if ( null == $request->user()->activeCompany() ) {
            return redirect()->route('company.create');
        }

        return $next($request);
    }
}
