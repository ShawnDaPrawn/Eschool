<section class="m3-card m3-card--outlined"><div class="m3-card__content"><div class="panel-actions"><h2>{{ $view==='attendance' ? __('Choose a session register') : __('Sessions') }}</h2>
@if($view!=='attendance' && !$readOnly)<a class="m3-button m3-button--filled" href="{{ route('activity-groups.index',['group_id'=>$group['id'],'view'=>'session-edit']) }}">{{ __('Create session') }}</a>@endif</div>
@forelse($detail['sessions'] as $row)<article class="activity-record"><h3>{{ $row['name'] }}</h3><p>{{ $row['on_date'] }}@if($row['starts_at'] ?? null) · {{ $row['starts_at'] }} – {{ $row['ends_at'] }}@endif · {{ $row['venue'] }} · {{ __(ucfirst($row['status'])) }}</p>@include('activities.session-cover',['coverSession'=>$row])<div class="panel-actions">
@if($view!=='attendance')<a class="m3-button m3-button--outlined" href="{{ route('activity-groups.index',['group_id'=>$group['id'],'view'=>'session-edit','edit'=>'session','record_id'=>$row['id']]) }}">{{ __('Edit or cancel session') }}</a>@endif
<a class="m3-button m3-button--outlined" href="{{ route('activity-groups.index',['group_id'=>$group['id'],'view'=>'attendance','session_id'=>$row['id']]) }}">{{ __('Open attendance') }}</a></div></article>
@empty<x-page.empty-state :title="__('No sessions yet')" :message="__('Create a dated session before capturing its attendance.')" />@endforelse
</div></section>
