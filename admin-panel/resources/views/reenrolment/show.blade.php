@extends('layouts.master')
@section('title', $detail['contract']->title)
@section('content')
@php
    $contract = $detail['contract'];
    $view = in_array(request('view'), ['overview','wording','outstanding','acceptances'], true) ? request('view') : 'overview';
    $rules = ['all_fee_payers'=>__('Every fee-responsible guardian'), 'fee_payer'=>__('One fee-responsible guardian'), 'any_guardian'=>__('Any guardian with approval rights')];
    $items = [];
    foreach(['overview'=>'Overview','wording'=>'Contract wording','outstanding'=>'Outstanding responses','acceptances'=>'Acceptance audit'] as $key=>$label) $items[$key] = ['label'=>$label, 'href'=>route('reenrolment-panel.show', ['contract'=>$contract->id,'view'=>$key])];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$contract->title" :subtitle="$detail['year'].' · '.__('Version :version', ['version'=>$contract->version]).' · '.__(ucfirst($contract->state))">
        <x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('reenrolment-panel.index') }}">{{ __('All contracts') }}</a></x-slot:actions>
    </x-page.header>
    <x-panel-view-nav :items="$items" :current="$view" />
    @if($receipt)
        <div class="m3-banner" role="status">
        @if($receipt['operation'] === 'remind')
            {{ __(':notices reminder notices queued for :learners learners.', ['notices'=>$receipt['result']['notices'], 'learners'=>$receipt['result']['learners']]) }}
            {{ $receipt['result']['more'] ? __('More guardians are waiting for today’s reminder. Send another batch when ready.') : __('No more eligible reminders are waiting in this batch. Learners may still have outstanding responses.') }}
        @else {{ __('Saved.') }} @endif
        </div>
    @endif
    @if($view === 'overview')
        <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ __('Contract summary') }}</h2>
            <dl class="panel-summary-grid"><div><dt>{{ __('Reply by') }}</dt><dd>{{ $contract->respond_by->format('Y-m-d') }}</dd></div><div><dt>{{ __('Who must accept') }}</dt><dd>{{ $rules[$contract->acceptance_rule] }}</dd></div><div><dt>{{ __('Published') }}</dt><dd>{{ $contract->published_at->toIso8601String() }}</dd></div><div><dt>{{ __('Current learners confirmed') }}</dt><dd>{{ $outstanding['confirmed'] }} / {{ $outstanding['audience'] }}</dd></div></dl>
            <p>{{ __('Each acceptance records the guardian, signed name, version, wording hash and time. Staff cannot accept on a guardian’s behalf.') }}</p>
        </section>
        @if($canWrite && $contract->state === 'open')
            <x-page.form-card id="reenrolment-withdraw" :action="route('reenrolment-panel.withdraw', $contract->id)" :title="__('Withdraw this version')" :subtitle="__('Withdrawal stops new acceptances and reminders. Existing acceptance evidence remains available.')" :submit-label="__('Withdraw contract')" :draft="false">
                @include('reenrolment.pins')<input type="hidden" name="revision" value="{{ $contract->revision }}">
                <x-m3.checkbox name="confirm_withdrawal" value="1" :label="__('I understand this closes this contract version for families.')" required />
            </x-page.form-card>
        @endif
    @elseif($view === 'wording')
        <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ __('Published contract wording') }}</h2><div class="panel-preserve-lines">{{ $contract->body }}</div><p>{{ __('Wording fingerprint') }}</p><code class="panel-wrap-anywhere">{{ $contract->text_hash }}</code></section>
    @elseif($view === 'outstanding')
        <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ __('Outstanding responses') }}</h2><p>{{ __(':outstanding of :audience currently enrolled learners still need confirmation.', ['outstanding'=>$outstanding['outstanding'],'audience'=>$outstanding['audience']]) }}</p><p>{{ __('This list reflects current enrolment and guardian fee responsibility. No awaited payer listed does not mean the learner is confirmed.') }}</p>
            @if($outstanding['items'] === [])<p>{{ __('No outstanding learners on this page.') }}</p>@else
            <div class="panel-table-scroll"><table class="page-table"><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Acceptances') }}</th><th scope="col">{{ __('Fee-responsible guardians') }}</th><th scope="col">{{ __('Awaiting') }}</th></tr></thead><tbody>
                @foreach($outstanding['items'] as $item)<tr><th scope="row">{{ $item['name'] }}</th><td>{{ $item['accepted_count'] }}</td><td>{{ $item['fee_responsible_count'] }}</td><td>{{ collect($item['awaiting'])->pluck('name')->join(', ') ?: __('Check guardian eligibility') }}</td></tr>@endforeach
            </tbody></table></div>@endif
            @include('reenrolment.pagination', ['page'=>$outstanding['page'], 'lastPage'=>max(1,(int) ceil($outstanding['outstanding']/100))])
        </section>
        @if($canWrite && $contract->state === 'open')
            <x-page.form-card id="reenrolment-remind" :action="route('reenrolment-panel.remind', $contract->id)" :title="__('Send a reminder batch')" :subtitle="__('Eligible guardians receive one reminder per contract per day. Repeated requests skip guardians already reminded today.')" :submit-label="__('Send reminder batch')" :draft="false">
                @include('reenrolment.pins')
                <p>{{ __('Each batch sends at most :limit notices. Review the receipt before sending a further batch.', ['limit'=>max(1,(int) config('school_business.reminder_batch',500))]) }}</p>
                <x-m3.checkbox name="confirm_reminders" value="1" :label="__('Send reminders to eligible guardians with outstanding responses.')" required />
            </x-page.form-card>
        @endif
    @else
        <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ __('Acceptance audit') }}</h2><p>{{ __(':count recorded acceptances for this exact contract version.', ['count'=>$detail['acceptance_count']]) }}</p>
            @if($detail['acceptances'] === [])<p>{{ __('No acceptances have been recorded for this version.') }}</p>@else
            <div class="panel-table-scroll"><table class="page-table"><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Guardian and signed name') }}</th><th scope="col">{{ __('Fee responsibility at acceptance') }}</th><th scope="col">{{ __('Accepted') }}</th><th scope="col">{{ __('Version and wording fingerprint') }}</th></tr></thead><tbody>
                @foreach($detail['acceptances'] as $item)<tr><th scope="row">{{ $item['learner'] }}</th><td>{{ $item['guardian'] }}<br>{{ $item['signed_name'] }}</td><td>{{ $item['fee_responsible'] ? __('Yes') : __('No') }}</td><td>{{ $item['accepted_at'] }}</td><td>{{ $item['version'] }}<br><code class="panel-wrap-anywhere">{{ $item['text_hash'] }}</code></td></tr>@endforeach
            </tbody></table></div>@endif
            @include('reenrolment.pagination', ['page'=>$detail['page'], 'lastPage'=>$detail['last_page']])
        </section>
    @endif
</div>
@endsection
