<?php

namespace App\Http\Controllers\Api\V1\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Access\RoleRequest;
use App\Http\Resources\Api\V1\Access\RoleResource;
use App\Models\Access\Role;
use App\Services\Access\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    public function __construct(protected AccessService $access)
    {
        $this->middleware('can:manage.roles');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection(Role::with('permissions')->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->access->createRole($request->validated(), $request->permissions ?? []);
        return RoleResource::make($role->load('permissions'))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): RoleResource
    {
        return RoleResource::make($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, Role $role): RoleResource
    {
        $this->access->updateRole($role, $request->validated(), $request->permissions);
        return RoleResource::make($role->load('permissions'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): Response
    {
        $role->delete();
        return response()->noContent();
    }

    public function syncPermissions(RoleRequest $request, Role $role): RoleResource
    {
        $this->access->syncRolePermissions($role, $request->permissions ?? []);
        return RoleResource::make($role->load('permissions'));
    }
}
