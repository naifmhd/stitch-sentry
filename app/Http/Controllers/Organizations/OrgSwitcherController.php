<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrgSwitcherController extends Controller
{
    /**
     * Switch the authenticated user's current organization.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'organization_id' => ['required', 'integer'],
        ]);

        $organization = Organization::findOrFail($request->integer('organization_id'));

        Gate::authorize('view', $organization);

        session(['current_organization_id' => $organization->id]);

        return redirect()->back();
    }
}
