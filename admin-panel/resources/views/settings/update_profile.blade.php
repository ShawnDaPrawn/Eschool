@extends('layouts.master')
@section('title', __('My profile'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('My profile')" :subtitle="__('Update your contact details and profile image.')" />
    <x-page.form-card id="my-profile-form" :action="route('update-profile')" :title="__('Personal details')" :draft="false" :write="false" :submit-label="__('Save profile')">
        <div class="panel-form-grid">
            <x-page.field name="first_name" :label="__('first_name')" :value="$admin_data->first_name" required />
            <x-page.field name="last_name" :label="__('last_name')" :value="$admin_data->last_name" required />
            <x-page.field name="mobile" type="tel" :label="__('mobile')" :value="$admin_data->mobile" required inputmode="numeric" maxlength="16" />
            <x-page.field name="gender" type="select" :label="__('gender')" :value="Str::lower($admin_data->gender)" :options="['male'=>__('male'),'female'=>__('female')]" required />
            <x-page.field name="dob" type="date" :label="__('dob')" :value="$admin_data->dob ? date('Y-m-d', strtotime($admin_data->dob)) : null" required />
            <x-page.field name="image" type="file" :label="__('image')" accept="image/jpeg,image/png" :help="__('JPEG or PNG, up to 2 MB. Leave empty to keep the current image.')" />
        </div>
        <p>{{ __('Sign-in email') }}: {{ $admin_data->email }}</p>
        <p>{{ __('Your sign-in email is managed by the school.') }}</p>
        <x-page.field name="current_address" type="textarea" :label="__('current_address')" :value="$admin_data->current_address" required />
        <x-page.field name="permanent_address" type="textarea" :label="__('permanent_address')" :value="$admin_data->permanent_address" required />
    </x-page.form-card>
</div>
@endsection
