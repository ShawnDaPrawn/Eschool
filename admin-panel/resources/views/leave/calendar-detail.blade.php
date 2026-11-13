@extends('layouts.master')
@section('title', __('Leave request'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Leave request')" :subtitle="$record->user?->full_name ?? __('Former person')"><x-slot:actions><a href="{{ route('leave-calendar.index',['week'=>\Carbon\Carbon::parse($record->from_date)->toDateString()]) }}" class="m3-button m3-button--text">{{ __('Back to calendar') }}</a></x-slot:actions></x-page.header>
<section class="page-section"><p>{{ $record->from_date }} – {{ $record->to_date }}</p><p>{{ __('Decision') }}: {{ __([0=>'Pending',1=>'Approved',2=>'Rejected'][(int)$record->status] ?? 'Pending') }}</p><h2>{{ __('Original reason') }}</h2><p>{{ $record->reason }}</p>
@if($record->reason_of_rejection)<p>{{ __('Reason for rejection') }}: {{ $record->reason_of_rejection }}</p>@endif
<ul>@foreach($record->leave_detail as $day)<li>{{ $day->date }} · {{ $day->type }}</li>@endforeach</ul>
@foreach($record->file as $file)@if(in_array(strtolower(parse_url($file->file_url,PHP_URL_SCHEME) ?? ''),['http','https'],true))<p><a href="{{ $file->file_url }}" target="_blank" rel="noopener noreferrer">{{ __('Attachment') }} {{ $loop->iteration }}</a></p>@endif
@endforeach
</section>
@can('leave-approve')<a class="m3-button m3-button--outlined" href="{{ route('leave-request.index') }}">{{ __('Open staff review queue') }}</a>@endcan
@can('student-leave-approve')<a class="m3-button m3-button--outlined" href="{{ route('student-leave-request.index') }}">{{ __('Open learner review queue') }}</a>@endcan
</div>
@endsection
