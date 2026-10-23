@extends('layouts.master')
@section('title', __('Profile fields'))
@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('Profile fields')" :subtitle="__('Arrange the fields offered on each person profile. Locked defaults keep their type and privacy protection.')" />
    <form method="GET" action="{{ route('profile-configuration.index') }}">
        <x-page.field name="profile_type" type="select" :label="__('Profile type')" :value="$profile" :options="array_combine(config('profile_fields.types'), array_map(fn ($value) => __(ucfirst($value)), config('profile_fields.types')))" />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('View fields') }}</button>
    </form>
    @if ($errors->any())<div role="alert"><p>{{ $errors->first() }}</p></div>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @can($category ? 'form-field-edit' : 'form-field-create')
    <section class="m3 m3-card m3-card--outlined page-form-card">
        <div class="m3-card__content page-card__content">
        <h2>{{ $category ? __('Edit category') : __('Add category') }}</h2>
        <form method="POST" action="{{ $category ? route('profile-configuration.categories.update', $category->id) : route('profile-configuration.categories.store') }}">
            @csrf
            @if ($category) @method('PUT') @endif
            <input type="hidden" name="profile_type" value="{{ $profile }}">
            <x-page.field name="label" :label="__('Category label')" :value="$category?->label" required maxlength="120" />
            <x-page.field name="key" :label="__('Category key')" :value="$category?->key" :readonly="$category !== null" required pattern="[a-z][a-z0-9_]{0,63}" :help="__('Use lowercase letters, numbers and underscores. The key stays the same after creation.')" />
            <x-page.field name="sort_order" type="number" :label="__('Category order')" :value="$category?->sort_order ?? 50" min="0" max="100000" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Save category') }}</button>
        </form>
        </div>
    </section>
    @endcan
    @can($field ? 'form-field-edit' : 'form-field-create')
    @if ($categories->isNotEmpty())
    <section class="m3 m3-card m3-card--outlined page-form-card">
        <div class="m3-card__content page-card__content">
        <h2>{{ $field ? __('Edit field') : __('Add field') }}</h2>
        <form method="POST" action="{{ $field ? route('profile-configuration.definitions.update', $field->id) : route('profile-configuration.definitions.store') }}">
            @csrf
            @if ($field) @method('PUT') @endif
            <x-page.field name="category_id" type="select" :label="__('Field category')" :value="$field?->category_id" :options="$categories->pluck('label', 'id')->all()" required />
            <x-page.field name="label" id="definition-label" :label="__('Field label')" :value="$field?->label" required maxlength="120" />
            <x-page.field name="key" id="definition-key" :label="__('Field key')" :value="$field?->key" :readonly="$field !== null" required pattern="[a-z][a-z0-9_]{0,63}" />
            @if ($field?->platform_locked)
                <input type="hidden" name="type" value="{{ $field->type }}">
                <p>{{ __('Locked field') }}: {{ __($field->type) }}</p>
            @else
                <x-page.field name="type" type="select" :label="__('Field type')" :value="$field?->type ?? 'text'" :options="array_combine(config('profile_fields.field_types'), array_map(fn ($value) => __(ucfirst($value)), config('profile_fields.field_types')))" required />
            @endif
            <x-page.field name="options_text" type="textarea" :label="__('Choices, one per line')" :value="implode(chr(10), $field?->options ?? [])" />
            <div class="m3-grid">
                <div class="m3-col-12 m3-col-expanded-6"><x-page.field name="validation[min]" type="number" :label="__('Minimum length or number')" :value="$field?->validation['min'] ?? null" min="0" max="10000" /></div>
                <div class="m3-col-12 m3-col-expanded-6"><x-page.field name="validation[max]" type="number" :label="__('Maximum length or number')" :value="$field?->validation['max'] ?? null" min="1" max="10000" /></div>
            </div>
            @foreach (['email' => 'Validate email format', 'past' => 'Past dates only'] as $option => $label)
                <input type="hidden" name="validation[{{ $option }}]" value="0">
                <label><input type="checkbox" name="validation[{{ $option }}]" value="1" @checked(old('validation.'.$option, $field?->validation[$option] ?? false))> {{ __($label) }}</label>
            @endforeach
            @foreach (['visible_to' => 'Visible to', 'editable_by' => 'Editable by'] as $group => $label)
                <fieldset><legend>{{ __($label) }}</legend>
                @foreach (config('profile_fields.roles') as $role)
                    <label><input type="checkbox" name="{{ $group }}[]" value="{{ $role }}" @checked(in_array($role, old($group, $field?->{$group} ?? ['owner', 'school_admin']), true))> {{ __(ucfirst(str_replace('_', ' ', $role))) }}</label>
                @endforeach
                </fieldset>
            @endforeach
            @foreach (['required' => 'Required field', 'sensitive' => 'Sensitive field', 'hidden' => 'Hidden field'] as $option => $label)
                <input type="hidden" name="{{ $option }}" value="0">
                <label><input type="checkbox" name="{{ $option }}" value="1" @checked(old($option, $field?->{$option} ?? false))> {{ __($label) }}</label>
            @endforeach
            <x-page.field name="sort_order" id="definition-order" type="number" :label="__('Field order')" :value="$field?->sort_order ?? 50" min="0" max="100000" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Save field') }}</button>
            @if ($field)<a class="m3-button m3-button--text" href="{{ route('profile-configuration.index', ['profile_type' => $profile]) }}">{{ __('Cancel') }}</a>@endif
        </form>
        </div>
    </section>
    @endif
    @endcan
    @foreach ($categories as $item)
    <section class="m3 m3-card m3-card--outlined page-form-card">
        <div class="m3-card__content page-card__content">
        <h2>{{ $item->label }}</h2>
        @can('form-field-edit')<a class="m3-button m3-button--text" href="{{ route('profile-configuration.index', ['profile_type' => $profile, 'category' => $item->id]) }}">{{ __('Edit category') }} {{ $item->label }}</a>@endcan
        @if ($item->definitions->isEmpty())
            @can('form-field-delete')<form method="POST" action="{{ route('profile-configuration.categories.destroy', $item->id) }}">@csrf @method('DELETE')<button class="m3-button m3-button--text" type="submit">{{ __('Remove category') }} {{ $item->label }}</button></form>@endcan
        @endif
        <ul>
        @foreach ($item->definitions as $definition)
            <li>{{ $definition->label }} — {{ __($definition->type) }} @if($definition->platform_locked) ({{ __('Locked field') }}) @endif
                @can('form-field-edit')<a class="m3-button m3-button--text" href="{{ route('profile-configuration.index', ['profile_type' => $profile, 'field' => $definition->id]) }}">{{ __('Edit') }} {{ $definition->label }}</a>@endcan
                @if (! $definition->platform_locked)
                    @can('form-field-delete')<form method="POST" action="{{ route('profile-configuration.definitions.destroy', $definition->id) }}">@csrf @method('DELETE')<button class="m3-button m3-button--text" type="submit">{{ __('Remove field') }} {{ $definition->label }}</button></form>@endcan
                @endif
            </li>
        @endforeach
        </ul>
        </div>
    </section>
    @endforeach
</div>
@endsection
