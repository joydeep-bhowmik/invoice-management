<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $company = Company::first();

        $isActive = $user->is_active;
        $isOwner = $company && $company->owner_id === $user->id;

        if ($isActive || $isOwner) {
            return $next($request);
        }

        $ignored_routes = ['company.create'];

        foreach ($ignored_routes as $route) {
            if (request()->routeIs($route)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
