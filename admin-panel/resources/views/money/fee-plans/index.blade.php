@extends('layouts.master')
@section('title', __('Fee plans'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper fee-plan-workbench">
    <x-page.header :title="__('Fee plans')" :description="__('Configure annual rates and check enrolment matches before billing.')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    <div class="card mb-4"><div class="card-body">
        <form method="get" action="{{ route('fee-plans.index') }}">
            <x-m3.select name="year_id" :label="__('School year')" :options="$years->pluck('name', 'id')" :value="$year?->id" required />
            <x-m3.button type="submit">{{ __('Show plans') }}</x-m3.button>
        </form>
        @if($canWrite)<x-m3.button variant="outlined" :href="route('fee-plans.new')">{{ __('New fee plan') }}</x-m3.button>
        @else<p>{{ __('Fee plans are read-only while fees are disabled.') }}</p>@endif
    </div></div>
    <div class="table-responsive" role="region" aria-label="{{ __('Annual fee plans for the selected school year') }}" tabindex="0"><table class="table">
        <caption>{{ __('Annual fee plans for the selected school year') }}</caption>
        <thead><tr><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Annual amount') }}</th><th scope="col">{{ __('Applies to') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Actions') }}</th></tr></thead>
        <tbody>@forelse($plans as $plan)<tr>
            <td>{{ $plan->name }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($plan->amount_cents, $plan->currency)->format() }}</td>
            <td>{{ $plan->scope==='all'?__('All learners'):($plan->scope==='grade'?__('Grade').': '.($gradeNames[$plan->target_id]??__('Unavailable target')):__('Class').': '.($classNames[$plan->target_id]??__('Unavailable target'))) }}</td>
            <td>{{ __(['draft'=>'Draft', 'active'=>'Active', 'archived'=>'Archived'][$plan->state]) }}</td>
            <td><x-m3.button variant="text" :href="route('fee-plans.edit', ['plan'=>$plan->id])" :aria-label="__('Review fee plan').': '.$plan->name">{{ __('Review fee plan') }}</x-m3.button></td>
        </tr>@empty<tr><td colspan="5">{{ __('No fee plans for this year.') }}</td></tr>@endforelse</tbody>
    </table></div>
    {{ $plans->links() }}
    @if($year)<div class="card mt-4"><div class="card-body">
        <h2 class="h4">{{ __('Preview enrolment matches') }}</h2>
        <p>{{ __('This preview shows annual rates, not invoices. It does not charge families or create payments.') }}</p>
        <form method="get" action="{{ route('fee-plans.preview', ['year'=>$year->id]) }}">
            <x-m3.text-field name="date" type="date" :label="__('Preview date')" :min="substr((string)$year->start_date,0,10)" :max="substr((string)$year->end_date,0,10)" :value="max(substr((string)$year->start_date,0,10), min(now()->toDateString(),substr((string)$year->end_date,0,10)))" required />
            <x-m3.button type="submit">{{ __('Preview enrolment matches') }}</x-m3.button>
        </form>
    </div></div>@endif
</div>
@endsection
