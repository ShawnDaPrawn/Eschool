{!! $brand['name'] ?? $schoolName !!}

{!! $noticeTitle !!}

{!! $contentText ?? $noticeBody !!}

{!! __('Open NavigateEd') !!}: {!! $noticeUrl !!}

--
{!! __('Sent by :school through NavigateEd.', ['school' => $brand['name'] ?? $schoolName]) !!}
@if ($unsubscribeUrl)
{!! __('Stop these emails') !!}: {!! $unsubscribeUrl !!}
{!! __('You can change all your notices in the NavigateEd app.') !!}
@endif
