@extends('layouts.master')

@section('title')
    {{__('notification').' '. __('setting')}}
@endsection


@section('content')

    <div class="content-wrapper m3 page-content">
        <x-page.header :title="__('notification').' '. __('setting')" />
        <div class="panel-form-grid">
            <div class="">
                <div class="m3-card m3-card--outlined">
                    <div class="m3-card__content panel-record-card">
                        <form id="formdata" class="general-setting" action="{{url('notification-setting')}}" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="panel-form-grid">
                                <div class="form-group  ">
                                    <label>{{  __('sender_id') }}</label>
                                    <input name="sender_id" value="{{ isset($settings['sender_id']) ? $settings['sender_id'] : '' }}" type="text" required placeholder="{{  __('sender_id') }}" class="m3-text-field__input"/>
                                </div>
                                <div class="form-group  ">
                                    <label>{{  __('firebase_project_id')}}</label>
                                    <input name="project_id" value="{{ isset($settings['project_id']) ? $settings['project_id'] : '' }}" type="text" required placeholder="{{  __('firebase_project_id')}}" class="m3-text-field__input"/>
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('service_account_file') }} <span class="text-danger">*<small>({{ __('only_json_file_allowed') }})</small></span></label>
                                    {{-- Set in the environment (FCM_SERVICE_ACCOUNT_JSON): no upload here. --}}
                                    @if (\App\Services\Platform\Secrets\SecretSettings::fromEnvironment('service_account_file'))
                                        <input type="text" class="m3-text-field__input" disabled placeholder="{{ __('set_in_environment') }}"/>
                                    @else
                                    <input type="file" name="service_account_file" class="file-upload-default"/>
                                    <div class="input-group  mb-3">
                                        <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('service_account_file.json') }}"/>
                                        <span class="input-group-append">
                                          <button class="file-upload-browse m3-button m3-button--filled" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    @endif
                                    {{-- The key file is private; it is never linked for download. --}}
                                    @if (! empty($settings['service_account_file']))
                                        <strong>{{ __('firebase_json_file') }}</strong> &#10003;
                                    @endif

                                </div>

                            </div>
                            <input class="m3-button m3-button--filled" type="submit" value="{{ __('submit') }}">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
