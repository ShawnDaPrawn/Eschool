@extends('saas.layout')

@section('title', __('School created'))

@section('content')
<h1>{{ $school->name }}</h1>
<p class="saas-lead">{{ __('The school is set up on the :package package.', ['package' => $school->package_key]) }}</p>
<p>{{ __('The school starts with an editable January–December calendar-year preset. Its administrator must confirm the dates and set up its terms and academic plans. No marks, grading policy or published assessment plan is created.') }}</p>
<div class="saas-card" role="alert">
    <h2>{{ __('First administrator') }}</h2>
    <p>{{ $admin->first_name }} {{ $admin->last_name }} · {{ $admin->email }}</p>
    @if ($existingAccount)
    <p>{{ __('This address already has a NavigateEd account, so no new one was made. They have been emailed an invitation to be this school\'s first administrator: they sign in with their own password and accept it. Until then they have no role here, and their password is never changed.') }}</p>
    <p>{{ __('If the email does not arrive, give them this link privately. It works only when signed in as that account, is shown only on this page and expires in seven days. You can send a new one from the school page.') }}</p>
    @else
    <p>{{ __('They have been emailed a private link to choose their own password. Nobody else knows it.') }}</p>
    <p>{{ __('If the email does not arrive, give them this link privately. It is shown only on this page, works once and expires in seven days. You can send a new one from the school page.') }}</p>
    @endif
    <p class="saas-secret">{{ $invitationUrl }}</p>
</div>
<p class="saas-actions">
    <a class="saas-btn" href="{{ route('saas.schools.show', $school->id) }}">{{ __('Open the school') }}</a>
    <a class="saas-btn saas-btn--quiet" href="{{ route('saas.index') }}">{{ __('All schools') }}</a>
</p>
@endsection
