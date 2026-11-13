@extends('layouts.master')

@section('title') {{__('terms_condition')}} @endsection


@section('content')

<div class="content-wrapper m3 page-content">
  <div class="page-header">
    <h3 class="page-title">
      {{__('terms_condition')}}
    </h3>
    <p class="text-muted">{{ __('school_privacy_and_terms_hint') }}</p>
  </div>
  <div class="panel-form-grid">
    <div class="">
      <div class="m3-card m3-card--outlined">
        <div class="m3-card__content panel-record-card">
          <form id="formdata" class="setting-form" action="{{url('setting-update')}}" method="POST" novalidate="novalidate" data-required-message="please_enter_terms_condition">
            @csrf
            <div class="panel-form-grid">
              <input type="hidden" name="type" id="type" value="{{$type}}">
              
              <div class="form-group  ">
                <textarea id="rich_text_message" name="setting_message" required placeholder="{{__('terms_condition')}}">{{isset($settings->message) ? $settings->message : ''}}</textarea>
                
              </div>
            </div>
            <input class="m3-button m3-button--filled" type="submit" value="{{__('submit')}}">
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
