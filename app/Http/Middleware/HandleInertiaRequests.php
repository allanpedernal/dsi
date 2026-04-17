<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /** @var string */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames()->all() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name')->all() ?? [],
                'tenant_scoped' => (bool) $user?->isTenantScoped(),
                'customer_ids' => $user ? $user->customerIds() : [],
            ],
            'unreadNotifications' => fn () => $user?->unreadNotifications()->limit(20)->get()->map(fn ($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'created_at' => $n->created_at?->diffForHumans(),
            ]),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
