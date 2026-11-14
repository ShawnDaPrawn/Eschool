@extends('errors::minimal')

@section('title', __('The service is temporarily unavailable'))
@section('code', '503')
@section('message', __('The service is temporarily unavailable'))
@section('description', __('Try opening the page again later. If you were saving, check the record before submitting again; the outcome has not been confirmed.'))
