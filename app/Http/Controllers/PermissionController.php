<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(private PermissionService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('permissions.view'), 403);

        return Inertia::render('permissions/index');
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('permissions.view'), 403);

        return ApiResponse::ok(PermissionResource::collection(
            $this->service->paginate($request->only(['search', 'per_page']))
        ));
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->service->create($request->validated());

        return ApiResponse::created(new PermissionResource($permission), 'Permission created');
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission = $this->service->update($permission, $request->validated());

        return ApiResponse::ok(new PermissionResource($permission), 'Permission updated');
    }

    public function destroy(Request $request, Permission $permission): JsonResponse
    {
        abort_unless($request->user()?->can('permissions.delete'), 403);

        $rolesCount = $permission->roles()->count();
        if ($rolesCount > 0) {
            return ApiResponse::error(
                "Cannot delete permission \"{$permission->name}\" — it is assigned to {$rolesCount} role(s). Revoke it first.",
                422,
            );
        }

        $this->service->delete($permission);

        return ApiResponse::ok(null, 'Permission deleted');
    }
}
