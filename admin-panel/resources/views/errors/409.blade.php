@extends('errors::minimal')

@section('title', __('This screen needs to be reopened'))
@section('code', '409')
@section('message', __('This screen needs to be reopened'))
@section('description', __('The record, school or account changed while this screen was open. Check the latest record before submitting again.'))
