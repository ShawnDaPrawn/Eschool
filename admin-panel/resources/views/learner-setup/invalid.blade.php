@extends('learner-setup.shell')

@section('content')
    <h1 class="m3-headline-small">{{ __('learner_setup_link_invalid_title') }}</h1>
    <p class="m3-body-large">{{ __('learner_setup_link_invalid') }}</p>
    <a class="m3-button m3-button--outlined" href="{{ route('learner-setup.code') }}">{{ __('learner_setup_use_code') }}</a>
@endsection
