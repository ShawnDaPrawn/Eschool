<!doctype html>
<html lang="en"><body>
@if ($firstAdministrator)
<p>{{ __(':school is ready on NavigateEd, and you are invited to be its first administrator.', ['school' => $schoolName]) }}</p>
@else
<p>{{ __(':school has invited you to join it on NavigateEd as :role.', ['school' => $schoolName, 'role' => $roleName]) }}</p>
@endif
<p>{{ __('You already have a NavigateEd account with this email address, so you do not need a new one. Sign in with your usual password to accept or decline. Accepting does not change your password, and :school cannot change your account details or your place at your other schools.', ['school' => $schoolName]) }}</p>
<p><a href="{{ $invitationUrl }}">{{ __('See the invitation') }}</a></p>
<p>{{ __('This private link expires after seven days. If you did not expect this email, ignore it.') }}</p>
</body></html>
