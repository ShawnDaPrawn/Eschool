@extends('layouts.master')
@section('title', __($readers ? 'Card readers' : 'School identity cards'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__($readers ? 'Card readers' : 'School identity cards')" :subtitle="__('Only current school identities and explicitly authorized readers can use these cards.')" />
@unless($canWrite)<p role="status">{{ __('Card changes are read-only. You can still download permitted print files, freeze a lost card, or revoke a reader.') }}</p>@endunless
@if($messages)<div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
@if($readers)
<p><a href="{{ route('card-reader.index') }}" target="_blank" rel="noopener noreferrer">{{ __('Open online card reader') }}</a></p>
<section class="page-section" aria-labelledby="readers-heading"><h2 id="readers-heading">{{ __('Registered readers') }}</h2>
@foreach($devices as $device)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $device->name }}</h3><p>{{ ucfirst($device->kind) }} · {{ $device->status }} · {{ $device->expires_at->toIso8601String() }}</p>
@if($device->status==='active')<form method="POST" action="{{ route('card-readers-panel.write',['device'=>$device->id,'operation'=>'revoke']) }}">@include('cards.hidden')<button class="m3-button m3-button--outlined" type="submit">{{ __('Revoke reader') }}</button></form>@endif
</div></article>@endforeach</section>
<section class="page-section" aria-labelledby="reader-new"><h2 id="reader-new">{{ __('Register a reader') }}</h2><p>{{ __('Register only a school-controlled gate or encoder. A reader never receives a staff or learner account session.') }}</p>
<form method="POST" action="{{ route('card-readers-panel.register') }}">@include('cards.hidden')
<x-page.field id="reader-name" name="name" :label="__('Reader name')" maxlength="100" required :restore-old-input="false" />
<x-page.field id="reader-kind" name="kind" type="select" :label="__('Reader purpose')" :options="$readerKinds" required :restore-old-input="false" />
<x-page.field id="reader-key" name="public_key" :label="__('Device public encryption key')" maxlength="64" required :restore-old-input="false" :help="__('Use the public key generated on the controlled reader. Never enter a private key.')" />
<x-page.field id="reader-days" name="days" type="number" :label="__('Valid for days')" min="1" max="30" value="1" required :restore-old-input="false" />
<button class="m3-button m3-button--filled" type="submit" @disabled(!$canWrite)>{{ __('Register reader') }}</button></form></section>
@else
<section class="page-section" aria-labelledby="card-people"><h2 id="card-people">{{ __('Choose a cardholder') }}</h2><form method="GET" action="{{ route('cards-panel.index') }}">
<x-page.field id="card-type" name="type" type="select" :label="__('Cardholder type')" :options="['learner'=>__('Learner'),'staff'=>__('Staff'),'guardian'=>__('Guardian')]" :value="$type" :restore-old-input="false" />
<x-page.field id="card-search" name="search" :label="__('Search by name')" :value="$search" maxlength="100" :restore-old-input="false" /><button class="m3-button m3-button--outlined" type="submit">{{ __('Find cardholders') }}</button></form>
<ul>@foreach($people['items'] as $person)<li><a href="{{ route('cards-panel.index',['type'=>$type,'person_id'=>$person['id']]) }}">{{ $person['name'] }}</a></li>@endforeach</ul>
@if($people['next_page'])<a href="{{ route('cards-panel.index',['type'=>$type,'search'=>$search,'page'=>$people['next_page']]) }}">{{ __('More cardholders') }}</a>@endif
</section>
@if($selected)
<section class="page-section" aria-labelledby="card-selected"><h2 id="card-selected">{{ $selected->full_name }}</h2>
<form method="POST" action="{{ route('cards-panel.create',['operation'=>'portrait']) }}" enctype="multipart/form-data">@include('cards.hidden')<input type="hidden" name="person_id" value="{{ $selected->id }}"><input type="hidden" name="type" value="{{ $type }}">
<x-page.field id="card-portrait" name="photo" type="file" :label="__('Private card portrait')" accept="image/jpeg,image/png" required :restore-old-input="false" /><button class="m3-button m3-button--outlined" type="submit" @disabled(!$canWrite)>{{ __('Save private portrait') }}</button></form>
<form method="POST" action="{{ route('cards-panel.create',['operation'=>'issue']) }}">@include('cards.hidden')<input type="hidden" name="person_id" value="{{ $selected->id }}"><input type="hidden" name="type" value="{{ $type }}"><button class="m3-button m3-button--filled" type="submit" @disabled(!$canWrite)>{{ __('Issue card') }}</button></form>
</section>
@endif
<section class="page-section" aria-labelledby="issued-cards"><h2 id="issued-cards">{{ __('Issued cards') }}</h2>
@foreach($listing['items'] as $card)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $names[$card['person_id']] ?? __('Former school member') }}</h3><p>{{ __(ucfirst($card['type'])) }} · {{ __(ucfirst($card['status'])) }} · {{ substr($card['id'],-8) }}</p>
@if($card['status']==='active' && $canEncode)<p><a href="{{ route('cards-encoding.index',['card'=>$card['id'],'expected_account_id'=>$account,'expected_school_id'=>$school]) }}">{{ __('Personalize secure chip') }}</a></p>@endif
@if($card['status']==='active')<img src="{{ route('cards-panel.portrait',['card'=>$card['id'],'expected_account_id'=>$account,'expected_school_id'=>$school]) }}" width="120" height="150" alt="{{ __('Private card portrait') }}" loading="lazy">
<form method="POST" action="{{ route('cards-panel.print') }}">@include('cards.hidden')<input type="hidden" name="card_ids[]" value="{{ $card['id'] }}"><x-page.field :id="'card-layout-'.$card['id']" name="layout" type="select" :label="__('Printer layout')" :options="['landscape'=>__('CR80 landscape'),'portrait'=>__('CR80 portrait')]" :restore-old-input="false" /><button class="m3-button m3-button--outlined" type="submit">{{ __('Download printer PDF') }}</button></form>@endif
@if(in_array($card['status'],['active','frozen']))
@foreach($card['status']==='active'?['freeze','replace']:['restore','replace'] as $action)
<form method="POST" action="{{ route('cards-panel.change',['card'=>$card['id'],'operation'=>$action]) }}">@include('cards.hidden')<x-page.field :id="'card-'.$action.'-'.$card['id']" name="reason" :label="__('Reason')" maxlength="2000" required :restore-old-input="false" />
@if($action==='restore' && $card['has_chip'])<x-page.field :id="'card-picc-'.$card['id']" name="picc" :label="__('Fresh secure card tap')" maxlength="32" required :restore-old-input="false" /><x-page.field :id="'card-mac-'.$card['id']" name="mac" :label="__('Tap authenticator')" maxlength="16" required :restore-old-input="false" />@endif
<button class="m3-button m3-button--outlined" type="submit" @disabled(!$canWrite && $action !== 'freeze')>{{ __(['freeze'=>'Freeze card','replace'=>'Replace card','restore'=>'Restore recovered card'][$action]) }}</button></form>
@endforeach
@endif
</div></article>@endforeach
@if($listing['next_page'])<a href="{{ route('cards-panel.index',['page'=>$listing['next_page'],'type'=>$type]) }}">{{ __('Older cards') }}</a>@endif
</section>
@endif
</div>
@endsection
