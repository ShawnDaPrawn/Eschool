@extends('layouts.master')
@section('title', __('Publish a re-enrolment contract'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Publish a re-enrolment contract')" :subtitle="__('Families accept the exact published wording. A replacement version needs new acceptances.')">
        <x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('reenrolment-panel.index') }}">{{ __('All contracts') }}</a></x-slot:actions>
    </x-page.header>
    @if(!$canWrite)
        <div class="m3-banner" role="status">{{ __('This workspace is read only. Return to the current school year before making changes.') }}</div>
    @elseif($years->isEmpty())
        <x-page.empty-state :title="__('Add the next school year first')" :message="__('The contract must belong to a school year that starts after the current year ends.')" />
    @else
        <x-page.form-card id="reenrolment-publish" :action="route('reenrolment-panel.publish')" :title="__('Contract wording and acceptance')" :submit-label="__('Publish contract')" :cancel-href="route('reenrolment-panel.index')" :draft="false">
            @include('reenrolment.pins')
            <div class="panel-form-grid">
                <x-page.field name="session_year_id" type="select" :label="__('Contract school year')" :options="$years->pluck('name','id')" :placeholder="__('Choose the future year')" :help="__('This chooses the contract year. It does not change the active school year.')" required />
                <x-page.field name="respond_by" type="date" :label="__('Reply by')" :min="app(\App\Services\Academics\AcademicAuthority::class)->today()" :help="__('Choose a date before the contract school year begins.')" required />
            </div>
            <x-page.field name="title" :label="__('Contract title')" maxlength="191" required />
            <x-page.field name="body" type="textarea" :label="__('Full contract wording')" rows="16" minlength="20" maxlength="60000" required :help="__('Review the complete wording before publishing. Published versions cannot be edited.')" />
            <x-page.field name="acceptance_rule" type="select" :label="__('Who must accept')" :options="['all_fee_payers'=>__('Every fee-responsible guardian'), 'fee_payer'=>__('One fee-responsible guardian'), 'any_guardian'=>__('Any guardian with approval rights')]" required />
            <p>{{ __('Under a fee-responsible rule, another guardian can add their acceptance after a fee-responsible guardian accepts. With no eligible fee payer, the learner remains awaiting a response.') }}</p>
            <div class="m3-banner" role="note">{{ __('Publishing replaces any open contract for the same future year. Existing acceptances remain in the audit history; families must accept the new version. Publishing does not send reminders automatically.') }}</div>
            @if(collect($listing['items'])->where('state','open')->isNotEmpty())
                <details class="m3-expansion"><summary class="m3-expansion__header">{{ __('Current open contracts') }}</summary><ul>@foreach(collect($listing['items'])->where('state','open') as $item)<li>{{ $item['session_year'] }} — {{ $item['title'] }} ({{ __('Version :version', ['version'=>$item['version']]) }})</li>@endforeach</ul></details>
            @endif
            <x-m3.checkbox name="confirm_publication" value="1" :label="__('I have reviewed the complete wording and understand that this publishes a new contract version.')" required />
        </x-page.form-card>
    @endif
</div>
@endsection
