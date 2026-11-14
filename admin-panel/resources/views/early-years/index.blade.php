@extends('layouts.master')
@section('title', __('Early years'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Early years')" :subtitle="__('The daily diary, observations and each child\'s portfolio for your class.')" />
    @include('early-years.messages')
    <nav class="workspace-tabs" aria-label="{{ __('Early years') }}">
        <a class="m3-button m3-button--text" href="{{ route('early-years.index') }}" @if($workspaceView === 'classes') aria-current="page" @endif>{{ __('Classes') }}</a>
        @if($manage)<a class="m3-button m3-button--text" href="{{ route('early-years.index', ['view' => 'configuration']) }}" @if($workspaceView !== 'classes') aria-current="page" @endif>{{ __('Configuration') }}</a>@endif
        @if(!$manage)<a class="m3-button m3-button--text" href="{{ route('early-years.index', ['view' => 'areas']) }}" @if($workspaceView === 'areas') aria-current="page" @endif>{{ __('Development areas') }}</a>@endif
    </nav>
    @if($workspaceView === 'classes')

    <section class="page-section" aria-labelledby="early-years-classes">
        <h2 id="early-years-classes">{{ __('Classes') }}</h2>
        @if ($classes->isEmpty())
            <x-page.empty-state :title="__('No classes yet')" :message="__('Teachers see the classes they are allocated to as homeroom teacher this year.')" />
        @else
            <ul>
                @foreach ($classes as $class)
                    <li>
                        <a href="{{ route('early-years.class', ['section' => $class->id]) }}">{{ $class->full_name }}</a>
                        · <a href="{{ route('early-years.diary', ['section' => $class->id]) }}">{{ __('Today\'s diary') }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @else
        @include('early-years.configuration')
    @endif
</div>
@endsection
