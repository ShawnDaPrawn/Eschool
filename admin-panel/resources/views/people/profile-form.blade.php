@extends('layouts.master')
@section('title', __('Person profile'))
@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('Person profile')" :subtitle="$subject->full_name" />
    <form method="GET" action="{{ route('people-profiles.index', $subject->id) }}">
        <x-page.field name="profile_type" type="select" :label="__('Profile type')" :value="$form['profile_type']" :options="array_combine($form['available_profiles'], array_map(fn ($value) => __(ucfirst($value)), $form['available_profiles']))" />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('View profile') }}</button>
    </form>
    <a class="m3-button m3-button--outlined" href="{{ route('people-profiles.export', ['person' => $subject->id, 'profile_type' => $form['profile_type']]) }}">{{ __('Export permitted fields') }}</a>
    <x-page.form-card id="person-profile" :action="route('people-profiles.update', $subject->id)" method="PUT" :title="__('Profile details')" :draft="false" :submit-label="__('Save profile')">
        <input type="hidden" name="profile_type" value="{{ $form['profile_type'] }}">
        @foreach ($form['categories'] as $category)
            <fieldset><legend>{{ $category['label'] }}</legend>
            @foreach ($category['fields'] as $field)
                @php
                    $key = $field['key'];
                    $value = $field['value'];
                    $type = $field['type'];
                    $name = 'values['.$key.'][text]';
                    $max = $field['validation']->max ?? null;
                @endphp
                @if ($type === 'file')
                    <div data-profile-file="{{ $key }}">
                        <p>{{ $field['label'] }}</p>
                        <p data-file-current>
                            @if (isset($value['file']))
                                <a href="{{ $value['file']['url'] }}" target="_blank" rel="noopener">{{ $value['file']['name'] }}</a>
                            @else
                                {{ __('Not provided') }}
                            @endif
                        </p>
                        @if ($field['editable'] && ! $field['required'])
                            <label><input type="checkbox" name="values[{{ $key }}][clear]" value="1"> {{ __('Remove file when saving') }}</label>
                        @endif
                    </div>
                @elseif (! $field['editable'])
                    <dl><dt>{{ $field['label'] }}</dt><dd>{{ $value === null ? __('Not provided') : (isset($value['choices']) ? implode(', ', $value['choices']) : (isset($value['flag']) ? __($value['flag'] ? 'Yes' : 'No') : $value['text'])) }}</dd></dl>
                @elseif ($type === 'checkbox')
                    <fieldset><legend>{{ $field['label'] }}</legend>
                        <input type="hidden" name="values[{{ $key }}][clear]" value="1">
                        @foreach ($field['options'] as $option)
                            <label><input type="checkbox" name="values[{{ $key }}][choices][]" value="{{ $option }}" @checked(in_array($option, $value['choices'] ?? [], true))> {{ $option }}</label>
                        @endforeach
                    </fieldset>
                @elseif ($type === 'boolean')
                    <input type="hidden" name="values[{{ $key }}][flag]" value="0">
                    <label><input type="checkbox" name="values[{{ $key }}][flag]" value="1" @checked($value['flag'] ?? false)> {{ $field['label'] }}</label>
                @elseif (in_array($type, ['dropdown', 'radio'], true))
                    <x-page.field :name="$name" type="select" :label="$field['label']" :value="$value['text'] ?? null" :options="array_combine($field['options'], $field['options'])" :placeholder="__('Not provided')" :required="$field['required']" />
                @else
                    <x-page.field :name="$name" :type="$type === 'textarea' ? 'textarea' : ($type === 'date' ? 'date' : 'text')" :label="$field['label']" :value="$value['text'] ?? null" :required="$field['required']" :maxlength="$max" :inputmode="$type === 'number' ? 'decimal' : 'text'" autocomplete="off" />
                @endif
            @endforeach
            </fieldset>
        @endforeach
    </x-page.form-card>
    @foreach ($form['categories'] as $category)
        @foreach ($category['fields'] as $field)
            @if ($field['type'] === 'file' && $field['editable'])
                <section class="m3 m3-card m3-card--outlined page-form-card">
                    <div class="m3-card__content page-card__content">
                        <form data-profile-file-upload="{{ $field['key'] }}" method="POST" enctype="multipart/form-data"
                              action="{{ url('/api/v2/people/'.$subject->id.'/profile/fields/'.$field['key'].'/file') }}"
                              data-schema="{{ url('/api/v2/people/'.$subject->id.'/profile') }}"
                              data-asset-prefix="{{ url('/protected/profile-files/people/'.$subject->id) }}/"
                              data-school="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}"
                              data-saved="{{ __('File saved. Other profile edits are still unsaved.') }}"
                              data-failed="{{ __('The file could not be saved. Please try again.') }}"
                              data-refresh="{{ __('File saved. Refresh the profile to view it.') }}">
                            @csrf
                            <input type="hidden" name="profile_type" value="{{ $form['profile_type'] }}">
                            <x-page.field name="document" type="file" :label="$field['label']" accept="application/pdf,image/png,image/jpeg" required :help="__('PDF, PNG or JPEG, up to 5 MB.')" />
                            <button class="m3-button m3-button--outlined" type="submit">{{ __('Upload or replace file') }}</button>
                            <p role="status" aria-live="polite" data-file-status></p>
                        </form>
                    </div>
                </section>
            @endif
        @endforeach
    @endforeach
</div>
@endsection
@section('script')
<script src="{{ asset('assets/js/custom/profile-form.js') }}?v={{ filemtime(public_path('assets/js/custom/profile-form.js')) }}"></script>
@endsection
