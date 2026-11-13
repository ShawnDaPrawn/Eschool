@extends('layouts.master')

@section('title') {{__('about_us')}} @endsection


@section('content')

<div class="content-wrapper m3 page-content">
  <x-page.header :title="__('about_us')" />
  <div class="panel-form-grid">
    <div class="">
      <div class="m3-card m3-card--outlined">
        <div class="m3-card__content panel-record-card">
          <form id="formdata" class="setting-form" action="{{url('setting-update')}}" method="POST" novalidate="novalidate" data-required-message="please_enter_about_us">
            @csrf
            <div class="panel-form-grid">
              <div class="form-group  ">
                {{-- <label>{{__('about_us')}}</label> --}}
                <input type="hidden" name="type" id="type" value="{{$type}}">
                
                <textarea id="rich_text_message" name="setting_message" required placeholder="{{__('about_us')}}">{{isset($settings->message) ? $settings->message : ''}}</textarea>
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
