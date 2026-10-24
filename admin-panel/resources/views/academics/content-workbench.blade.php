@extends('layouts.master')
@section('title',__('Learning content'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/content-workbench.css') }}?v={{ filemtime(public_path('assets/css/content-workbench.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content content-workbench">
    <x-page.header :title="__('Learning content')" :subtitle="__('Upload privately, review the converted draft, then publish to your teaching groups.')" />
    @include('academics.groups-status')
    <form method="GET" action="{{ route('academic-content.index') }}" aria-label="{{ __('Choose content context') }}" class="content-controls">
        <x-page.field type="select" :restore-old-input="false" name="session_year_id" :label="__('School year')" :options="array_column($configuration['school_years'],'name','id')" :value="$configuration['session_year_id']" />
        @if($configuration['groups'])<x-page.field type="select" :restore-old-input="false" name="group_id" :label="__('Teaching context')" :options="collect($configuration['groups'])->mapWithKeys(fn ($g) => [$g['id']=>$g['subject_name'].' · '.$g['grade_name'].' · '.$g['name']])->all()" :value="$group['id'] ?? null" />@endif
        <button class="m3-button m3-button--tonal" type="submit">{{ __('Open teaching context') }}</button>
    </form>
    <div class="content-columns">
    <section class="content-panel" aria-labelledby="content-library-heading"><h2 id="content-library-heading">{{ __('Your material') }}</h2>
        <a class="m3-button m3-button--outlined" href="{{ route('academic-content.index',['session_year_id'=>$configuration['session_year_id'],'group_id'=>$group['id'] ?? null]) }}">{{ __('New content upload') }}</a>
        @forelse($items['items'] as $row)<a class="content-row" href="{{ route('academic-content.index',['item_id'=>$row['id'],'session_year_id'=>$configuration['session_year_id']]) }}"><strong>{{ $row['title'] }}</strong><span>{{ __($row['draft_state'] ?? 'published') }} · {{ $row['active'] ? __('Active') : __('Withdrawn') }}</span></a>
        @empty<p>{{ __('No learning material yet. Upload a file to begin.') }}</p>@endforelse
        @if($items['page']>1)<a href="{{ route('academic-content.index',['page'=>$items['page']-1,'session_year_id'=>$configuration['session_year_id']]) }}">{{ __('Previous page') }}</a>@endif
        @if($items['page']<$items['last_page'])<a href="{{ route('academic-content.index',['page'=>$items['page']+1,'session_year_id'=>$configuration['session_year_id']]) }}">{{ __('Next page') }}</a>@endif
    </section>
    <section class="content-panel" aria-labelledby="content-editor-heading"><h2 id="content-editor-heading">{{ $version['selection']['title'] ?? __('New private upload') }}</h2>
        @if($item)
            <p>{{ __('Version') }} {{ $version['number'] ?? '' }} · {{ __($version['state'] ?? 'unavailable') }}</p>
            <a class="m3-button m3-button--outlined" href="{{ route('academic-content.index',['item_id'=>$item['id']]) }}">{{ __('Refresh content status') }}</a>
            @if($version)
                <p>{{ __('Source file') }}: {{ $version['source']['name'] }} · {{ __('Scan') }}: {{ __($version['source']['scan_state']) }}</p>
                @if($version['failure_code'])<div role="alert"><h3>{{ __('Conversion needs attention') }}</h3><p>{{ \Illuminate\Support\Facades\Lang::has('content_failure_'.$version['failure_code']) ? __('content_failure_'.$version['failure_code']) : __('The source could not be processed safely. Review it or contact the school administrator.') }}</p><p>{{ __('Check the source and school controls. Retry after restoring the service, or upload a corrected source.') }}</p></div>@endif
                @if(in_array($version['state'],['queued','scanning','converting'],true))<p role="status">{{ __('The private source is being scanned and converted. Refresh its status shortly. It is not published yet.') }}</p>@endif
                @if($item['can_write'] && $version['id']===($item['draft']['id'] ?? null) && in_array($version['state'],['failed','quarantined'],true))
                    <form method="POST" action="{{ route('academic-content.retry',['version'=>$version['id']]) }}" aria-label="{{ __('Retry content conversion') }}">@csrf @include('academics.content.revisions')<button class="m3-button m3-button--tonal" type="submit">{{ __('Retry content conversion') }}</button></form>
                @endif
            @endif
        @endif
        @if(!$item && $selection)
            <form method="POST" enctype="multipart/form-data" data-native-file-upload action="{{ route('academic-content.create') }}" aria-label="{{ __('Upload private learning material') }}" class="content-form">@csrf
                <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
                @include('academics.content.selection',['fieldPrefix'=>'content-upload'])
                @include('academics.content.source-field',['sourceFieldId'=>'content-new-source','sourceFieldLabel'=>__('Private source file')])
                <p>{{ __('PDF, Word, slides, spreadsheet, text, audio or video up to 25 MiB. Audio/video must fit the school transcription allowance. Nothing is published until you review it.') }}</p>
                <button class="m3-button m3-button--filled" type="submit">{{ __('Upload for conversion') }}</button>
            </form>
        @elseif(!$item)<p>{{ __('No current teaching groups are available. Ask the school to check your dated allocations.') }}</p>@endif
        @if($version && $item['can_write'] && $version['id']===($item['draft']['id'] ?? null) && in_array($version['state'],['draft','published'],true))
            <form method="POST" action="{{ route('academic-content.save',['version'=>$version['id']]) }}" aria-label="{{ __('Edit content draft') }}" class="content-form">@csrf @include('academics.content.revisions')
                @include('academics.content.selection',['fieldPrefix'=>'content-edit'])
                <x-page.field :restore-old-input="false" type="textarea" name="markdown" :label="__('Markdown draft')" :value="$version['markdown']" rows="16" required />
                <button class="m3-button m3-button--filled" type="submit">{{ __('Save reviewed draft') }}</button>
            </form>
            @if($version['state']==='draft')<a class="m3-button m3-button--tonal" href="{{ route('academic-content.index',['item_id'=>$item['id'],'publication_preview'=>1]) }}">{{ __('Preview publication') }}</a>@endif
        @endif
        @if($rendered)<section aria-labelledby="content-rendered-heading" class="content-rendered"><h3 id="content-rendered-heading">{{ __('Readable Markdown preview') }}</h3>{!! $rendered !!}</section>@endif
        @if($preview)
            <section aria-labelledby="content-publication-heading"><h3 id="content-publication-heading">{{ __('Review content publication') }}</h3><p>{{ __('Review the body, topic tags and teaching groups above. Current group memberships and enrolments determine reader access.') }}</p><p>{{ __('Current group members') }}: {{ $preview['learner_count'] }}</p>
            <form method="POST" action="{{ route('academic-content.publish',['version'=>$version['id']]) }}" aria-label="{{ __('Publish reviewed content') }}" class="content-form">@csrf @include('academics.content.revisions')
                <input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
                <label class="content-choice"><input type="checkbox" name="reviewed" value="1" required>{{ __('I reviewed the converted text, topic tags and published teaching groups.') }}</label>
                @if($preview['requires_reason'])<x-page.field name="reason" :label="__('Leadership review reason')" required minlength="3" maxlength="2000" />@endif
                <button class="m3-button m3-button--filled" type="submit">{{ __('Publish reviewed content') }}</button>
            </form></section>
        @endif
        @if($item && $version && $item['can_write'] && $version['id']===($item['draft']['id'] ?? null))
            <details><summary>{{ __('Replace private source') }}</summary><form method="POST" enctype="multipart/form-data" data-native-file-upload action="{{ route('academic-content.upload',['item'=>$item['id']]) }}" aria-label="{{ __('Replace private source') }}" class="content-form">@csrf @include('academics.content.revisions') @include('academics.content.selection',['fieldPrefix'=>'content-replace'])
                @include('academics.content.source-field',['sourceFieldId'=>'content-replacement-source','sourceFieldLabel'=>__('Replacement source file')])
                <button class="m3-button m3-button--tonal" type="submit">{{ __('Queue replacement source') }}</button></form></details>
        @endif
        @if($item)
            <details><summary>{{ __('Private version history') }}</summary><ul>@foreach($item['versions'] as $history)<li><a href="{{ route('academic-content.index',['item_id'=>$item['id'],'version_id'=>$history['id'],'history_page'=>$item['page']]) }}">{{ __('Version') }} {{ $history['number'] }} · {{ $history['title'] }} · {{ __($history['state']) }}</a></li>@endforeach</ul>
            @if($item['page']>1)<a href="{{ route('academic-content.index',['item_id'=>$item['id'],'history_page'=>$item['page']-1]) }}">{{ __('Previous history page') }}</a>@endif
            @if($item['page']<$item['last_page'])<a href="{{ route('academic-content.index',['item_id'=>$item['id'],'history_page'=>$item['page']+1]) }}">{{ __('Next history page') }}</a>@endif</details>
            @if($item['can_write'] && $item['active'])<details><summary>{{ __('Withdraw published material') }}</summary><form method="POST" action="{{ route('academic-content.archive',['item'=>$item['id']]) }}" aria-label="{{ __('Withdraw published material') }}" class="content-form">@csrf<input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="item_revision" value="{{ $item['revision'] }}"><x-page.field name="reason" :label="__('Withdrawal reason')" required minlength="3" maxlength="2000" /><button class="m3-button m3-button--outlined" type="submit">{{ __('Withdraw from learner libraries') }}</button></form></details>@endif
        @endif
    </section>
    </div>
    @if($configuration['can_manage'])<details class="content-panel"><summary>{{ __('School transcription controls') }}</summary><form method="POST" action="{{ route('academic-content.settings') }}" aria-label="{{ __('School transcription controls') }}" class="content-form">@csrf
        <input type="hidden" name="revision" value="{{ $configuration['settings']['revision'] }}">
        <x-page.field type="select" :restore-old-input="false" name="transcription_enabled" :label="__('Audio and video transcription')" :options="[1=>__('Enabled'),0=>__('Disabled')]" :value="(int) $configuration['settings']['transcription_enabled']" />
        <x-page.field :restore-old-input="false" name="daily_transcription_seconds" type="number" :label="__('School daily transcription allowance in seconds')" :value="$configuration['settings']['daily_transcription_seconds']" min="600" max="28800" required />
        <button class="m3-button m3-button--tonal" type="submit">{{ __('Save content controls') }}</button></form></details>@endif
</div>
@endsection
