<!doctype html>
<html lang="en"><body>
<p>{{ $schoolName }}</p>
<h1>{{ $noticeTitle }}</h1>
<p>{{ $noticeBody }}</p>
<p><a href="{{ $noticeUrl }}">{{ __('Open NavigateEd') }}</a></p>
@if ($unsubscribeUrl)
<p><small><a href="{{ $unsubscribeUrl }}">{{ __('Stop these emails') }}</a> · {{ __('You can change all your notices in the NavigateEd app.') }}</small></p>
@endif
</body></html>
