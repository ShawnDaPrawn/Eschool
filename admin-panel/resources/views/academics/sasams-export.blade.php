@extends('layouts.master')
@section('title', __('SA-SAMS exports'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('SA-SAMS exports')" :subtitle="__('Export learners, daily attendance or task marks using your confirmed target layout.')" />
    @include('academics.groups-status')
    <section class="page-section">
        <h2>{{ __('Choose the matching import layout') }}</h2>
        <p>{{ __('Use the version and import menu confirmed by your school SA-SAMS operator. The layout supplies ordered headers, learner references and explicit status codes. An Excel export also needs the exact blank template named by its hash.') }}</p>
        <p>{{ __('Missing marks, unmapped codes and ambiguous daily attendance must be resolved before exporting. This download does not change academic records.') }}</p>
        <form method="POST" action="{{ route('academic-sasams.download') }}" enctype="multipart/form-data" aria-label="{{ __('Prepare private SA-SAMS export') }}">
            @csrf
            <x-page.field :restore-old-input="false" name="layout" type="file" :label="__('Versioned JSON layout')" accept=".json,application/json" required />
            <x-page.field :restore-old-input="false" name="template" type="file" :label="__('Matching blank Excel template, if required')" accept=".xlsx" />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Download private export and manifest') }}</button>
        </form>
    </section>
    <section class="page-section"><h2>{{ __('Check the target import') }}</h2>
        <p>{{ __('Keep the ZIP private. Its manifest records counts, version and hashes. Back up the target database, import into a test copy of the matching SA-SAMS version, and compare learner counts, attendance and scores before using it for a live submission.') }}</p>
        <p>{{ __('A generated export is not proof of a successful SA-SAMS import. Record that result separately for each target layout version.') }}</p>
    </section>
</div>
@endsection
