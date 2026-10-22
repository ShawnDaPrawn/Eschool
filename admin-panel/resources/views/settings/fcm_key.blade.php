@extends('layouts.master')

@section('title')
    {{__('notification').' '. __('setting')}}
@endsection


@section('content')

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{__('notification').' '. __('setting')}}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form id="formdata" class="general-setting" action="{{url('notification-setting')}}" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{  __('sender_id') }}</label>
                                    <input name="sender_id" value="{{ isset($settings['sender_id']) ? $settings['sender_id'] : '' }}" type="text" required placeholder="{{  __('sender_id') }}" class="form-control"/>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{  __('firebase_project_id')}}</label>
                                    <input name="project_id" value="{{ isset($settings['project_id']) ? $settings['project_id'] : '' }}" type="text" required placeholder="{{  __('firebase_project_id')}}" class="form-control"/>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{ __('service_account_file') }} <span class="text-danger">*<small>({{ __('only_json_file_allowed') }})</small></span></label>
                                    {{-- Set in the environment (FCM_SERVICE_ACCOUNT_JSON): no upload here. --}}
                                    @if (\App\Services\Platform\Secrets\SecretSettings::fromEnvironment('service_account_file'))
                                        <input type="text" class="form-control" disabled placeholder="{{ __('set_in_environment') }}"/>
                                    @else
                                    <input type="file" name="service_account_file" class="file-upload-default"/>
                                    <div class="input-group col-xs-12 mb-3">
                                        <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('service_account_file.json') }}"/>
                                        <span class="input-group-append">
                                          <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    @endif
                                    {{-- The key file is private; it is never linked for download. --}}
                                    @if (! empty($settings['service_account_file']))
                                        <strong>{{ __('firebase_json_file') }}</strong> &#10003;
                                    @endif

                                </div>

                            </div>
                            <input class="btn btn-theme" type="submit" value="{{ __('submit') }}">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
