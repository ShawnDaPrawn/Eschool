<!doctype html>
<html lang="en"><body>
@if ($firstAdministrator)
<p>{{ __(':school is ready on NavigateEd, and you are its first administrator.', ['school' => $schoolName]) }}</p>
@else
<p>{{ __(':school has invited you to NavigateEd as a member of staff.', ['school' => $schoolName]) }}</p>
@endif
<p><a href="{{ $invitationUrl }}">{{ __('Choose your password and sign in') }}</a></p>
<p>{{ __('This private link expires after seven days and can be used once. Nobody else knows your password. If you did not expect this email, ignore it and tell the school.') }}</p>
</body></html>
