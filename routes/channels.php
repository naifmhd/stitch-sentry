<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('org.{orgId}', function (User $user, int $orgId): bool {
    // TODO: ISSUE 1.1 — validate user belongs to org via membership model
    return true;
});
