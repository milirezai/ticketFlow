<?php

namespace App\Http\Controllers\Api\V1\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Access\UserAccessRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User\User;
use App\Services\Access\AccessService;

class UserAccessController extends Controller
{
    public function __construct(protected AccessService $access)
    {
        $this->middleware('can:assign.permissions');
    }

    public function assignRoles(User $user, UserAccessRequest $request): UserResource
    {
        $this->access->assignRolesToUser($user, $request->role_ids ?? []);
        return UserResource::make($user->load('roles'));
    }

    public function assignPermissions(User $user, UserAccessRequest $request): UserResource
    {
        $this->access->assignPermissionsToUser($user, $request->permission_ids ?? []);
        return UserResource::make($user->load(['roles', 'permissions']));
    }
}
