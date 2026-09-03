<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ImportPlanLimitException;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ImportBatch;
use App\Models\Seller;
use App\Services\Admin\HistoricalSalesImportService;
use App\Services\Tenant\HistoricalImportLimitService;
use App\Support\PortalAuthorization;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminImportController extends Controller
{
    public function __construct(
        private HistoricalSalesImportService $imports,
        private HistoricalImportLimitService $importLimits,
    ) {}

    public function index()
    {
        PortalAuthorization::requireAdmin();

        $batches = ImportBatch::query()
            ->with(['brand:id,brand_name', 'seller:id,name'])
            ->latest()
            ->paginate(20);

        return view('admin.pages.import.index', [
            'batches'     => $batches,
            'brands'      => Brand::query()->orderBy('brand_name')->get(['id', 'brand_name']),
            'sellers'     => Seller::query()->orderBy('name')->get(['id', 'name', 'brand_id']),
            'targets'     => HistoricalSalesImportService::TARGETS,
            'importQuota' => $this->importLimits->usageForUi(),
        ]);
    }

    public function sample()
    {
        PortalAuthorization::requireAdmin();

        return response($this->imports->sampleCsv(), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ledrix-historical-sales-sample.csv"',
        ]);
    }

    public function guide()
    {
        PortalAuthorization::requireAdmin();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('docs.historical-sales-import-guide', [
            'generatedAt' => now()->timezone(config('app.timezone'))->format('F j, Y'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('ledrix-historical-sales-import-guide.pdf');
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = PortalAuthorization::requireAdmin();
        $tenantId = TenantContext::require();

        $validated = $request->validate([
            'brand_id'             => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'seller_id'            => ['required', 'integer', Rule::exists('sellers', 'id')],
            'multi_brand'          => ['sometimes', 'boolean'],
            'enter_live_pipeline'  => ['sometimes', 'boolean'],
            'file'                 => ['required', 'file', 'max:10240'],
        ]);

        $multiBrand = $request->boolean('multi_brand');
        if (! $multiBrand && empty($validated['brand_id'])) {
            return back()->with('error', 'Select a brand, or tick multi-brand if the sheet has a brand_name column.')->withInput();
        }

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        $isCsv = in_array($extension, ['csv', 'txt'], true)
            || str_contains($mime, 'csv')
            || str_contains($mime, 'plain');

        if (! $isCsv) {
            return back()->with('error', 'Upload a CSV file. In Excel, use File → Save As → CSV (UTF-8).')->withInput();
        }

        try {
            $this->importLimits->assertCanUpload($tenantId, $file, $multiBrand);
        } catch (ImportPlanLimitException $e) {
            return back()->with('import_limit', $e->toFlash())->withInput();
        }

        $batch = ImportBatch::query()->create([
            'tenant_id'            => $tenantId,
            'admin_id'             => $admin->id,
            'plan_id_at_import'    => $this->importLimits->currentPlanId($tenantId),
            'brand_id'             => $multiBrand ? null : $validated['brand_id'],
            'seller_id'            => $validated['seller_id'],
            'multi_brand'          => $multiBrand,
            'enter_live_pipeline'  => $request->boolean('enter_live_pipeline'),
            'original_filename'    => $file->getClientOriginalName(),
            'status'               => 'uploaded',
        ]);

        $path = $file->storeAs('imports/'.$tenantId, $batch->id.'.csv');
        $batch->forceFill(['stored_path' => $path])->save();

        return redirect()
            ->route('admin.import.map', $batch)
            ->with('success', 'File uploaded. Map your columns — they do not need to match Ledrix names.');
    }

    public function map(ImportBatch $batch)
    {
        PortalAuthorization::requireAdmin();
        $this->guardBatch($batch);

        $parsed = $this->imports->parseStoredCsv($batch);
        $saved = $this->imports->savedMappingFor((int) $batch->tenant_id, (int) $batch->admin_id);
        $mapping = $batch->mapping ?: $this->imports->suggestMapping($parsed['headers'], $saved);

        return view('admin.pages.import.map', [
            'batch'    => $batch,
            'headers'  => $parsed['headers'],
            'samples'  => $parsed['samples'],
            'mapping'  => $mapping,
            'targets'  => HistoricalSalesImportService::TARGETS,
        ]);
    }

    public function saveMap(Request $request, ImportBatch $batch): RedirectResponse
    {
        $admin = PortalAuthorization::requireAdmin();
        $this->guardBatch($batch);

        $validated = $request->validate([
            'mapping'   => ['required', 'array'],
            'mapping.*' => ['required', 'string', Rule::in(array_keys(HistoricalSalesImportService::TARGETS))],
        ]);

        $mapping = $validated['mapping'];

        try {
            $this->importLimits->assertCanMap((int) $batch->tenant_id, (bool) $batch->multi_brand, $mapping);
        } catch (ImportPlanLimitException $e) {
            return redirect()
                ->route('admin.import.map', $batch)
                ->with('import_limit', $e->toFlash())
                ->withInput();
        }

        if ($batch->multi_brand && ! in_array('brand_name', $mapping, true)) {
            return redirect()
                ->route('admin.import.map', $batch)
                ->with('error', 'Multi-brand sheets need one column mapped to brand name.')
                ->withInput();
        }

        $required = ['lead.email'];
        $targets = array_values($mapping);
        foreach ($required as $need) {
            if (! in_array($need, $targets, true)) {
                return redirect()
                    ->route('admin.import.map', $batch)
                    ->with('error', 'Map at least one column to lead email. Unmapped columns are ignored.')
                    ->withInput();
            }
        }

        $this->imports->persistMapping((int) $batch->tenant_id, (int) $admin->id, $mapping);
        $batch->forceFill([
            'mapping' => $mapping,
            'status'  => 'mapped',
        ])->save();

        $this->imports->preview($batch, $mapping);

        return redirect()->route('admin.import.preview', $batch);
    }

    public function preview(ImportBatch $batch)
    {
        PortalAuthorization::requireAdmin();
        $this->guardBatch($batch);

        if (! is_array($batch->mapping) || $batch->mapping === []) {
            return redirect()->route('admin.import.map', $batch);
        }

        $plan = $this->imports->preview($batch, $batch->mapping, $batch->duplicate_strategy);

        return view('admin.pages.import.preview', [
            'batch' => $batch->fresh(),
            'plan'  => $plan,
        ]);
    }

    public function commit(Request $request, ImportBatch $batch): RedirectResponse
    {
        PortalAuthorization::requireAdmin();
        $this->guardBatch($batch);

        $validated = $request->validate([
            'duplicate_strategy' => ['required', Rule::in(HistoricalSalesImportService::DUPLICATE_STRATEGIES)],
        ]);

        try {
            $this->imports->commit($batch, $batch->mapping ?? [], $validated['duplicate_strategy']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.import.show', $batch)
            ->with('success', 'Import committed. Nothing was invented that was not in the sheet.');
    }

    public function show(ImportBatch $batch)
    {
        PortalAuthorization::requireAdmin();
        $this->guardBatch($batch);

        $batch->load(['brand:id,brand_name', 'seller:id,name']);

        return view('admin.pages.import.show', [
            'batch' => $batch,
        ]);
    }

    public function rollback(ImportBatch $batch): RedirectResponse
    {
        PortalAuthorization::requireAdmin();
        $this->guardBatch($batch);

        try {
            $this->imports->rollback($batch);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.import.show', $batch)
            ->with('success', 'Import rolled back. Imported payments, links, orders, and unused leads were removed.');
    }

    private function guardBatch(ImportBatch $batch): void
    {
        abort_unless((int) $batch->tenant_id === TenantContext::require(), 404);
    }
}
