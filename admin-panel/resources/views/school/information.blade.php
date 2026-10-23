@extends('layouts.master')
@section('title', __('School information'))
@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('School information')" :subtitle="$info['school']['name']" />
    @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    <x-page.form-card id="school-information" :action="route('school-information.update')" method="PUT" :title="__('School contacts and branding')">
        <x-page.field name="school_name" :label="__('School display name')" :value="$info['school']['name']" required maxlength="255" />
        <x-page.field name="school_short_name" :label="__('School short name')" :value="$info['school']['short_name']" maxlength="64" />
        <x-page.field name="school_tagline" :label="__('School tagline')" :value="$settings['school_tagline'] ?? null" maxlength="255" />
        <x-page.field name="school_website" type="url" :label="__('School website')" :value="$info['website']" maxlength="2048" />
        <x-page.field name="school_phone" type="tel" :label="__('School phone')" :value="$info['phone']" maxlength="80" />
        <x-page.field name="school_email" type="email" :label="__('School email')" :value="$info['email']" maxlength="191" />
        <x-page.field name="school_address" type="textarea" :label="__('School address')" :value="$info['address']" maxlength="2000" />
        <x-page.field name="time_zone" type="select" :label="__('School timezone')" :value="$settings['time_zone'] ?? app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone" :options="array_combine(timezone_identifiers_list(), timezone_identifiers_list())" required />
        <x-page.field name="crest" type="file" :label="__('Replace school crest')" accept="image/jpeg,image/png" :help="__('JPEG or PNG, up to 2 MB. Leave empty to keep the current crest.')" />
    </x-page.form-card>
    <section class="m3 m3-card m3-card--outlined page-form-card">
        <div class="m3-card__content page-card__content">
            <h2>{{ __('Published school documents') }}</h2>
            <p>{{ __('Publish school policies and schedules as PDFs with public links. Unlisting removes the document from My school; shared links continue to work.') }}</p>
            <form method="POST" action="{{ route('school-information.documents.store') }}" enctype="multipart/form-data">
                @csrf
                <x-page.field name="title" :label="__('Document title')" required maxlength="120" />
                <x-page.field name="kind" type="select" :label="__('Document kind')" :options="['code_of_conduct' => __('Code of conduct'), 'fee_schedule' => __('Fee schedule'), 'discipline_framework' => __('Discipline framework'), 'other' => __('Other school document')]" required />
                <x-page.field name="document" type="file" :label="__('PDF document')" accept="application/pdf" required :help="__('PDF, up to 5 MB.')" />
                <button class="m3-button m3-button--filled" type="submit">{{ __('Publish document') }}</button>
            </form>
            <ul>
            @foreach ($info['documents'] as $document)
                <li><a href="{{ $document['url'] }}">{{ $document['title'] }}</a>
                    <form method="POST" action="{{ route('school-information.documents.destroy', $document['id']) }}">@csrf @method('DELETE')
                        <button class="m3-button m3-button--text" type="submit">{{ __('Unlist document') }} {{ $document['title'] }}</button>
                    </form>
                </li>
            @endforeach
            </ul>
        </div>
    </section>
    <section class="m3 m3-card m3-card--outlined page-form-card">
        <div class="m3-card__content page-card__content">
            <h2>{{ __('School term dates') }}</h2>
            <p>{{ __('My school uses the terms in the current school year.') }}</p>
            <dl>@foreach ($info['term_dates'] as $term)<dt>{{ $term['name'] }}</dt><dd>{{ $term['starts_on'] }} — {{ $term['ends_on'] }}</dd>@endforeach</dl>
            @can('semester-list')<a class="m3-button m3-button--outlined" href="{{ route('semester.index') }}">{{ __('Manage term dates') }}</a>@endcan
        </div>
    </section>
</div>
@endsection
