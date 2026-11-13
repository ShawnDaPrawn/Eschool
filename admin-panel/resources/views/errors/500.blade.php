@extends('errors::minimal')

@section('title', __('We could not confirm the outcome'))
@section('code', '500')
@section('message', __('We could not confirm the outcome'))
@section('description', __('If you were saving a change, it may have reached the server. Check the latest record before submitting again, especially for payments and approvals.'))
