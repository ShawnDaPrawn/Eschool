@foreach($sessionCovers[$coverSession['id']] ?? [] as $cover)
<p class="panel-record-context">{{ __('Temporary session cover') }}: {{ $cover['name'] }}.
@if($cover['needs_review'])<strong>{{ __('The session changed; review this cover assignment.') }}</strong>@endif
@if($canManageCover)<a href="{{ route('facilitator-leave.show',['absence'=>$cover['absence_id']]) }}">{{ __('Review facilitator leave and cover') }}</a>@endif
</p>
@endforeach
