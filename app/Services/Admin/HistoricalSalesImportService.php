<?php

namespace App\Services\Admin;

use App\Jobs\VerifyImportedContactJob;
use App\Models\Brand;
use App\Models\Client;
use App\Models\ImportBatch;
use App\Models\ImportColumnMap;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Seller;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class HistoricalSalesImportService
{
    public const TARGETS = [
        'ignore'                      => 'Ignore this column',
        'lead.name'                   => 'Lead — name',
        'lead.email'                  => 'Lead — email',
        'lead.phone'                  => 'Lead — phone',
        'order.amount'                => 'Order — amount',
        'order.status'                => 'Order — status',
        'pay_link.provider'           => 'Payment link — provider',
        'pay_link.provider_link_id'   => 'Payment link — provider ID',
        'payment.amount_paid'         => 'Payment — amount paid',
        'payment.paid_at'             => 'Payment — paid at',
        'payment.provider_payment_id' => 'Payment — provider payment ID',
        'brand_name'                  => 'Brand name (multi-brand sheets)',
    ];

    public const DUPLICATE_STRATEGIES = ['merge', 'skip', 'create_new'];

    public const KNOWN_PROVIDERS = ['stripe', 'paypal'];

    public function sampleCsv(): string
    {
        $headers = [
            'name',
            'email',
            'phone',
            'brand_name',
            'order_amount',
            'order_status',
            'pay_link_provider',
            'provider_link_id',
            'amount_paid',
            'paid_at',
            'provider_payment_id',
        ];

        $rows = [
            // Contacts only → lead
            ['Alice Contact', 'alice.contact@example.com', '5551112222', 'Acme Agency', '', '', '', '', '', '', ''],
            // Amount, no payment → lead + order
            ['Bob Invoice', 'bob.invoice@example.com', '5553334444', 'Acme Agency', '1500.00', 'draft', '', '', '', '', ''],
            // Cash / check → lead + order + payment, both provider IDs NULL
            ['Cara Cash', 'cara.cash@example.com', '5555556666', 'Acme Agency', '800.00', 'paid', 'cash', '', '800.00', '2024-03-15', ''],
            // Real Stripe IDs from the source — never invent these
            ['Dan Stripe', 'dan.stripe@example.com', '5557778888', 'Acme Agency', '2000.00', 'paid', 'stripe', 'cs_test_real_from_sheet', '2000.00', '2024-04-01', 'pi_test_real_from_sheet'],
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, string>  $saved
     * @return array<string, string>
     */
    public function suggestMapping(array $headers, array $saved = []): array
    {
        $suggested = [];

        foreach ($headers as $header) {
            if (isset($saved[$header]) && array_key_exists($saved[$header], self::TARGETS)) {
                $suggested[$header] = $saved[$header];
                continue;
            }

            $suggested[$header] = $this->guessTarget($header);
        }

        return $suggested;
    }

    public function savedMappingFor(int $tenantId, int $adminId): array
    {
        $row = ImportColumnMap::query()
            ->where('tenant_id', $tenantId)
            ->where('admin_id', $adminId)
            ->first();

        return is_array($row?->mapping) ? $row->mapping : [];
    }

    public function persistMapping(int $tenantId, int $adminId, array $mapping): void
    {
        ImportColumnMap::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'admin_id' => $adminId],
            ['mapping' => $mapping],
        );
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>, samples: array<string, list<string>>}
     */
    public function parseStoredCsv(ImportBatch $batch): array
    {
        $path = $this->absolutePath($batch);
        if (! is_readable($path)) {
            throw new RuntimeException('The uploaded file is no longer available.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Could not read the uploaded CSV.');
        }

        $rawHeaders = fgetcsv($handle);
        if (! is_array($rawHeaders) || $rawHeaders === [null] || $rawHeaders === false) {
            fclose($handle);
            throw new RuntimeException('The CSV has no header row.');
        }

        $headers = $this->normalizeHeaders($rawHeaders);
        $rows = [];
        $samples = array_fill_keys($headers, []);

        while (($cols = fgetcsv($handle)) !== false) {
            if ($cols === [null] || $this->rowIsEmpty($cols)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $header) {
                $value = trim((string) ($cols[$i] ?? ''));
                $assoc[$header] = $value;
                if ($value !== '' && count($samples[$header]) < 3) {
                    $samples[$header][] = $value;
                }
            }
            $rows[] = $assoc;
        }

        fclose($handle);

        return compact('headers', 'rows', 'samples');
    }

    /**
     * Dry-run. Does not write CRM rows.
     *
     * @param  array<string, string>  $mapping
     * @return array<string, mixed>
     */
    public function preview(ImportBatch $batch, array $mapping, ?string $duplicateStrategy = null): array
    {
        $plan = $this->buildPlan($batch, $mapping, $duplicateStrategy ?? 'skip');

        $batch->forceFill([
            'mapping'   => $mapping,
            'summary'   => $plan['summary'],
            'status'    => $batch->status === 'committed' ? $batch->status : 'previewed',
            'row_count' => $plan['summary']['rows'],
        ])->save();

        return $plan;
    }

    /**
     * @param  array<string, string>  $mapping
     * @return array<string, mixed>
     */
    public function commit(ImportBatch $batch, array $mapping, string $duplicateStrategy): array
    {
        if ($batch->status === 'committed') {
            throw new RuntimeException('This import has already been committed.');
        }

        if (! in_array($duplicateStrategy, self::DUPLICATE_STRATEGIES, true)) {
            throw new RuntimeException('Choose merge, skip, or create new for duplicate contacts.');
        }

        $plan = $this->buildPlan($batch, $mapping, $duplicateStrategy);

        if (($plan['summary']['duplicates'] ?? 0) > 0 && $duplicateStrategy === '') {
            throw new RuntimeException('This sheet has duplicate contacts. Choose merge, skip, or create new.');
        }

        $createdLeadIds = [];

        DB::transaction(function () use ($batch, $mapping, $duplicateStrategy, $plan, &$createdLeadIds) {
            foreach ($plan['actions'] as $action) {
                if (($action['decision'] ?? '') === 'reject' || ($action['decision'] ?? '') === 'skip') {
                    continue;
                }

                $this->applyAction($batch, $action, $createdLeadIds);
            }

            $batch->forceFill([
                'mapping'             => $mapping,
                'duplicate_strategy'  => $duplicateStrategy,
                'summary'             => $plan['summary'],
                'decisions'           => $plan['row_logs'],
                'status'              => 'committed',
                'committed_at'        => now(),
                'row_count'           => $plan['summary']['rows'],
            ])->save();
        });

        foreach (array_unique($createdLeadIds) as $leadId) {
            VerifyImportedContactJob::dispatch((int) $leadId);
        }

        return $plan;
    }

    public function rollback(ImportBatch $batch): void
    {
        if ($batch->status !== 'committed') {
            throw new RuntimeException('Only a committed import can be rolled back.');
        }

        DB::transaction(function () use ($batch) {
            Payment::withoutEvents(function () use ($batch) {
                Payment::query()->where('import_batch_id', $batch->id)->withTrashed()->forceDelete();
            });
            PaymentLink::withoutEvents(function () use ($batch) {
                PaymentLink::query()->where('import_batch_id', $batch->id)->withTrashed()->forceDelete();
            });
            Order::withoutEvents(function () use ($batch) {
                Order::query()->where('import_batch_id', $batch->id)->withTrashed()->forceDelete();
            });

            $leads = Lead::withoutGlobalScopes()->where('import_batch_id', $batch->id)->withTrashed()->get();
            foreach ($leads as $lead) {
                $otherOrders = Order::query()
                    ->where('lead_id', $lead->id)
                    ->where(function ($q) use ($batch) {
                        $q->whereNull('import_batch_id')
                            ->orWhere('import_batch_id', '!=', $batch->id);
                    })
                    ->count();

                if ($otherOrders === 0) {
                    LeadAssignment::withoutEvents(function () use ($lead) {
                        LeadAssignment::query()->where('lead_id', $lead->id)->withTrashed()->forceDelete();
                    });
                    Lead::withoutEvents(fn () => $lead->forceDelete());
                } else {
                    $lead->forceFill([
                        'import_batch_id' => null,
                        'source'          => $lead->source === 'import' ? null : $lead->source,
                    ])->save();
                }
            }

            $batch->forceFill([
                'status'         => 'rolled_back',
                'rolled_back_at' => now(),
            ])->save();
        });
    }

    /**
     * @param  array<string, string>  $mapping
     * @return array<string, mixed>
     */
    public function buildPlan(ImportBatch $batch, array $mapping, string $duplicateStrategy): array
    {
        $parsed = $this->parseStoredCsv($batch);
        $brands = $this->brandLookup();
        $seller = $this->requireSeller($batch);

        $summary = [
            'rows'                    => count($parsed['rows']),
            'leads_create'            => 0,
            'leads_matched'           => 0,
            'leads_skipped'           => 0,
            'orders'                  => 0,
            'pay_links_real'          => 0,
            'pay_links_unknown'       => 0,
            'payments'                => 0,
            'payments_without_link'   => 0,
            'review_flags'            => 0,
            'unmatched_brands'        => 0,
            'duplicates'              => 0,
            'rejected'                => 0,
        ];

        $unmatchedBrandRows = [];
        $duplicateRows = [];
        $rowLogs = [];
        $actions = [];
        $issues = [];

        foreach ($parsed['rows'] as $index => $row) {
            $line = $index + 2; // header is line 1
            $mapped = $this->applyMapping($row, $mapping);
            $brandResolution = $this->resolveBrand($batch, $mapped, $brands);

            if ($brandResolution['error']) {
                $summary['unmatched_brands']++;
                $summary['rejected']++;
                $unmatchedBrandRows[] = [
                    'row'        => $line,
                    'brand_name' => $mapped['brand_name'] ?? '',
                    'reason'     => $brandResolution['error'],
                ];
                $log = $this->rowLog($line, 'reject', $brandResolution['error'], $mapped);
                $rowLogs[] = $log;
                $issues[] = $log;
                continue;
            }

            $email = $this->normalizeEmail($mapped['lead.email'] ?? '');
            $phone = $this->normalizePhone($mapped['lead.phone'] ?? '');
            $name  = trim((string) ($mapped['lead.name'] ?? ''));

            if ($email === '') {
                $summary['rejected']++;
                $log = $this->rowLog($line, 'reject', 'Email is required to create a lead. This row was not imported.', $mapped);
                $rowLogs[] = $log;
                $issues[] = $log;
                continue;
            }

            if ($name === '') {
                $name = $email;
            }

            $existing = $this->findExistingLead((int) $brandResolution['brand']->id, $email, $phone);
            $decision = 'create';
            $reason = 'New contact.';

            if ($existing) {
                $summary['duplicates']++;
                $duplicateRows[] = [
                    'row'            => $line,
                    'email'          => $email,
                    'phone'          => $phone,
                    'existing_id'    => $existing->id,
                    'existing_name'  => $existing->name,
                ];

                if ($duplicateStrategy === 'skip') {
                    $decision = 'skip';
                    $reason = 'Duplicate contact skipped (same brand + email or phone).';
                    $summary['leads_skipped']++;
                } elseif ($duplicateStrategy === 'merge') {
                    $decision = 'merge';
                    $reason = 'Duplicate contact will merge onto the existing lead.';
                    $summary['leads_matched']++;
                } else {
                    $decision = 'create';
                    $reason = 'Duplicate contact will still create a new lead.';
                    $summary['leads_create']++;
                }
            } else {
                $summary['leads_create']++;
            }

            $orderAmount = $this->parseCents($mapped['order.amount'] ?? '');
            $paidAmount  = $this->parseCents($mapped['payment.amount_paid'] ?? '');
            $provider    = $this->normalizeProvider($mapped['pay_link.provider'] ?? '');
            $linkId      = trim((string) ($mapped['pay_link.provider_link_id'] ?? ''));
            $paymentId   = trim((string) ($mapped['payment.provider_payment_id'] ?? ''));
            $paidAt      = $this->parseDate($mapped['payment.paid_at'] ?? '');
            $orderStatus = trim((string) ($mapped['order.status'] ?? ''));

            $willOrder = $orderAmount !== null && $orderAmount > 0;
            if (! $willOrder && $paidAmount !== null && $paidAmount > 0) {
                $orderAmount = $paidAmount;
                $willOrder = true;
            }

            $knownProvider = in_array($provider, self::KNOWN_PROVIDERS, true);
            $offlineProvider = $this->isOfflineProvider($provider);
            $willPayLink = $willOrder && $knownProvider && $linkId !== '';
            $unknownPayLink = ($linkId !== '' && ! $willPayLink)
                || ($provider !== null && ! $knownProvider && ! $offlineProvider);
            $willPayment = $paidAmount !== null && $paidAmount > 0;

            $needsReview = $unknownPayLink
                || ($linkId !== '' && $willPayLink)
                || $paymentId !== ''
                || ($knownProvider && $willPayment && $paymentId === '');

            if ($decision === 'skip') {
                $log = $this->rowLog($line, 'skip', $reason, $mapped, $existing?->id);
                $rowLogs[] = $log;
                continue;
            }

            if ($willOrder) {
                $summary['orders']++;
            }
            if ($willPayLink) {
                $summary['pay_links_real']++;
            }
            if ($unknownPayLink) {
                $summary['pay_links_unknown']++;
            }
            if ($willPayment) {
                $summary['payments']++;
                if (! $willPayLink) {
                    $summary['payments_without_link']++;
                }
            }
            if ($needsReview && ($willPayLink || $willPayment || $unknownPayLink)) {
                $summary['review_flags']++;
            }

            $action = [
                'row'            => $line,
                'decision'       => $decision,
                'reason'         => $reason,
                'brand_id'       => (int) $brandResolution['brand']->id,
                'seller_id'      => (int) $seller->id,
                'existing_id'    => $existing?->id,
                'name'           => $name,
                'email'          => $email,
                'phone'          => $phone !== '' ? $phone : null,
                'order_amount'   => $willOrder ? $orderAmount : null,
                'order_status'   => $orderStatus !== '' ? $orderStatus : null,
                'paid_amount'    => $willPayment ? $paidAmount : null,
                'paid_at'        => $paidAt,
                'provider'       => $knownProvider ? $provider : null,
                'raw_provider'   => $provider,
                'link_id'        => $willPayLink ? $linkId : null,
                'payment_id'     => $paymentId !== '' ? $paymentId : null,
                'will_order'     => $willOrder,
                'will_pay_link'  => $willPayLink,
                'will_payment'   => $willPayment,
                'needs_review'   => $needsReview,
                'unknown_link'   => $unknownPayLink,
            ];

            $actions[] = $action;
            $rowLogs[] = $this->rowLog($line, $decision, $reason, $mapped, $existing?->id, [
                'order'    => $willOrder,
                'pay_link' => $willPayLink,
                'payment'  => $willPayment,
            ]);
        }

        return [
            'headers'               => $parsed['headers'],
            'samples'               => $parsed['samples'],
            'summary'               => $summary,
            'unmatched_brand_rows'  => $unmatchedBrandRows,
            'duplicate_rows'        => $duplicateRows,
            'row_logs'              => $rowLogs,
            'issues'                => array_slice($issues, 0, 50),
            'actions'               => $actions,
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  list<int>  $createdLeadIds
     */
    private function applyAction(ImportBatch $batch, array $action, array &$createdLeadIds): void
    {
        $tenantId = (int) ($batch->tenant_id ?: TenantContext::require());
        $lead = null;

        if ($action['decision'] === 'merge' && ! empty($action['existing_id'])) {
            $lead = Lead::query()->find($action['existing_id']);
        }

        if (! $lead) {
            $client = $this->findOrCreateClient($tenantId, $action);
            $status = 'new';
            if ($action['will_payment']) {
                $status = 'first_paid';
            } elseif ($action['will_order']) {
                $status = 'qualified';
            }

            $lead = Lead::query()->create([
                'tenant_id'       => $tenantId,
                'seller_id'       => $action['seller_id'],
                'brand_id'        => $action['brand_id'],
                'client_id'       => $client->id,
                'name'            => $action['name'],
                'email'           => $action['email'],
                'phone'           => $action['phone'],
                'status'          => $status,
                'source'          => 'import',
                'import_batch_id' => $batch->id,
                'converted_at'    => $action['will_payment'] ? ($action['paid_at'] ?? now()) : null,
            ]);

            $createdLeadIds[] = $lead->id;

            if ($batch->enter_live_pipeline) {
                LeadAssignment::query()->create([
                    'tenant_id'     => $tenantId,
                    'lead_id'       => $lead->id,
                    'assigned_to'   => $action['seller_id'],
                    'assigned_role' => 'front_seller',
                    'assigned_by'   => $batch->admin_id,
                    'assigned_at'   => now(),
                    'status'        => 'assigned',
                ]);
            }
        }

        if (! $action['will_order']) {
            return;
        }

        $clientId = $lead->client_id ?: $this->findOrCreateClient($tenantId, $action)->id;
        if (! $lead->client_id) {
            $lead->forceFill(['client_id' => $clientId])->save();
        }

        $paidCents = (int) ($action['paid_amount'] ?? 0);
        $unitCents = (int) $action['order_amount'];

        $order = Order::query()->create([
            'tenant_id'       => $tenantId,
            'lead_id'         => $lead->id,
            'brand_id'        => $action['brand_id'],
            'seller_id'       => $action['seller_id'],
            'client_id'       => $clientId,
            'service_name'    => 'Imported sale',
            'currency'        => 'USD',
            'unit_amount'     => $unitCents,
            'amount_paid'     => $paidCents,
            'front_seller_id' => $action['seller_id'],
            'paid_at'         => $paidCents > 0 ? ($action['paid_at'] ?? now()) : null,
            'first_paid_at'   => $paidCents > 0 ? ($action['paid_at'] ?? now()) : null,
            'buyer_name'      => $action['name'],
            'buyer_email'     => $action['email'],
            'source'          => 'import',
            'import_batch_id' => $batch->id,
        ]);

        $payLink = null;
        if ($action['will_pay_link']) {
            $payLink = PaymentLink::query()->create([
                'tenant_id'            => $tenantId,
                'lead_id'              => $lead->id,
                'seller_id'            => $action['seller_id'],
                'brand_id'             => $action['brand_id'],
                'client_id'            => $clientId,
                'order_id'             => $order->id,
                'service_name'         => 'Imported sale',
                'currency'             => 'USD',
                'provider'             => $action['provider'],
                'unit_amount'          => $unitCents,
                'order_total_snapshot' => $unitCents,
                'provider_session_id'  => $action['link_id'],
                'token'                => 'imp-'.Str::uuid()->toString(),
                'status'               => $paidCents > 0 ? 'paid' : 'expired',
                'is_active_link'       => false,
                'paid_at'              => $paidCents > 0 ? ($action['paid_at'] ?? null) : null,
                'source'               => 'import',
                'import_batch_id'      => $batch->id,
                'needs_review'         => (bool) $action['needs_review'],
            ]);
        }

        if ($action['will_payment']) {
            Payment::query()->create([
                'tenant_id'                   => $tenantId,
                'order_id'                    => $order->id,
                'payment_link_id'             => $payLink?->id,
                'amount'                      => $paidCents,
                'currency'                    => 'USD',
                'status'                      => 'succeeded',
                'provider'                    => $action['provider'],
                'provider_payment_intent_id'  => $action['payment_id'],
                'seller_id'                   => $action['seller_id'],
                'front_seller_id'             => $action['seller_id'],
                'paid_at'                     => $action['paid_at'],
                'source'                      => 'import',
                'import_batch_id'             => $batch->id,
                'needs_review'                => (bool) $action['needs_review'],
                'payload'                     => [
                    'import' => true,
                    'row'    => $action['row'],
                ],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function findOrCreateClient(int $tenantId, array $action): Client
    {
        $existing = Client::query()
            ->where('tenant_id', $tenantId)
            ->where('email', $action['email'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return Client::query()->create([
            'tenant_id' => $tenantId,
            'name'      => $action['name'],
            'email'     => $action['email'],
            'phone'     => $action['phone'],
            'status'    => 'Active',
            'meta'      => ['portal_access' => false, 'source' => 'import'],
        ]);
    }

    private function requireSeller(ImportBatch $batch): Seller
    {
        $seller = Seller::query()->find($batch->seller_id);
        if (! $seller) {
            throw new RuntimeException('Assign a seller before importing. Leads require an owner.');
        }

        return $seller;
    }

    /**
     * @return array<string, Brand>
     */
    private function brandLookup(): array
    {
        $lookup = [];
        foreach (Brand::query()->get() as $brand) {
            $lookup[Str::lower(trim((string) $brand->brand_name))] = $brand;
        }

        return $lookup;
    }

    /**
     * @param  array<string, string>  $mapped
     * @param  array<string, Brand>  $brands
     * @return array{brand: ?Brand, error: ?string}
     */
    private function resolveBrand(ImportBatch $batch, array $mapped, array $brands): array
    {
        if ($batch->multi_brand) {
            $name = trim((string) ($mapped['brand_name'] ?? ''));
            if ($name === '') {
                return ['brand' => null, 'error' => 'Multi-brand import requires a brand_name value on this row.'];
            }
            $key = Str::lower($name);
            if (! isset($brands[$key])) {
                return ['brand' => null, 'error' => 'No brand named “'.$name.'” exists. Row was not imported.'];
            }

            return ['brand' => $brands[$key], 'error' => null];
        }

        $brand = Brand::query()->find($batch->brand_id);
        if (! $brand) {
            return ['brand' => null, 'error' => 'Select a brand before importing.'];
        }

        return ['brand' => $brand, 'error' => null];
    }

    private function findExistingLead(int $brandId, string $email, string $phone): ?Lead
    {
        $byEmail = Lead::query()
            ->where('brand_id', $brandId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($byEmail) {
            return $byEmail;
        }

        if ($phone === '') {
            return null;
        }

        return Lead::query()
            ->where('brand_id', $brandId)
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?",
                [$phone]
            )
            ->first();
    }

    private function isOfflineProvider(?string $provider): bool
    {
        return in_array($provider, ['cash', 'check', 'cheque', 'bank', 'wire', 'transfer', 'other', 'offline', 'none', 'n/a', 'na'], true);
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, string>  $mapping
     * @return array<string, string>
     */
    private function applyMapping(array $row, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $header => $target) {
            if ($target === '' || $target === 'ignore' || ! array_key_exists($target, self::TARGETS)) {
                continue;
            }
            $mapped[$target] = trim((string) ($row[$header] ?? ''));
        }

        return $mapped;
    }

    /**
     * @param  list<string>  $cols
     */
    private function rowIsEmpty(array $cols): bool
    {
        foreach ($cols as $col) {
            if (trim((string) $col) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $raw
     * @return list<string>
     */
    private function normalizeHeaders(array $raw): array
    {
        $headers = [];
        foreach ($raw as $i => $header) {
            $header = (string) $header;
            if ($i === 0) {
                $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
            }
            $header = trim($header);
            $headers[] = $header !== '' ? $header : 'column_'.($i + 1);
        }

        return $headers;
    }

    private function guessTarget(string $header): string
    {
        $key = Str::lower(preg_replace('/[^a-z0-9]+/i', ' ', $header) ?? $header);
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? $key);

        $rules = [
            'payment.amount_paid'         => ['amount paid', 'paid amount', 'payment amount'],
            'payment.paid_at'             => ['paid at', 'paid date', 'payment date', 'date paid'],
            'payment.provider_payment_id' => ['provider payment id', 'payment intent', 'pi id', 'txn id', 'transaction id'],
            'pay_link.provider_link_id'   => ['provider link id', 'session id', 'checkout session', 'cs id', 'payment link id'],
            'pay_link.provider'           => ['pay link provider', 'payment provider', 'provider', 'gateway', 'processor'],
            'order.amount'                => ['order amount', 'order total', 'deal value', 'amount', 'total', 'price', 'value'],
            'order.status'                => ['order status', 'deal status'],
            'lead.email'                  => ['email address', 'client email', 'customer email', 'e mail', 'email'],
            'lead.phone'                  => ['phone', 'mobile', 'cell', 'whatsapp', 'telephone'],
            'lead.name'                   => ['full name', 'client name', 'customer name', 'customer', 'name', 'contact'],
            'brand_name'                  => ['brand name', 'brand', 'llc'],
        ];

        $pairs = [];
        foreach ($rules as $target => $needles) {
            foreach ($needles as $needle) {
                $pairs[] = [$needle, $target];
            }
        }

        usort($pairs, fn (array $a, array $b) => strlen($b[0]) <=> strlen($a[0]));

        foreach ($pairs as [$needle, $target]) {
            if ($key === $needle) {
                return $target;
            }
        }

        foreach ($pairs as [$needle, $target]) {
            if (str_contains($key, $needle)) {
                return $target;
            }
        }

        return 'ignore';
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }

    private function normalizeProvider(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        if (in_array($value, self::KNOWN_PROVIDERS, true)) {
            return $value;
        }

        if (str_contains($value, 'stripe')) {
            return 'stripe';
        }
        if (str_contains($value, 'paypal') || $value === 'pp') {
            return 'paypal';
        }

        return $value;
    }

    private function parseCents(?string $raw): ?int
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $raw = str_replace([',', '$', '€', '£', ' '], '', $raw);
        if (! is_numeric($raw)) {
            return null;
        }

        return (int) round(((float) $raw) * 100);
    }

    private function parseDate(?string $raw): ?Carbon
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, string>  $mapped
     * @param  array<string, bool>  $created
     * @return array<string, mixed>
     */
    private function rowLog(int $line, string $decision, string $reason, array $mapped, ?int $leadId = null, array $created = []): array
    {
        return [
            'row'      => $line,
            'decision' => $decision,
            'reason'   => $reason,
            'lead_id'  => $leadId,
            'email'    => $mapped['lead.email'] ?? '',
            'created'  => $created,
        ];
    }

    private function absolutePath(ImportBatch $batch): string
    {
        $stored = (string) $batch->stored_path;
        if ($stored === '') {
            throw new RuntimeException('This import has no stored file.');
        }

        return Storage::path($stored);
    }
}
