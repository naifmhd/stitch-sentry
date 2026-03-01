<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $currentOrgId = session('current_organization_id');
        $currentOrg = null;

        if ($currentOrgId) {
            $currentOrg = $user->organizations()->find($currentOrgId);
        }

        if (! $currentOrg) {
            $currentOrg = $user->organizations()->first();

            if ($currentOrg) {
                session(['current_organization_id' => $currentOrg->id]);
            }
        }

        if ($currentOrg) {
            app()->instance(Organization::class, $currentOrg);
        }

        return $next($request);
    }
}
