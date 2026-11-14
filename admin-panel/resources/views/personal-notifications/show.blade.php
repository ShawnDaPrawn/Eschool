@extends('layouts.master')
@section('title', $notice['title'])
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$notice['title']"><x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('panel.inbox.index') }}">{{ __('Back to inbox') }}</a></x-slot:actions></x-page.header>
    <article class="panel-record-card">
        <time datetime="{{ $notice['date'] }}">{{ \Carbon\Carbon::parse($notice['date'])->format('d M Y H:i') }}</time>
        <div class="panel-preserve-lines">{{ $notice['body'] }}</div>
        @unless($notice['has_body'])<p>{{ __('Private details stay in their authorised school workspace.') }}</p>@endunless
        @if($source)<a class="m3-button m3-button--filled" href="{{ $source['url'] }}">{{ __('Open') }} {{ $source['label'] }}</a>@endif
    </article>
</div>
@endsection
