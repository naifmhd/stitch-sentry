<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrgSettingsController extends Controller
{
    public function show(Organization $organization): Response
    {
        Gate::authorize('view', $organization);

        $members = $organization->users()
            ->select(['users.id', 'users.name', 'users.email'])
            ->withPivot('role')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
            ]);

        return Inertia::render('organizations/Settings', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'members' => $members,
        ]);
    }
}
