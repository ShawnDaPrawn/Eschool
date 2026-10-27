@extends('layouts.master')
@section('title', __('Preview enrolment matches'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Preview enrolment matches')" />
    <x-m3.button variant="text" :href="route('fee-plans.index',['year_id'=>$preview['year_id']])">{{ __('Back to fee plans') }}</x-m3.button>
    <p>{{ __('This preview shows annual rates, not invoices. It does not charge families or create payments.') }}</p>
    <p>{{ __('Preview date') }}: {{ $preview['date'] }} · {{ __('Learners') }}: {{ $preview['learners'] }} · {{ __('Unambiguous full-year matches') }}: {{ $preview['matched'] }}</p>
    <h2 class="m3-title-large">{{ __('Annual rate totals for full-year matches') }}</h2>
    @forelse($preview['totals'] as $currency=>$cents)<p>{{ \App\Services\Platform\Ledger\Money::ofCents($cents,$currency)->format() }}</p>@empty<p>{{ __('No unambiguous full-year matches.') }}</p>@endforelse
    <h2 class="m3-title-large">{{ __('Exceptions to review') }}</h2>
    <ul>@forelse($preview['exceptions'] as $issue=>$count)<li>{{ __($issue) }}: {{ $count }}</li>@empty<li>{{ __('No matching exceptions. Payer and billing checks will run when billing is available.') }}</li>@endforelse</ul>
    <div class="page-table__scroll" role="region" aria-label="{{ __('Enrolment matches on the selected date') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Enrolment matches on the selected date') }}</caption>
        <thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Matched plans') }}</th><th scope="col">{{ __('Annual amount') }}</th><th scope="col">{{ __('Exceptions to review') }}</th></tr></thead>
        <tbody>@forelse($rows as $row)<tr><td>{{ $row['name'] }}</td><td>{{ implode(', ',$row['plan_names']) }}</td>
            <td>@if($row['amount_cents']!==null){{ \App\Services\Platform\Ledger\Money::ofCents($row['amount_cents'],$row['currency'])->format() }}@else — @endif</td>
            <td>{{ implode('; ',array_map(fn($issue)=>__($issue),$row['issues'])) }}</td></tr>@empty<tr><td colspan="4">{{ __('No enrolments on this date.') }}</td></tr>@endforelse</tbody>
    </table></div>
    {{ $rows->links() }}
</div>
@endsection
