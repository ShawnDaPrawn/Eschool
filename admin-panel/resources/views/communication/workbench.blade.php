@extends('layouts.master')
@section('title',__('School messages'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/message-workbench.css') }}?v={{ filemtime(public_path('assets/css/message-workbench.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content message-workbench">
    <x-page.header :title="__('School messages')" :subtitle="__('Prepare a private draft, preview its audience, then submit for approval or delivery.')" />
    @include('academics.groups-status')
    <p>{{ __('School timezone') }}: {{ $configuration['timezone'] }} · {{ __('Grade heads need no message approval.') }}</p>
    <form method="GET" action="{{ route('communication-centre.index') }}" class="message-controls" aria-label="{{ __('Choose message year') }}">
        <x-page.field type="select" :restore-old-input="false" name="session_year_id" :label="__('School year')" :options="array_column($configuration['years'],'name','id')" :value="$configuration['session_year_id']" />
        <button class="m3-button m3-button--tonal" type="submit">{{ __('Open year') }}</button>
    </form>
    <div class="message-columns">
    <section class="message-panel" aria-labelledby="message-list-heading"><h2 id="message-list-heading">{{ __('Your drafts and reviews') }}</h2>
        <a class="m3-button m3-button--outlined" href="{{ route('communication-centre.index',['session_year_id'=>$configuration['session_year_id']]) }}">{{ __('New school message') }}</a>
        @forelse($messages['items'] as $row)<a class="message-row" href="{{ route('communication-centre.index',['message_id'=>$row['id']]) }}"><strong>{{ $row['title'] }}</strong><span>{{ __($row['state']) }} · {{ $row['is_author'] ? __('Your message') : __('Review requested') }}</span></a>@empty<p>{{ __('No drafts or reviews yet.') }}</p>@endforelse
        @if($messages['page']>1)<a href="{{ route('communication-centre.index',['page'=>$messages['page']-1,'session_year_id'=>$configuration['session_year_id']]) }}">{{ __('Previous page') }}</a>@endif
        @if($messages['page']<$messages['last_page'])<a href="{{ route('communication-centre.index',['page'=>$messages['page']+1,'session_year_id'=>$configuration['session_year_id']]) }}">{{ __('Next page') }}</a>@endif
    </section>
    <section class="message-panel" aria-labelledby="message-editor-heading"><h2 id="message-editor-heading">{{ $message['title'] ?? __('New private message') }}</h2>
        @if($message)<p>{{ __($message['state']) }} · {{ __('Revision') }} {{ $message['revision'] }}</p>
            <a class="m3-button m3-button--outlined" href="{{ route('communication-centre.index',['message_id'=>$message['id']]) }}" data-message-saved-action>{{ __('Refresh message status') }}</a>
            @if($message['blocked_code'])<p role="alert">{{ __('Delivery needs review because the audience or author authority changed, or no current recipients remain. Edit and preview the draft before submitting it again.') }}</p>@endif
            @if($message['decision_reason'])<p>{{ __('Review reason') }}: {{ $message['decision_reason'] }}</p>@endif
            @if($message['published_at'])<p>{{ __('Recipients at publication') }}: {{ $message['recipient_count'] }} · {{ __('Read acknowledgements') }}: {{ $message['read_count'] }}</p>@endif
        @endif
        @if(!$message || ($message['is_author'] && !$message['published_at'] && $message['state']!=='withdrawn'))
            @php($scopeOptions=collect($configuration['scopes'])->mapWithKeys(fn ($s)=>[$s['kind'].':'.($s['target_id'] ?? 0)=>__($s['kind']).' · '.$s['name']])->all())
            @foreach(array_filter($configuration['audiences'],fn ($a)=>$a['active']) as $list)@php($scopeOptions['custom:'.$list['id']]=__('Custom list').' · '.$list['name'])@endforeach
            @php($savedScope=$message ? $message['audience']['kind'].':'.($message['audience']['target_id'] ?? 0) : array_key_first($scopeOptions))
            @php($selectedScope=$restored['scope_selector'] ?? (isset($restored['audience']) ? $restored['audience']['kind'].':'.($restored['audience']['target_id'] ?? 0) : $savedScope))
            @if($scopeOptions)
            <form method="POST" action="{{ $message ? route('communication-centre.save',['message'=>$message['id']]) : route('communication-centre.create') }}" class="message-form" aria-label="{{ __('Edit private message') }}" data-message-draft @if($restored) data-restored-draft @endif>@csrf
                <input type="hidden" name="operation_id" value="{{ $restored['operation_id'] ?? \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $restored['revision'] ?? $message['revision'] ?? 0 }}"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
                <x-page.field :restore-old-input="false" name="title" :label="__('Message subject')" :value="$restored['title'] ?? $message['title'] ?? ''" required maxlength="191" />
                <x-page.field type="select" :restore-old-input="false" name="scope_selector" :label="__('Audience scope')" :options="$scopeOptions" :value="$selectedScope" required />
                <x-page.field type="select" :restore-old-input="false" name="parents" :label="__('Audience people')" :options="['0'=>__('Learners or school staff; explicit contacts for custom lists'),'1'=>__('Parents and guardians of these learners')]" :value="(int) ($restored['parents'] ?? $restored['audience']['parents'] ?? $message['audience']['parents'] ?? false)" />
                <p>{{ __('Custom lists use the explicit contacts saved in the list. Their guardian toggle is ignored.') }}</p>
                <fieldset><legend>{{ __('Delivery channels') }}</legend>@foreach(['in_app'=>__('In app'),'push'=>__('Push notification'),'email'=>__('Email')] as $key=>$label)<label class="message-choice"><input type="checkbox" name="channels[]" value="{{ $key }}" @checked(in_array($key,$restored['channels'] ?? $message['channels'] ?? ['in_app'],true))>{{ $label }}</label>@endforeach</fieldset>
                <p>{{ __('Mandatory school channels can be added automatically. Optional channels respect each persons choices. Counts record queued attempts, not delivery receipts.') }}</p>
                <x-page.field :restore-old-input="false" type="datetime-local" name="scheduled_local" :label="__('Schedule in school time; leave blank to send now')" :value="$restored !== null ? ($restored['scheduled_local'] ?? '') : ($message['scheduled_local'] ?? '')" />
                <x-page.field :restore-old-input="false" type="textarea" name="markdown" :label="__('Message Markdown')" :value="$restored['markdown'] ?? $message['markdown'] ?? ''" rows="12" required maxlength="20000" />
                <p>{{ __('Formatting supports headings, lists and HTTPS links. Images and raw HTML are removed. Maximum 20,000 characters and 64 KiB.') }}</p>
                <button class="m3-button m3-button--filled" type="submit">{{ __('Save private message') }}</button>
            </form>
            <p data-message-dirty-status hidden role="status">{{ __('Save your changes before previewing, withdrawing or submitting this message.') }}</p>
            @else<p>{{ __('No current audience scopes are available. Ask the school to check your allocations or author rules.') }}</p>@if($restored)<h3>{{ __('Your unsaved text') }}</h3><p>{{ $restored['title'] }}</p><pre class="message-restored-text">{{ $restored['markdown'] }}</pre>@endif @endif
        @endif
        @if($message && !$message['published_at'] && in_array($message['state'],['draft','pending','approved','rejected','blocked'],true))<a class="m3-button m3-button--tonal" data-message-saved-action href="{{ route('communication-centre.index',['message_id'=>$message['id'],'preview_message'=>1]) }}">{{ __('Preview saved message') }}</a>@endif
        @if($restored && $message && ($message['published_at'] || $message['state']==='withdrawn'))<h3>{{ __('Your unsaved text') }}</h3><p>{{ $restored['title'] }}</p><pre class="message-restored-text">{{ $restored['markdown'] }}</pre>@endif
        @if($rendered)<section class="message-rendered" aria-labelledby="message-body-heading"><h3 id="message-body-heading">{{ __('Saved message preview') }}</h3>{!! $rendered !!}</section>@endif
        @if($preview)<section aria-labelledby="message-delivery-heading"><h3 id="message-delivery-heading">{{ __('Audience and delivery preview') }}</h3>
            <p>{{ __('Current recipients') }}: {{ $preview['recipient_count'] }} · {{ $preview['requires_approval'] ? __('Approval required') : __('No approval required') }}</p>
            <p>{{ __('Channels') }}: {{ implode(', ',array_map(fn ($c)=>__($c),$preview['channels'])) }} · {{ __('Schedule') }}: {{ $message['scheduled_local'] ?? __('Send now') }} ({{ $configuration['timezone'] }})</p>
            <p>{{ __('Scheduled standard audiences resolve again at delivery time. The send checks current author and approver authority.') }}</p>
            @if($message['is_author'] && in_array($message['state'],['draft','rejected','blocked'],true))<form method="POST" action="{{ route('communication-centre.submit',['message'=>$message['id']]) }}" aria-label="{{ __('Submit previewed message') }}">@csrf @include('communication.revisions')<input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}"><button class="m3-button m3-button--filled" type="submit" data-message-saved-action>{{ __('Submit previewed message') }}</button></form>@endif
            @if($message['can_review'] && $message['state']==='pending')<form method="POST" action="{{ route('communication-centre.decision',['message'=>$message['id']]) }}" class="message-form" aria-label="{{ __('Decide message approval') }}">@csrf @include('communication.revisions')<input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}"><x-page.field id="message-review-reason" name="reason" :label="__('Review reason')" required minlength="3" maxlength="2000" /><button name="decision" value="approve" class="m3-button m3-button--filled" type="submit">{{ __('Approve message') }}</button><button name="decision" value="reject" class="m3-button m3-button--outlined" type="submit">{{ __('Return for changes') }}</button></form>@endif
        </section>@endif
        @if($message && $message['state']!=='withdrawn')<details><summary>{{ __('Withdraw message') }}</summary><form method="POST" action="{{ route('communication-centre.withdraw',['message'=>$message['id']]) }}" class="message-form" aria-label="{{ __('Withdraw message') }}">@csrf @include('communication.revisions')<x-page.field id="message-withdraw-reason" name="reason" :label="__('Withdrawal reason')" required minlength="3" maxlength="2000" /><button class="m3-button m3-button--outlined" type="submit" data-message-saved-action>{{ __('Withdraw message') }}</button></form></details>@endif
    </section></div>
    <details class="message-panel" @if($audience && !$audience->isSmart()) open @endif><summary>{{ __('Saved custom audiences') }}</summary>@php($fixedAudience = $audience && !$audience->isSmart() ? $audience : null)
        <p>{{ __('Select current permitted contacts. Names are shown without email addresses or phone numbers.') }}</p>
        <a href="{{ route('communication-centre.index',['session_year_id'=>$configuration['session_year_id']]) }}">{{ __('New custom audience') }}</a>
        <ul>@foreach($configuration['audiences'] as $list)@if($list['editable'])<li><a href="{{ route('communication-centre.index',['audience_id'=>$list['id'],'session_year_id'=>$configuration['session_year_id']]) }}">{{ $list['name'] }}</a> · {{ $list['kind']==='smart' ? __('Smart group') : __('Fixed list') }} · {{ $list['shared'] ? __('Shared') : __('Private') }}</li>@endif @endforeach</ul>
        <form method="POST" action="{{ $fixedAudience ? route('communication-centre.audience-save',['audience'=>$fixedAudience->id]) : route('communication-centre.audience-create') }}" class="message-form" aria-label="{{ __('Save custom audience') }}">@csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $fixedAudience?->revision ?? 0 }}"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
            <x-page.field :restore-old-input="false" name="name" :label="__('Audience name')" :value="$fixedAudience?->name ?? ''" required maxlength="191" />
            <x-page.field :restore-old-input="false" type="select" name="active" :label="__('Audience availability')" :options="['1'=>__('Active'),'0'=>__('Archived')]" :value="(int) ($fixedAudience?->active ?? true)" />
            <x-page.field :restore-old-input="false" type="select" name="shared" :label="__('Who can use it')" :options="['0'=>__('Private: only you'),'1'=>__('Shared with staff who may address everyone on it')]" :value="(int) ($fixedAudience?->shared ?? false)" />
            @if($removedContacts)<p role="status">{{ __('Contacts no longer available in your scope') }}: {{ $removedContacts }}. {{ __('Saving removes these contacts. An empty list can be archived.') }}</p>@endif
            @foreach(array_diff($selectedContacts,array_column($contacts['items'],'id')) as $id)<input type="hidden" name="user_ids[]" value="{{ $id }}">@endforeach
            <fieldset><legend>{{ __('Current contacts') }}</legend>@foreach($contacts['items'] as $contact)<label class="message-choice"><input type="checkbox" name="user_ids[]" value="{{ $contact['id'] }}" @checked(in_array($contact['id'],$selectedContacts,true))>{{ $contact['name'] }}</label>@endforeach</fieldset>
            <button class="m3-button m3-button--tonal" type="submit">{{ __('Save custom audience') }}</button>
        </form>
        <p>{{ __('Save the list before changing the contacts page. Contacts that left your authorized scope are removed when you save.') }}</p>
        @if($contacts['page']>1)<a href="{{ route('communication-centre.index',['audience_id'=>$fixedAudience?->id,'contact_page'=>$contacts['page']-1,'session_year_id'=>$configuration['session_year_id']]) }}">{{ __('Previous contacts') }}</a>@endif
        @if($contacts['page']<$contacts['last_page'])<a href="{{ route('communication-centre.index',['audience_id'=>$fixedAudience?->id,'contact_page'=>$contacts['page']+1,'session_year_id'=>$configuration['session_year_id']]) }}">{{ __('More contacts') }}</a>@endif
    </details>
    <details class="message-panel" @if($smart) open @endif><summary>{{ __('Smart groups') }}</summary>
        <p>{{ __('A smart group keeps rules, not names. It reaches whoever is in its grades, homerooms or groups when a message is previewed and again when it is sent.') }}</p>
        <a href="{{ route('communication-centre.index',['session_year_id'=>$configuration['session_year_id']]) }}">{{ __('New smart group') }}</a>
        @if($smart)<p role="status">{{ __('People this group reaches today') }}: {{ $smartCount }}</p>@endif
        @php($ruleOptions = array_values(array_filter($configuration['scopes'], fn ($s) => in_array($s['kind'], \App\Models\Communication\Audience::RULE_KINDS, true))))
        @php($chosenRules = $smart ? array_map(fn ($rule) => $rule['kind'].':'.$rule['target_id'], $smart->definition['rules']) : [])
        @if($ruleOptions || $smart)
        <form method="POST" action="{{ $smart ? route('communication-centre.audience-save',['audience'=>$smart->id]) : route('communication-centre.audience-create') }}" class="message-form" aria-label="{{ __('Save smart group') }}">@csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $smart?->revision ?? 0 }}"><input type="hidden" name="session_year_id" value="{{ $smart?->definition['session_year_id'] ?? $configuration['session_year_id'] }}"><input type="hidden" name="kind" value="smart">
            <x-page.field :restore-old-input="false" id="smart-group-name" name="name" :label="__('Group name')" :value="$smart?->name ?? ''" required maxlength="191" />
            <x-page.field :restore-old-input="false" id="smart-group-active" type="select" name="active" :label="__('Audience availability')" :options="['1'=>__('Active'),'0'=>__('Archived')]" :value="(int) ($smart?->active ?? true)" />
            <x-page.field :restore-old-input="false" id="smart-group-shared" type="select" name="shared" :label="__('Who can use it')" :options="['0'=>__('Private: only you'),'1'=>__('Shared with staff who may address every rule')]" :value="(int) ($smart?->shared ?? false)" />
            <x-page.field :restore-old-input="false" id="smart-group-match" type="select" name="match" :label="__('Learners to include')" :options="['any'=>__('In any of the chosen groups'),'all'=>__('Only those in all of the chosen groups')]" :value="$smart?->definition['match'] ?? 'any'" />
            <x-page.field :restore-old-input="false" id="smart-group-parents" type="select" name="parents" :label="__('Audience people')" :options="['0'=>__('The learners'),'1'=>__('Parents and guardians of these learners')]" :value="(int) ($smart?->definition['parents'] ?? true)" />
            <fieldset><legend>{{ __('Rules (up to ten)') }}</legend>@foreach($ruleOptions as $option)@php($key = $option['kind'].':'.$option['target_id'])<label class="message-choice"><input type="checkbox" name="rule_selectors[]" value="{{ $key }}" @checked(in_array($key,$chosenRules,true))>{{ __($option['kind']) }} · {{ $option['name'] }}</label>@endforeach</fieldset>
            @if($smart && array_diff($chosenRules, array_map(fn ($o) => $o['kind'].':'.$o['target_id'], $ruleOptions)))<p role="status">{{ __('Some rules are outside the scopes you may address now. Saving keeps only the rules you choose here.') }}</p>@endif
            <button class="m3-button m3-button--tonal" type="submit">{{ __('Save smart group') }}</button>
        </form>
        @else<p>{{ __('No current audience scopes are available. Ask the school to check your allocations or author rules.') }}</p>@endif
    </details>
    @if($configuration['can_manage'])<details class="message-panel"><summary>{{ __('Message author rules') }}</summary><p>{{ __('Grade heads are exempt from approval even when their role normally requires it. A sender cannot approve their own message.') }}</p>
        @foreach($roleRules as $rule)<form method="POST" action="{{ route('communication-centre.rule-save',['role'=>$rule['role_id']]) }}" class="message-rule" aria-label="{{ __('Author rule') }} {{ $rule['role'] }}">@csrf
            <h3>{{ $rule['role'] }}</h3><input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $rule['revision'] }}">
            <x-page.field :restore-old-input="false" :id="'message-role-school-'.$rule['role_id']" type="select" name="allow_school" :label="__('School-wide addressing')" :options="['0'=>__('Restricted to allocated scopes'),'1'=>__('Whole school allowed')]" :value="(int) $rule['allow_school']" />
            <x-page.field :restore-old-input="false" :id="'message-role-approval-'.$rule['role_id']" type="select" name="requires_approval" :label="__('Approval rule')" :options="['0'=>__('No approval required'),'1'=>__('Approval required')]" :value="(int) $rule['requires_approval']" />
            <button class="m3-button m3-button--tonal" type="submit">{{ __('Save author rule') }}</button>
        </form>@endforeach
    </details>@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/message-workbench.js') }}?v={{ filemtime(public_path('assets/js/message-workbench.js')) }}" defer></script>@endsection
