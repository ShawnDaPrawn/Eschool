@extends('layouts.master')
@section('title', __('Private photos'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/private-gallery.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content gallery-workbench" data-private-gallery>
    <x-page.header :title="__('Private photos')" :subtitle="__('Share photos with your current group. Learners and guardians control their personal tags.')" />
    @if($messages)<div role="alert" tabindex="-1" data-gallery-error>@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
    @if(!$canWrite)<p role="status">{{ __('Changes are unavailable. Check the current school year and module access.') }}</p>@endif
    @if($configuration['groups'])
    <form method="GET" action="{{ route('gallery-panel.index') }}" class="gallery-fields" aria-label="{{ __('Choose photo group') }}">
        <x-page.field id="gallery-group" name="group_id" type="select" :label="__('Available group')" :options="array_column($configuration['groups'], 'name', 'id')" :value="$groupId" :restore-old-input="false" />
        <button type="submit" class="m3-button m3-button--outlined">{{ __('Open group') }}</button>
    </form>
    @else<p>{{ __('No current groups are available for this account.') }}</p>@endif

    @if($group)
    <section class="page-section" aria-labelledby="gallery-group-heading">
        <h2 id="gallery-group-heading">{{ $group['name'] }}</h2>
        <p>{{ __('Group photos are private to the current group. Personal galleries contain only approved tags.') }}</p>
        <div class="gallery-grid">
            @forelse($listing['items'] as $photo)
            @php($image = route('gallery-panel.image', ['photo' => $photo['id'], 'expected_account_id' => $configuration['account_id'], 'expected_school_id' => $configuration['school_id']]))
            <article class="m3-card m3-card--outlined"><div class="m3-card__content gallery-card">
                <img src="{{ $image }}&amp;thumbnail=1" alt="{{ __('Private group photograph') }}" width="360" height="270" loading="lazy">
                <p class="gallery-caption">{{ $photo['caption'] }}</p>
                <p>{{ $photo['school_year'] }} · {{ $photo['captured_on'] }}</p>
                <button type="button" class="m3-button m3-button--text" data-gallery-open="{{ $image }}">{{ __('Open photograph') }}</button>
                @if($canWrite && $group['can_upload'])
                <form method="POST" action="{{ route('gallery-panel.propose', ['photo' => $photo['id']]) }}" aria-label="{{ __('Propose photo tag').' '.$photo['id'] }}">
                    @include('gallery.hidden')
                    <x-page.field :id="'gallery-handle-'.$photo['id']" name="handle" :label="__('Approved learner handle')" placeholder="@learner.handle" :restore-old-input="false" maxlength="21" required />
                    <p>{{ __('The learner must currently belong to this group. The school consent rule still applies.') }}</p>
                    <button type="submit" class="m3-button m3-button--outlined">{{ __('Propose tag') }}</button>
                </form>
                <form method="POST" action="{{ route('gallery-panel.withdraw', ['photo' => $photo['id']]) }}" aria-label="{{ __('Withdraw photograph').' '.$photo['id'] }}">
                    @include('gallery.hidden')<input type="hidden" name="revision" value="{{ $photo['revision'] }}">
                    <button type="submit" class="m3-button m3-button--outlined">{{ __('Withdraw photograph') }}</button>
                </form>
                @endif
            </div></article>
            @empty<p>{{ __('No current photos in this group.') }}</p>@endforelse
        </div>
        @if($listing['next_before'])<a class="m3-button m3-button--outlined" href="{{ route('gallery-panel.index', ['group_id' => $groupId, 'before' => $listing['next_before']]) }}">{{ __('Older photographs') }}</a>@endif
    </section>
    @if($canWrite && $group['can_upload'])
    <section class="page-section" aria-labelledby="gallery-upload-heading"><h2 id="gallery-upload-heading">{{ __('Add a group photograph') }}</h2>
        <form method="POST" action="{{ route('gallery-panel.upload') }}" enctype="multipart/form-data" class="gallery-fields" aria-label="{{ __('Add a group photograph') }}">
            @include('gallery.hidden', ['intent' => $operationId])
            <input type="hidden" name="scope" value="group"><input type="hidden" name="activity_group_id" value="{{ $groupId }}"><input type="hidden" name="session_year_id" value="{{ $group['session_year_id'] }}">
            <x-page.field id="gallery-photo" name="photo" type="file" :label="__('Photograph')" accept="image/jpeg,image/png" :restore-old-input="false" required :help="__('Choose a JPEG or PNG up to 4 MB. Location and device metadata are removed.')" />
            <x-page.field id="gallery-captured" name="captured_on" type="date" :label="__('Date photographed')" :value="$draft['captured_on']" :restore-old-input="false" required />
            <x-page.field id="gallery-caption" name="caption" type="textarea" :label="__('Caption')" :value="$draft['caption']" :restore-old-input="false" maxlength="2000" />
            @if($messages)<p>{{ __('Choose the photograph again before saving. Your caption stays only on this page.') }}</p>@endif
            <button type="submit" class="m3-button m3-button--filled">{{ __('Save private photograph') }}</button>
        </form>
    </section>
    @endif
    @endif

    @if($configuration['can_manage'])
    <section class="page-section" aria-labelledby="gallery-consent-heading"><h2 id="gallery-consent-heading">{{ __('Photo tag consent') }}</h2>
        <p>{{ __('Guardian approval is required by default. Tightening this rule hides self-approved tags until a guardian approves them.') }}</p>
        @if($canWrite)
        <form method="POST" action="{{ route('gallery-panel.settings') }}" aria-label="{{ __('Photo tag consent') }}" class="gallery-fields">
            @include('gallery.hidden')<input type="hidden" name="revision" value="{{ $configuration['settings_revision'] }}"><input type="hidden" name="guardian_required" value="0">
            <label for="gallery-guardian"><input id="gallery-guardian" type="checkbox" name="guardian_required" value="1" @checked($configuration['guardian_required'])> {{ __('Require a verified guardian to approve photo tags') }}</label>
            <button type="submit" class="m3-button m3-button--filled">{{ __('Save consent rule') }}</button>
        </form>
        @else<p>{{ $configuration['guardian_required'] ? __('Guardian approval is required.') : __('Learners may approve their own tags.') }}</p>@endif
    </section>
    @endif
    <dialog data-gallery-viewer aria-label="{{ __('Private photograph') }}"><form method="dialog"><button class="m3-button m3-button--text">{{ __('Close photograph') }}</button></form><img alt="{{ __('Private photograph') }}" data-gallery-full></dialog>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/private-gallery.js') }}" defer></script>@endsection
