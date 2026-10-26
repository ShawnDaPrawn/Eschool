@extends('layouts.master')
@section('title', __('Group material documents'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/activity-workbench.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content activity-workbench">
<x-page.header :title="__('Group material documents')" :subtitle="__('Documents are available only to current authorized group members.')" />
<a href="{{ route('home') }}">{{ __('Back to school') }}</a>
@if($fileErrors)<div role="alert">@foreach($fileErrors as $message)<p>{{ __($message) }}</p>@endforeach<p>{{ __('Refresh the saved documents before choosing another upload.') }}</p></div>@endif
<x-m3.card :title="__('Documents')">
@if($files['can_upload'])
<form method="POST" enctype="multipart/form-data" action="{{ route('activity-files.upload', compact('group','material')) }}" class="activity-form" aria-label="{{ __('Upload document') }}">@csrf
<input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
<input type="hidden" name="material_revision" value="{{ $files['material_revision'] }}">
<x-page.field id="activity-material-document" :restore-old-input="false" type="file" name="document" :label="__('Private document')" accept="application/pdf,image/jpeg,image/png" required />
<p>{{ __('PDF up to 5 MB; JPEG or PNG up to 4 MB.') }}</p>
<x-m3.button type="submit">{{ __('Upload document') }}</x-m3.button>
</form>
@elseif($files['can_remove'])<p>{{ __('New uploads are unavailable. You can still remove a document.') }}</p>@endif
@forelse($files['items'] as $file)
<section><h2 class="activity-document-name">{{ $file['name'] }}</h2><p>{{ (int) ceil($file['bytes']/1024) }} KB</p>
<a href="{{ route('activity-files.read', ['group'=>$group,'material'=>$material,'file'=>$file['id']]) }}">{{ __('Download document') }}</a>
@if($files['can_remove'])
<details><summary>{{ __('Remove document') }}</summary><p>{{ __('Remove document?') }} {{ $file['name'] }}</p>
<form method="POST" action="{{ route('activity-files.remove', ['group'=>$group,'material'=>$material,'file'=>$file['id']]) }}" class="activity-form" aria-label="{{ __('Remove document') }}">@csrf
<input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
<input type="hidden" name="material_revision" value="{{ $files['material_revision'] }}"><input type="hidden" name="revision" value="{{ $file['revision'] }}">
<x-m3.button type="submit" variant="outlined">{{ __('Confirm document removal') }}</x-m3.button></form></details>
@endif</section>
@empty<p>{{ __('No documents have been attached.') }}</p>@endforelse
</x-m3.card></div>
@endsection
