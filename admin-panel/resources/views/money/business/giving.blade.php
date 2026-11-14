@extends('layouts.master')
@section('title', __('Giving campaigns'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench">
<x-page.header :title="__('Giving campaigns')" :subtitle="__('Track campaign goals, pledges and verified receipts separately.')" :primary-label="$canManage && $canWrite && $section==='campaigns'?__('Create campaign'):null" :primary-href="$canManage && $canWrite && $section==='campaigns'?route('giving-panel.index',['section'=>'create']):null" />
@if($section==='campaigns')<form method="get" class="workspace-filters"><x-page.field name="search" type="search" :label="__('Find a giving campaign')" :value="request('search')" maxlength="120" /><x-m3.button type="submit" variant="outlined">{{ __('Search campaigns') }}</x-m3.button></form>@endif
@if($section==='create' && !$canWrite)<x-page.empty-state :title="__('Giving is read-only')" :message="__('Review retained campaigns or ask the school administrator to enable this module before creating a campaign.')" />
@elseif($section==='create')
<x-m3.button variant="text" :href="route('giving-panel.index')">{{ __('Back to campaigns') }}</x-m3.button>
<x-page.form-card id="giving-campaign" :action="route('giving-panel.create')" :title="__('Create giving campaign')" :submit-label="__('Open campaign')" :draft="false" :cancel-href="route('giving-panel.index')">
@include('money.business.hidden')<fieldset @disabled(!$canWrite)>
<x-page.field name="name" :label="__('Campaign name')" maxlength="160" required />
<x-page.field name="description" type="textarea" :label="__('Campaign description')" maxlength="5000" />
<div class="m3-grid m3-gap-4"><x-page.field name="currency" :label="__('Currency')" :value="\App\Services\Platform\Ledger\Money::defaultCurrency()" pattern="[A-Z]{3}" maxlength="3" required class="m3-col-12 m3-col-medium-6" /><x-page.field name="goal" :label="__('Fundraising goal')" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" class="m3-col-12 m3-col-medium-6" /></div>
<div class="m3-grid m3-gap-4"><x-page.field name="starts_on" type="date" :label="__('Starts on')" required class="m3-col-12 m3-col-medium-6" /><x-page.field name="ends_on" type="date" :label="__('Ends on')" required class="m3-col-12 m3-col-medium-6" /></div>
<x-page.field name="public_totals" type="select" :label="__('Campaign totals visibility')" :options="['0'=>__('Finance team only'),'1'=>__('Share totals with the school community')]" required />
<p>{{ __('Sharing totals never publishes donor names or email addresses.') }}</p></fieldset>
</x-page.form-card>
@elseif(!$campaigns)<x-page.empty-state :title="__('No giving campaigns yet')" :message="__('Create a campaign with its purpose, dates and currency before recording gifts.')" />
@else
<div class="page-table__scroll" role="region" aria-label="{{ __('Giving campaigns') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Giving campaigns') }}</caption><thead><tr><th scope="col">{{ __('Campaign') }}</th><th scope="col">{{ __('State') }}</th><th scope="col">{{ __('Received') }}</th><th scope="col">{{ __('Pledged') }}</th><th scope="col">{{ __('Goal') }}</th></tr></thead><tbody>
@foreach($campaigns as $row)<tr><td><a href="{{ route('giving-panel.show',['campaign'=>$row['id']]) }}">{{ $row['name'] }}</a></td><td>{{ __($row['state']) }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['raised_cents'],$row['currency'])->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['pledged_cents'],$row['currency'])->format() }}</td><td>{{ $row['goal_cents']===null?'—':\App\Services\Platform\Ledger\Money::ofCents($row['goal_cents'],$row['currency'])->format() }}</td></tr>@endforeach
</tbody></table></div>{{ $campaignPage->withQueryString()->links() }}
@endif
</div>
@endsection
