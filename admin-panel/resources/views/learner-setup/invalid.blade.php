@extends('learner-setup.shell')

@section('content')
    <h1 class="h4 mb-2">{{ __('learner_setup_link_invalid_title') }}</h1>
    <p>{{ __('learner_setup_link_invalid') }}</p>
    <p><a href="{{ route('learner-setup.code') }}">{{ __('learner_setup_use_code') }}</a></p>
@endsection
