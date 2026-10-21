@php
    $currencySettings = getSettings('currency_symbol');
    $currencySymbol = ($currencySettings['currency_symbol'] ?? '') !== '' ? $currencySettings['currency_symbol'] : 'R';
@endphp
<div class="row">
    <div class="col-md-12">

        <div class="report-section-title">{{ __('fees_history') }}</div>

        <div class="table-responsive">
            <table
                class="table"
                id="table_list"
                data-toggle="table"
                data-pagination="true"
                data-search="true"
                data-show-refresh="true"
                data-show-columns="true"
                data-mobile-responsive="true"
                data-page-list="[5,10,20,50]"
                data-escape="false">

                <thead>
                    <tr>
                        <th>{{ __('no.') }}</th>
                        <th>{{ __('amount') }}</th>
                        <th class="text-center">{{ __('date') }}</th>
                        <th class="text-center">{{ __('mode') }}</th>
                        <th class="text-center">{{ __('status') }}</th>
                        <th class="text-center">{{ __('action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($feesDetail as $fee)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $currencySymbol }} {{ number_format($fee->total_amount, 2) }}</strong></td>
                        <td class="text-center">{{ $fee->date ? date('d-m-Y', strtotime($fee->date)) : '-' }}</td>
                        <td class="text-center">
                           
    @if($fee->status_label == 'pending')
        -
    @else
        @if($fee->mode == 0)
            <span class="badge badge-secondary p-2">{{ __('cash') }}</span>
        @elseif($fee->mode == 1)
            <span class="badge badge-info p-2">{{ __('cheque') }}</span>
        @elseif($fee->mode == 2)
            <span class="badge badge-primary p-2">{{ __('online') }}</span>
        @else
            -
        @endif
    @endif
</td>
                       
                        <td class="text-center">
                            @if($fee->status_label == 'paid')
                            <span class="badge badge-success p-2">{{ __('paid') }}</span>
                            @elseif($fee->status_label == 'partial')
                            <span class="badge badge-success p-2">{{ __('partial_paid') }}</span>
                            @else
                            <span class="badge badge-danger p-2">{{ __('pending') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn-icon-square" data-toggle="modal" data-target="#feeLedgerModal" title="{{ __('view_details') }}">
                                <x-icon name="eye" :size="16" />
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="report-empty-state">
                            <h6 class="text-muted">{{ __('no_fees_found') }}</h6>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

{{-- Modal — content is already available from the controller, no AJAX needed --}}
<div class="modal fade" id="feeLedgerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">{{ __('fee_payment_installments') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <div>
                        <strong>{{ __('student') }}:</strong> {{ $student->user?->full_name }}

                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4 mb-2">
                        <div class="fee-stat-box">
                            <div class="fee-stat-icon fee-stat-icon-neutral">
                                <x-icon name="dollar-sign" :size="16" />
                            </div>
                            <div>
                                <div class="fee-stat-label">{{ __('course_fees') }}</div>
                                <div class="fee-stat-value">{{ $currencySymbol }} {{ number_format($courseFees ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="fee-stat-box">
                            <div class="fee-stat-icon fee-stat-icon-success">
                                <x-icon name="check" :size="16" />
                            </div>
                            <div>
                                <div class="fee-stat-label">{{ __('paid_amount') }}</div>
                                <div class="fee-stat-value text-success">{{ $currencySymbol }} {{ number_format($paidAmount ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="fee-stat-box">
                            <div class="fee-stat-icon fee-stat-icon-danger">
                                <x-icon name="alert-circle" :size="16" />
                            </div>
                            <div>
                                <div class="fee-stat-label">{{ __('outstanding') }}</div>
                                <div class="fee-stat-value text-danger">{{ $currencySymbol }} {{ number_format($outstandingAmount ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($isInstallmentEnabled)

                @if($ledgerRows->count())
                <h6 class="mb-3">{{ __('installment_ledger_timeline') }}</h6>

                @foreach($ledgerRows as $ledgerRow)
                <div class="installment-row {{ $ledgerRow->is_paid ? 'installment-row-paid' : ($ledgerRow->is_overdue ? 'installment-row-overdue' : '') }}">
                    <div class="installment-number {{ $ledgerRow->is_paid ? 'installment-number-paid' : ($ledgerRow->is_overdue ? 'installment-number-overdue' : '') }}">
                        {{ $loop->iteration }}
                    </div>

                    <div class="installment-details">
                        <div class="d-flex align-items-center flex-wrap">
                            <strong class="mr-2">{{ __('installment') }} {{ $loop->iteration }}</strong>
                            @if($ledgerRow->is_paid)
                            <span class="badge badge-success p-2">{{ __('paid') }}</span>
                            @elseif($ledgerRow->is_overdue)
                            <span class="badge badge-danger p-2">{{ __('overdue') }}</span>
                            @else
                            <span class="badge badge-warning p-2">{{ __('pending') }}</span>
                            @endif
                        </div>

                        <div class="text-muted mt-1">
                            @if($ledgerRow->is_paid)
                            <span class="installment-date-paid">{{ __('paid_on') }}: {{ date('d-m-Y', strtotime($ledgerRow->paid_date)) }}</span>

                            @if(isset($ledgerRow->mode) && $ledgerRow->mode == 2 && isset($ledgerRow->transaction_id) && $ledgerRow->transaction_id)
                            <span class="ml-2">{{ __('payment_id') }}: {{ $ledgerRow->transaction_id }}</span>
                            @elseif(isset($ledgerRow->mode) && $ledgerRow->mode == 1 && isset($ledgerRow->cheque_number) && $ledgerRow->cheque_number)
                            <span class="ml-2">{{ __('cheque_number') }}: {{ $ledgerRow->cheque_number }}</span>
                            @endif

                            @else
                            {{ __('due_date_on') }}: {{ $ledgerRow->due_date }},
                            {{ __('charges') }}: {{ $ledgerRow->due_charges }}%
                            @endif
                        </div>
                    </div>

                    <div class="installment-meta text-right">
                        <strong>{{ $currencySymbol }} {{ number_format($ledgerRow->amount ?? 0, 2) }}</strong>
                        @if($ledgerRow->is_paid && isset($ledgerRow->mode))
                        <div class="small mt-1" style="color: var(--theme-color);">
                            @if($ledgerRow->mode == 0)
                            {{ __('mode') }}: {{ __('cash') }}
                            @elseif($ledgerRow->mode == 1)
                            {{ __('mode') }}: {{ __('cheque') }}
                            @elseif($ledgerRow->mode == 2)
                            {{ __('mode') }}: {{ __('online') }}
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach

                <div class="text-muted small mt-3">
                    {{ $ledgerRows->where('is_paid', true)->count() }} {{ __('of') }} {{ $ledgerRows->count() }} {{ __('installments_paid') }}
                </div>
                @else
                <div class="text-center text-muted p-4">{{ __('no_installments_found') }}</div>
                @endif

                @else

                @if($paymentHistory->count())
                <h6 class="mb-3">{{ __('payment_history') }}</h6>

                @foreach($paymentHistory as $payment)
                <div class="installment-row {{ $payment->is_fully_paid ? 'installment-row-paid' : '' }}">
                    <div class="installment-number {{ $payment->is_fully_paid ? 'installment-number-paid' : '' }}">
                        {{ $loop->iteration }}
                    </div>
                    <div class="installment-details">
                        <div class="d-flex align-items-center flex-wrap">
                            <strong class="mr-2">{{ $currencySymbol }} {{ number_format($payment->total_amount ?? 0, 2) }}</strong>
                            @if($payment->is_fully_paid)
                            <span class="badge badge-success p-2">{{ __('paid') }}</span>
                            @else
                            <span class="badge badge-warning p-2">{{ __('partial_paid') }}</span>
                            @endif
                        </div>

                        @if($payment->mode == 2 && $payment->transaction_id)
                        <div class="text-muted small mt-1">{{ __('payment_id') }}: {{ $payment->transaction_id }}</div>
                        @elseif($payment->mode == 1 && $payment->cheque_number)
                        <div class="text-muted small mt-1">{{ __('cheque_number') }}: {{ $payment->cheque_number }}</div>
                        @endif
                    </div>
                    <div class="installment-meta text-right">
                        <div class="text-muted small">{{ __('date') }}: {{ date('d-m-Y', strtotime($payment->date)) }}</div>

                        <div class="small mt-1" style="color: var(--theme-color);">
                            @if($payment->mode == 0)
                            {{ __('mode') }}: {{ __('cash') }}
                            @elseif($payment->mode == 1)
                            {{ __('mode') }}: {{ __('cheque') }}
                            @elseif($payment->mode == 2)
                            {{ __('mode') }}: {{ __('online') }}
                            @else
                            {{ __('mode') }}: {{ __('n_a') }}
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
                @else
                <div class="text-center text-muted p-4">{{ __('no_fees_found') }}</div>
                @endif

                @endif

            </div>

            <div class="modal-footer justify-content-between">
                <small class="text-muted">{{ __('secure_billing_ledger') }}</small>
                <div>

                    <button type="button" class="btn btn-inverse" data-dismiss="modal">{{ __('close') }}</button>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('#installmentPayTable .installment-checkbox');
        const totalEl = document.getElementById('installmentTotalAmount');

        function updateTotal() {
            let total = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) total += parseFloat(cb.dataset.amount || 0);
            });
            totalEl.textContent = total.toFixed(2);
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateTotal));
        updateTotal();
    });
</script>