<?php

namespace App\Http\Controllers\Api\V1\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Access\PermissionRequest;
use App\Http\Resources\Api\V1\Access\PermissionResource;
use App\Models\Access\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    public function __construct()
    {
        Gate::authorize('manage-permissions');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return PermissionResource::collection(Permission::paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());
        return PermissionResource::make($permission)->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): PermissionResource
    {
        return PermissionResource::make($permission);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PermissionRequest $request, Permission $permission): PermissionResource
    {
        $permission->update($request->validated());
        return PermissionResource::make($permission);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission): Response
    {
        $permission->delete();
        return response()->noContent();
    }
}
