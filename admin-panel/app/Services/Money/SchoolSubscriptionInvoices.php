<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformPayment;
use App\Models\Platform\PlatformPaymentAllocation;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Saas\PlatformBillingAccounts;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** School-only SE075 read contract. It never invokes or grants HQ billing authority. */
class SchoolSubscriptionInvoices
{
    public function __construct(private readonly CurrentSchool $school) {}

    public function index(User $actor): LengthAwarePaginator
    {
        $this->authorise($actor);

        return PlatformInvoice::query()->where('status', PlatformInvoice::ISSUED)->orderByDesc('issued_on')->orderByDesc('id')
            ->paginate(50, ['id', 'kind', 'reference', 'period_start', 'period_end', 'package_key', 'currency', 'issued_on', 'due_on', 'total_cents'])
            ->through(fn (PlatformInvoice $invoice): array => $this->summary($invoice));
    }

    public function show(User $actor, int $id): array
    {
        $this->authorise($actor);
        $invoice = PlatformInvoice::query()->where('status', PlatformInvoice::ISSUED)->findOrFail($id);
        // Resolve every source through the same tenant scope. Private notes, operator IDs,
        // draft diagnostics, contracts and platform audit events never enter this projection.
        $allocations = PlatformPaymentAllocation::query()->where('invoice_id', $invoice->id)->orderBy('id')->get();
        $payments = PlatformPayment::query()->whereIn('id', $allocations->pluck('payment_id')->filter())->get()->keyBy('id');
        $credits = PlatformInvoice::query()->where('status', PlatformInvoice::ISSUED)->whereIn('id', $allocations->pluck('credit_note_id')->filter())->get()->keyBy('id');

        return $this->summary($invoice) + [
            'school_name' => $this->school->school()->name,
            'school_status' => $this->school->school()->status,
            'subtotal_cents' => $invoice->subtotal_cents, 'vat_cents' => $invoice->vat_cents,
            'lines' => $invoice->lines->map(fn ($line): array => $line->only(['description', 'learners', 'unit_cents', 'amount_cents', 'vat_cents']))->all(),
            'allocations' => $allocations->map(function (PlatformPaymentAllocation $allocation) use ($payments, $credits): array {
                $payment = $payments->get($allocation->payment_id);
                $credit = $credits->get($allocation->credit_note_id);

                return ['reference' => $payment->bank_reference ?? $credit->reference ?? __('Recorded allocation'),
                    'kind' => $payment ? 'payment' : 'credit_note', 'date' => $payment?->received_on?->toDateString() ?? $credit?->issued_on?->toDateString(),
                    'amount_cents' => $allocation->amount_cents, 'reversed' => $allocation->reversed_at !== null];
            })->all(),
            'credit_notes' => PlatformInvoice::query()->where('status', PlatformInvoice::ISSUED)->where('credits_invoice_id', $invoice->id)->orderBy('id')->get()
                ->map(fn (PlatformInvoice $note): array => $this->summary($note))->all(),
        ];
    }

    private function summary(PlatformInvoice $invoice): array
    {
        $outstanding = $invoice->kind === PlatformInvoice::INVOICE ? app(PlatformBillingAccounts::class)->outstanding($invoice) : null;

        return ['id' => (int) $invoice->id, 'kind' => $invoice->kind, 'reference' => $invoice->reference,
            'period_start' => $invoice->period_start->toDateString(), 'period_end' => $invoice->period_end->toDateString(),
            'package' => $invoice->package_key, 'currency' => $invoice->currency, 'issued_on' => $invoice->issued_on?->toDateString(), 'due_on' => $invoice->due_on?->toDateString(),
            'total_cents' => $invoice->total_cents, 'outstanding_cents' => $outstanding,
            'payment_status' => $outstanding === null ? 'credit_note' : ($outstanding === 0 ? 'paid' : ($outstanding < $invoice->total_cents ? 'part_paid' : 'unpaid'))];
    }

    private function authorise(User $actor): void
    {
        abort_unless($this->school->isSet() && ! $this->school->isAcrossSchools() && ! $this->school->isImpersonating() && $this->school->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::query()->where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        abort_unless(SchoolMembership::query()->active()->where('user_id', $fresh->id)->where('role', 'Super Admin')->exists(), 403);
    }
}
