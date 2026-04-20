<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Web controller backing the users management UI and its JSON data endpoints.
 */
class UserController extends Controller
{
    public function __construct(private UserService $service) {}

    /** Render the users index page with the role picker. */
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('users/index', [
            'roles' => collect(UserRole::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
        ]);
    }

    /** Return paginated users with their roles as JSON for the data table. */
    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $users = $this->service->paginate($request->only(['search', 'role', 'per_page']));

        return ApiResponse::ok(UserResource::collection($users->loadMissing('roles')));
    }

    /** Create a new user and optionally assign a role. */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated());

        return ApiResponse::created(new UserResource($user->load('roles')), 'User created');
    }

    /** Update an existing user; re-hashes the password only when supplied. */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->service->update($user, $request->validated());

        return ApiResponse::ok(new UserResource($user->load('roles')), 'User updated');
    }

    /** Delete a user; the policy blocks self-deletion. */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $this->service->delete($user);

        return ApiResponse::ok(null, 'User deleted');
    }
}
