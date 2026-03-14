<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company = Company::first();

        // Allow company creation route
        if ($request->routeIs('company.create')) {

            // if compnay already exists then return to dashboard
            if ($company) {
                return redirect()->route('dashboard');
            }

            return $next($request);
        }

        if (! $company) {
            return redirect()->route('company.create');
        }

        return $next($request);
    }
}
