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
                @if (! $field['editable'])
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
</div>
@endsection
@section('script')
<script src="{{ asset('assets/js/custom/profile-form.js') }}?v={{ filemtime(public_path('assets/js/custom/profile-form.js')) }}"></script>
@endsection
