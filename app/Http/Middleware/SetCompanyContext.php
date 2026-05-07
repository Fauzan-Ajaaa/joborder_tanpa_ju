<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->company_id) {
                // Store in session and config for easy access without calling Auth::user() in models
                session(['active_company_id' => $user->company_id]);
                config(['app.company_id' => $user->company_id]);
            }
        }

        return $next($request);
    }
}
