@switch($kind)
@case('debate'){{ __('Debate') }}@break
@case('quiz'){{ __('Quiz or olympiad') }}@break
@case('speech'){{ __('Speech contest') }}@break
@default{{ $kind }}
@endswitch
