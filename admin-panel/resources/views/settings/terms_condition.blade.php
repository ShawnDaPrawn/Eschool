@extends('layouts.master')

@section('title') {{__('terms_condition')}} @endsection


@section('content')

<div class="content-wrapper">
  <div class="page-header">
    <h3 class="page-title">
      {{__('terms_condition')}}
    </h3>
    <p class="text-muted">{{ __('school_privacy_and_terms_hint') }}</p>
  </div>
  <div class="row grid-margin">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body">
          <form id="formdata" class="setting-form" action="{{url('setting-update')}}" method="POST" novalidate="novalidate" data-required-message="please_enter_terms_condition">
            @csrf
            <div class="row">
              <input type="hidden" name="type" id="type" value="{{$type}}">
              
              <div class="form-group col-md-12 col-sm-12">
                <textarea id="rich_text_message" name="setting_message" required placeholder="{{__('terms_condition')}}">{{isset($settings->message) ? $settings->message : ''}}</textarea>
                
              </div>
            </div>
            <input class="btn btn-theme" type="submit" value="{{__('submit')}}">
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
