@extends('learner-setup.shell')

@section('content')
    <h1 class="m3-headline-small">{{ __('learner_setup_done_title') }}</h1>
    <p class="m3-body-large">{{ __('learner_setup_done', ['name' => $learner->full_name, 'gr' => $learner->email]) }}</p>
@endsection
