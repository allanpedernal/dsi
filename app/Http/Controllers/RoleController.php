<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Web controller backing the roles-and-permissions UI and its JSON data endpoints.
 */
class RoleController extends Controller
{
    /** @var array<int, string> Built-in roles that cannot be deleted. */
    private const PROTECTED_ROLES = [
        UserRole::Admin->value,
        UserRole::Manager->value,
        UserRole::Customer->value,
    ];

    public function __construct(private RoleService $service) {}

    /** Render the roles management page. */
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('roles.view'), 403);

        return Inertia::render('roles/index');
    }

    /** Return all roles, all permissions, and protected-role names for the editor UI. */
    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('roles.view'), 403);

        return ApiResponse::ok([
            'roles' => RoleResource::collection($this->service->list())->resolve(),
            'permissions' => $this->service->permissions()->pluck('name')->all(),
            'protected' => self::PROTECTED_ROLES,
        ]);
    }

    /** Create a new role on the web guard. */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->service->create($request->validated('name'));

        return ApiResponse::created(new RoleResource($role), 'Role created');
    }

    /** Sync the list of permissions attached to a role. */
    public function update(UpdateRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $role = $this->service->syncPermissions($role, $request->validated('permissions') ?? []);

        return ApiResponse::ok(new RoleResource($role), 'Permissions updated');
    }

    /** Delete a role, blocking removal of built-in roles or roles still assigned to users. */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()?->can('roles.delete'), 403);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return ApiResponse::error('Built-in roles cannot be deleted.', 422);
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return ApiResponse::error(
                "Cannot delete role \"{$role->name}\" — it is assigned to {$usersCount} user(s). Reassign them first.",
                422,
            );
        }

        $this->service->delete($role);

        return ApiResponse::ok(null, 'Role deleted');
    }
}
