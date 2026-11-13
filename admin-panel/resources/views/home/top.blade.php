{{--
    Top of Home, on the page pattern and the Material 3 library: the page header
    (one primary action), the setup checklist for people who can complete it,
    and today's numbers. The dashboard widgets below are the ones Home always had.
--}}
<x-page.header :title="__('home_welcome', ['name' => $admin_name])"
    :subtitle="now()->translatedFormat('l, j F Y')"
    :primary-label="__('nav_add_learner')" :primary-href="route('students.create')"
    primary-permission="student-create" :breadcrumbs="false" />

@php($operationLinks = collect(app(\App\Services\Panel\Navigation::class)->items(auth()->user()))->whereIn('key',['emergency','family-visits','leadership-dashboard','mastery-impact']))
@if($operationLinks->isNotEmpty())
<section class="page-section" aria-labelledby="school-operations-title">
    <h2 class="page-section-title" id="school-operations-title">{{ __('School operations') }}</h2>
    <nav class="panel-actions" aria-label="{{ __('School operations') }}">@foreach($operationLinks as $operationLink)<a class="m3-button m3-button--outlined" href="{{ $operationLink['url'] }}">{{ $operationLink['label'] }}</a>@endforeach</nav>
</section>
@endif

@if (! empty($checklist))
    @include('home.checklist', ['checklist' => $checklist])
@endif

@if (! empty($today))
    <section class="m3 page-section" aria-labelledby="today-title">
        <h2 class="page-section-title" id="today-title">{{ __('today_title') }}</h2>
        <div class="page-stat-grid">
            @foreach ($today as $number)
                @php($tag = $number['url'] ? 'a' : 'div')
                <{{ $tag }} class="m3-card m3-card--outlined {{ $number['url'] ? 'm3-card--interactive' : '' }} page-stat" data-today="{{ $number['key'] }}" @if ($number['url']) href="{{ $number['url'] }}" @endif>
                    <span class="page-stat-label">{{ $number['label'] }}</span>
                    <span class="page-stat-value">{{ $number['value'] }}</span>
                    @if ($number['note'])
                        <span class="page-stat-note">{{ $number['note'] }}</span>
                    @endif
                </{{ $tag }}>
            @endforeach
        </div>
    </section>
@endif
