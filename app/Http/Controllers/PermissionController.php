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

/**
 * Web controller backing the permissions management UI and its JSON data endpoints.
 */
class PermissionController extends Controller
{
    public function __construct(private PermissionService $service) {}

    /** Render the permissions index page. */
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('permissions.view'), 403);

        return Inertia::render('permissions/index');
    }

    /** Return paginated permissions as JSON for the data table. */
    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('permissions.view'), 403);

        return ApiResponse::ok(PermissionResource::collection(
            $this->service->paginate($request->only(['search', 'per_page']))
        ));
    }

    /** Create a new permission. */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->service->create($request->validated());

        return ApiResponse::created(new PermissionResource($permission), 'Permission created');
    }

    /** Update a permission's name and/or guard. */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission = $this->service->update($permission, $request->validated());

        return ApiResponse::ok(new PermissionResource($permission), 'Permission updated');
    }

    /** Delete a permission, blocking removal while it is still attached to any roles. */
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
