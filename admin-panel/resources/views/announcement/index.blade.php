@extends('layouts.master')
@section('title', __('School notices'))
@section('content')
@php
    $noticeView = $editingNotice ? 'edit' : (request('view') === 'create' ? 'create' : 'list');
    $isTeacher = auth()->user()->hasRole('Teacher');
    $notice = $editingNotice;
    $scope = $notice?->table_type === \App\Models\SubjectTeacher::class ? 'class_section' : ($notice?->table_type === \App\Models\ClassSchool::class ? 'class' : 'noticeboard');
    if (!$notice && $isTeacher) $scope = 'class_section';
    $noticeYear = $notice?->session_year_id ?? app(\App\Services\Panel\SessionYearContext::class)->selectedId();
@endphp
<div class="content-wrapper m3 page-content" data-notice-workspace data-assign-url="{{ url('getAssignData') }}" data-sections-url="{{ url('announcement-class-sections') }}" data-year="{{ $noticeYear }}" data-choose-label="{{ __('Please select') }}" data-load-error="{{ __('Audience choices could not be loaded. Retry without losing your notice.') }}" data-delete-message="{{ __('Delete this school notice? Its original files are retained by the source record policy.') }}" data-delete-error="{{ __('The notice could not be deleted. Refresh its saved status before retrying.') }}">
    <x-page.header :title="$notice ? __('Edit school notice') : __('School notices')" :subtitle="__('Publish notices to a class or the school noticeboard. Teachers use their current allocated subjects.')" :primary-label="$noticeView === 'list' ? __('Create notice') : null" :primary-href="$noticeView === 'list' ? route('announcement.index',['view'=>'create']) : null" primary-permission="announcement-create" />
    <x-panel-view-nav :current="$noticeView === 'edit' ? 'list' : $noticeView" :items="[
        'list'=>['label'=>'Notices','href'=>route('announcement.index')],
        'create'=>['label'=>'Create notice','href'=>route('announcement.index',['view'=>'create']),'visible'=>auth()->user()->can('announcement-create')],
    ]" />
    <p id="notice-action-status" role="alert" tabindex="-1" hidden></p>
    @if($noticeView === 'list')
        <x-page.table id="school-notices" :url="url('announcement-list')" query-params="noticeListParams" :empty-title="__('No school notices')" :empty-message="__('Create a notice for the selected school year.')">
            <x-slot:columns>
                <th data-field="title">{{ __('title') }}</th>
                <th data-field="description">{{ __('description') }}</th>
                <th data-field="assignto">{{ __('assign_to') }}</th>
                <th data-field="file" data-formatter="fileFormatter" data-escape="false">{{ __('files') }}</th>
                <th data-field="operate" data-escape="false">{{ __('action') }}</th>
            </x-slot:columns>
        </x-page.table>
    @elseif($notice || auth()->user()->can('announcement-create'))
        <x-page.form-card id="school-notice-editor" :action="$notice ? route('announcement.update',$notice->id) : route('announcement.store')" :method="$notice ? 'PUT' : 'POST'" :title="$notice ? __('Notice details') : __('Create notice')" :submit-label="$notice ? __('Save notice') : __('Publish notice')" :redirect="route('announcement.index')" :draft="false">
            @if($notice)<input type="hidden" name="edit_id" value="{{ $notice->id }}">@endif
            <x-page.field name="title" :label="__('title')" :value="$notice?->title" :restore-old-input="false" required />
            <x-page.field name="description" type="textarea" :label="__('description')" :value="$notice?->description" :restore-old-input="false" rows="5" />
            <x-page.field name="set_data" id="notice-scope" type="select" :label="__('assign_to')" :options="$isTeacher ? ['class_section'=>__('Allocated class and subject')] : ['noticeboard'=>__('noticeboard'),'class'=>__('Whole class')]" :value="$scope" :restore-old-input="false" required />
            <div data-notice-section hidden>
                <x-page.field name="class_section_id" id="notice-section" type="select" :label="__('class_section')" :options="[]" data-selected="{{ $notice?->table_type === \App\Models\SubjectTeacher::class ? $notice->table?->class_section_id : '' }}" data-selected-term="{{ $notice?->term_id }}" :restore-old-input="false" />
            </div>
            <input type="hidden" name="term_id" id="notice-term" value="{{ $notice?->term_id }}">
            <div data-notice-target hidden>
                <x-page.field :name="$notice ? 'get_data' : 'get_data[]'" id="notice-target" type="select" :label="$isTeacher ? __('subject') : __('class')" :options="[]" :multiple="!$notice" data-selected="{{ $notice?->table_type === \App\Models\SubjectTeacher::class ? $notice->table?->subject_id : ($notice?->table_id ?? '') }}" :restore-old-input="false" />
            </div>
            <p data-notice-load-status role="status" hidden></p>
            <button type="button" class="m3-button m3-button--outlined" data-notice-retry hidden>{{ __('Retry audience choices') }}</button>
            @if($notice)
                <section><h3>{{ __('Current attachments') }}</h3>
                    @forelse($notice->file as $file)<p><a href="{{ $file->file_url }}" target="_blank" rel="noopener noreferrer">{{ $file->file_name }}</a></p>@empty<p>{{ __('No files attached') }}</p>@endforelse
                </section>
            @endif
            <x-page.field name="file[]" type="file" multiple :label="$notice ? __('Add attachments') : __('files')" :help="__('PDF, office documents or images, up to 4 MB per file.')" />
            <x-slot:actions><a class="m3-button m3-button--text" href="{{ route('announcement.index') }}">{{ __('Cancel') }}</a></x-slot:actions>
        </x-page.form-card>
    @endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/announcement-workspace.js') }}" defer></script>@endsection
