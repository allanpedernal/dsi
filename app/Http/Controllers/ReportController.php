<?php

namespace App\Http\Controllers;

use App\Exports\SalesExport;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Services\ReportService;
use App\Support\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Web controller for sales report UI and its JSON / PDF / Excel / CSV exports.
 */
class ReportController extends Controller
{
    public function __construct(private ReportService $service) {}

    /** Render the sales report page with tenant-scoped customer filter options. */
    public function sales(Request $request): Response
    {
        abort_unless($request->user()?->can('reports.view'), 403);
        $user = $request->user();

        return Inertia::render('reports/sales', [
            'tenantScoped' => $user->isTenantScoped(),
            'customers' => $user->isTenantScoped() ? [] : Customer::orderBy('last_name')->orderBy('first_name')
                ->get(['id', 'code', 'first_name', 'last_name'])
                ->map(fn ($c) => ['id' => $c->id, 'label' => trim("{$c->first_name} {$c->last_name}").' ('.$c->code.')'])
                ->values()
                ->all(),
        ]);
    }

    /** Return paginated sales rows plus aggregate totals as JSON for the report table. */
    public function salesData(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $filters = $request->only(['from', 'to', 'status', 'user_id', 'customer_id']);
        $sales = $this->service->salesQuery($filters)->paginate(min(100, (int) $request->get('per_page', 25)));
        $aggregate = $this->service->salesAggregate($filters);

        $payload = SaleResource::collection($sales)->response()->getData(true);
        $payload['aggregate'] = $aggregate;

        return ApiResponse::ok($payload);
    }

    /** Stream the filtered sales report rendered as a landscape A4 PDF download. */
    public function salesPdf(Request $request): HttpResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $filters = $request->only(['from', 'to', 'status', 'user_id', 'customer_id']);
        $sales = $this->service->salesQuery($filters)->limit(2000)->get();
        $aggregate = $this->service->salesAggregate($filters);

        $pdf = Pdf::loadView('reports.sales', [
            'sales' => $sales,
            'aggregate' => $aggregate,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sales-report-'.now()->format('Ymd-His').'.pdf');
    }

    /** Stream the filtered sales report as an Excel (xlsx) or CSV download. */
    public function salesExcel(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $format = $request->get('format', 'xlsx') === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';
        $filename = 'sales-report-'.now()->format('Ymd-His').'.'.$ext;

        return Excel::download(new SalesExport($request->only(['from', 'to', 'status', 'user_id', 'customer_id'])), $filename, $format);
    }
}
