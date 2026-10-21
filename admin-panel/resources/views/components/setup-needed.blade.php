{{-- Tells the admin which records must exist before this form can be used. --}}
@props(['missing' => []])
@if (count(array_filter($missing)))
    <div class="alert alert-warning" role="alert">
        <x-icon name="alert" :size="16" /> {{ __('before_using_this_form_create') }}
        @foreach (array_filter($missing) as $label => $url)
            <a href="{{ $url }}" class="alert-link">{{ __($label) }}</a>@if (!$loop->last), @endif
        @endforeach
    </div>
@endif
