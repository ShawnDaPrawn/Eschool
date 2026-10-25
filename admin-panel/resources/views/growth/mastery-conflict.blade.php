@extends('layouts.master')
@section('title', __('Mastery configuration changed'))
@section('content')<div class="content-wrapper m3 page-content"><x-page.header :title="__('Mastery configuration changed')" :subtitle="__('Your changes were not saved. Compare them with the current graph before editing again.')" />
<h2>{{ __('Your submitted prerequisites') }}</h2><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ json_encode($submitted,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
<h2>{{ __('Current prerequisites') }}</h2><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ json_encode($current,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
<a class="m3-button m3-button--filled" href="{{ route('mastery.index',['subject_id'=>$current['subject_id']]) }}">{{ __('Reload current graph') }}</a></div>@endsection
