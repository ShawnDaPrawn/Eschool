@extends('layouts.master')
@section('title', __('Group conversations'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/group-conversations.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content group-conversations" data-group-conversations
 @if($group) data-lease-url="{{ route('group-chat-panel.lease',['group'=>$group->id,'thread'=>$conversation['thread']['id']??null,'expected_account_id'=>$accountId,'expected_school_id'=>$schoolId]) }}" @endif>
<x-page.header :title="__('Group conversations')" :subtitle="__('Only your current allocated groups and their permitted conversations appear here.')" />
<div data-private-chat-content>
@if($messages)<div role="alert"><h2>{{ __('Review this change') }}</h2><ul>@foreach($messages as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(!$group)
@forelse($groups as $item)<article class="m3-card m3-card--outlined"><h2><a href="{{ route('group-chat-panel.group',['group'=>$item->id]) }}">{{ $item->name }}</a></h2><p>{{ $item->discipline }}</p></article>@empty<p>{{ __('No currently allocated groups are available.') }}</p>@endforelse
<nav aria-label="{{ __('Group pages') }}">@if($groups->previousPageUrl())<a href="{{ $groups->previousPageUrl() }}">{{ __('Previous page') }}</a>@endif @if($groups->nextPageUrl())<a href="{{ $groups->nextPageUrl() }}">{{ __('Next page') }}</a>@endif</nav>
@else
<nav><a href="{{ route('group-chat-panel.index') }}">{{ __('All allocated groups') }}</a><a href="{{ route('group-chat-panel.group',['group'=>$group->id]) }}">{{ __('Refresh group conversations') }}</a></nav>
<h2>{{ $group->name }}</h2>
<nav class="panel-local-nav" aria-label="{{ __('Group conversation tasks') }}"><a href="{{ route('group-chat-panel.group',['group'=>$group->id]) }}" @if($screen==='list') aria-current="page" @endif>{{ __('Conversations') }}</a>@if($canWrite && $listing['writable'])<a href="{{ route('group-chat-panel.group',['group'=>$group->id,'screen'=>'create']) }}" @if($screen==='create') aria-current="page" @endif>{{ __('Start a conversation') }}</a>@endif</nav>
@if($conversation)
<section aria-label="{{ __('Selected conversation') }}">
<h2>{{ $conversation['thread']['title'] }}</h2>
<p>{{ __(ucfirst($conversation['thread']['audience'])) }} · {{ $conversation['thread']['private']?__('Private question'):__('Group announcement') }} · {{ $conversation['thread']['closed']?__('Closed'):__('Open') }}</p>
@foreach($conversation['items'] as $item)
<article class="m3-card m3-card--outlined"><h3>{{ $item['sender_name'] }}</h3><p>{{ $item['private']?__('Private to the author and group staff'):__('Shared with this conversation audience') }} · {{ $item['language'] }}</p>
<p class="chat-message">{{ $item['removed']?__('Message removed'):$item['body'] }}</p>
@if($item['can_remove'] && $canWrite)
<details><summary>{{ __('Remove this message') }}</summary><form method="post" action="{{ route('group-chat-panel.remove',['group'=>$group->id,'thread'=>$conversation['thread']['id'],'message'=>$item['id']]) }}">
@csrf @include('activities.chat-context',['id'=>(string)\Illuminate\Support\Str::uuid()])
<input type="hidden" name="revision" value="{{ $item['revision'] }}">
<label>{{ __('Reason for removal') }}<textarea name="reason" required maxlength="2000"></textarea></label>
<button class="m3-button m3-button--outlined" type="submit">{{ __('Remove message') }}</button></form></details>
@endif
</article>
@endforeach
@if($conversation['next_before'])<a href="{{ route('group-chat-panel.show',['group'=>$group->id,'thread'=>$conversation['thread']['id'],'before_message_id'=>$conversation['next_before']]) }}">{{ __('Earlier messages') }}</a>@endif
@if($canWrite && $conversation['can_post'])
<form method="post" action="{{ route('group-chat-panel.write',['group'=>$group->id,'thread'=>$conversation['thread']['id'],'operation'=>'send']) }}">
@csrf @include('activities.chat-context',['id'=>$operationId])
<input type="hidden" name="visibility" value="{{ $draftOperation==='send' ? ($draft['visibility']??'thread') : 'thread' }}">
<label for="chat-reply-target">{{ __('Reply destination') }}</label><select id="chat-reply-target" name="reply_to"><option value="">{{ __('This conversation audience') }}</option>
@foreach($conversation['items'] as $item)@if($item['private'] && !$item['removed'] && $item['sender_persona']!=='staff')<option value="{{ $item['id'] }}" @selected($draftOperation==='send' && ($draft['reply_to']??null)===$item['id'])>{{ __('Private reply to') }} {{ $item['sender_name'] }} ({{ $item['id'] }})</option>@endif @endforeach
</select>
<p>{{ __('Choosing a private family message keeps your response private to that author and group staff.') }}</p>
@include('activities.chat-text',['max'=>$conversation['max_characters'],'prefix'=>'reply','draft'=>$draftOperation==='send'?$draft:[]])
<button class="m3-button m3-button--filled" type="submit">{{ __('Send reply') }}</button></form>
@endif
@if($canWrite && ($conversation['can_close'] || $conversation['can_reopen']))
<form method="post" action="{{ route('group-chat-panel.write',['group'=>$group->id,'thread'=>$conversation['thread']['id'],'operation'=>'state']) }}">
@csrf @include('activities.chat-context',['id'=>(string)\Illuminate\Support\Str::uuid()])
<input type="hidden" name="revision" value="{{ $conversation['thread']['revision'] }}"><input type="hidden" name="closed" value="{{ $conversation['thread']['closed']?'0':'1' }}">
<button class="m3-button m3-button--outlined" type="submit">{{ $conversation['thread']['closed']?__('Reopen conversation'):__('Close conversation') }}</button></form>
@endif
</section>
@endif
@if($screen==='list')
<section aria-label="{{ __('Available conversations') }}"><h2>{{ __('Available conversations') }}</h2>
@forelse($listing['items'] as $item)<article class="m3-card m3-card--outlined"><h3><a href="{{ route('group-chat-panel.show',['group'=>$group->id,'thread'=>$item['id']]) }}">{{ $item['title'] }}</a></h3><p>{{ __(ucfirst($item['audience'])) }} · {{ $item['private']?__('Private question'):__('Group announcement') }} · {{ __('Unread messages') }}: {{ $item['unread'] }}</p></article>@empty<p>{{ __('No conversations are available.') }}</p>@endforelse
@if($listing['next_before'])<a href="{{ route('group-chat-panel.group',['group'=>$group->id,'before'=>$listing['next_before']]) }}">{{ __('Earlier conversations') }}</a>@endif
</section>
@endif
@if($screen==='create' && $canWrite && $listing['writable'])
<section class="m3-card m3-card--outlined"><h2>{{ __('Start a conversation') }}</h2><form method="post" action="{{ route('group-chat-panel.create',['group'=>$group->id]) }}">
@csrf @include('activities.chat-context',['id'=>$draftOperation==='create'?$operationId:(string)\Illuminate\Support\Str::uuid()])
<label for="chat-create-title">{{ __('Title') }}</label><input id="chat-create-title" name="title" value="{{ $draftOperation==='create'?($draft['title']??''):'' }}" required maxlength="191">
<label for="chat-create-kind">{{ __('Conversation type') }}</label><select id="chat-create-kind" name="kind"><option value="announcement" @selected(($draft['kind']??'announcement')==='announcement')>{{ __('Group announcement') }}</option><option value="question" @selected(($draft['kind']??null)==='question')>{{ __('Private staff question') }}</option></select>
<label for="chat-create-audience">{{ __('Audience') }}</label><select id="chat-create-audience" name="audience">@foreach(['learners'=>'staff_learners','guardians'=>'staff_guardians','staff'=>'staff_staff'] as $audience=>$policy)@if($listing['policy'][$policy])<option value="{{ $audience }}" @selected(($draft['audience']??null)===$audience)>{{ __(ucfirst($audience)) }}</option>@endif @endforeach</select>
@include('activities.chat-text',['max'=>$listing['max_characters'],'prefix'=>'create','draft'=>$draftOperation==='create'?$draft:[]])
<button class="m3-button m3-button--filled" type="submit">{{ __('Create conversation') }}</button></form></section>
@elseif(!$canWrite)<p>{{ __('This group is currently read only.') }}</p>@endif
@endif
</div>
<div data-chat-retired hidden role="status"><p>{{ __('This private view has expired. Reload to check your current access.') }}</p><a href="{{ route('group-chat-panel.index') }}">{{ __('Reload group conversations') }}</a></div>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/group-conversations.js') }}" defer></script>@endsection
