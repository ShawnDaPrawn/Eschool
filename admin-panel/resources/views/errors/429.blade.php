@extends('errors::minimal')

@section('title', __('Please wait before trying again'))
@section('code', '429')
@section('message', __('Please wait before trying again'))
@section('description', __('Too many requests were received. Wait briefly, then open the latest record before trying the action again.'))
