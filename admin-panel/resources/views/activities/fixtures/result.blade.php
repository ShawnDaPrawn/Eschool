@if ($result['state'] === 'confirmed'){{ $result['host_score'] }}–{{ $result['guest_score'] }}
@elseif ($result['state'] === 'disputed'){{ __('The two schools reported different scores') }}
@elseif ($result['state'] === 'awaiting'){{ __('Waiting for both scores') }}
@elseif ($state === 'invited'){{ __('Waiting for your answer') }}
@elseif ($state === 'declined'){{ __('Declined') }}
@else{{ __('Not played yet') }}@endif
