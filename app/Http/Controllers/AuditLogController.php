<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Customer;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(private AuditService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('audit.view'), 403);
        $user = $request->user();

        return Inertia::render('audit-log/index', [
            'tenantScoped' => $user->isTenantScoped(),
            'customers' => $user->isTenantScoped() ? [] : Customer::orderBy('last_name')->orderBy('first_name')
                ->get(['id', 'code', 'first_name', 'last_name'])
                ->map(fn ($c) => ['id' => $c->id, 'label' => trim("{$c->first_name} {$c->last_name}").' ('.$c->code.')'])
                ->values()
                ->all(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('audit.view'), 403);

        $rows = $this->service->paginate($request->only(['search', 'source', 'log_name', 'customer_id', 'from', 'to', 'per_page']));

        return ApiResponse::ok(ActivityResource::collection($rows));
    }
}
