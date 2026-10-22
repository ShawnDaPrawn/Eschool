@extends('learner-setup.shell')

@section('content')
    <h1 class="h4 mb-2">{{ __('learner_setup_done_title') }}</h1>
    <p>{{ __('learner_setup_done', ['name' => $learner->full_name, 'gr' => $learner->email]) }}</p>
@endsection
