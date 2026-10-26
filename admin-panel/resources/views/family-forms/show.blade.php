@extends('layouts.master')
@section('title', $record['title'])
@section('content')<div class="content-wrapper m3 page-content">
<x-page.header :title="$record['title']" :subtitle="__('Read the complete wording and school dates.')" />
@if($messages)<div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
<p>{{ __('School timezone') }}: {{ $access['timezone'] }} · {{ __(ucfirst($record['status'])) }}</p>
<dl>@foreach(['event_starts_at'=>'Activity starts','event_ends_at'=>'Activity ends','respond_by'=>'Positive response deadline'] as $field=>$label)<dt>{{ __($label) }}</dt><dd>{{ $dates[$field] }}</dd>@endforeach</dl>
<section aria-labelledby="consent-wording-heading"><h2 id="consent-wording-heading">{{ __('Exact consent wording') }}</h2><p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $record['wording'] }}</p></section>
@if($managed)
<section aria-labelledby="consent-roster-heading"><h2 id="consent-roster-heading">{{ __('Current participant permissions') }}</h2>
@foreach($record['recipients'] as $row)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $row['name'] }}</h3><p>{{ $row['effective']?__('Current signed permission'):__('Permission is not current') }}</p><p>{{ __('Last recorded decision') }}: {{ $row['decision']===null?__('No response'):__($row['decision']==='yes'?'Yes':'No') }}</p></div></article>@endforeach
@if($record['next_page'])<a href="{{ route('family-forms-panel.managed',['consent'=>$record['id'],'page'=>$record['next_page']]) }}">{{ __('More participants') }}</a>@endif</section>
@if($record['status']==='open')<form method="POST" action="{{ route('family-forms-panel.cancel',['consent'=>$record['id']]) }}">@include('family-forms.hidden')<input type="hidden" name="revision" value="{{ $record['revision'] }}"><button type="submit" class="m3-button m3-button--outlined">{{ __('Cancel consent request') }}</button></form>@endif
@endif
</div>@endsection
