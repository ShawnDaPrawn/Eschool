@foreach($values as $key => $value)
@if(is_array($value))@foreach($value as $part)<input type="hidden" name="{{ $key }}[]" value="{{ $part }}">@endforeach
@elseif($value !== null)<input type="hidden" name="{{ $key }}" value="{{ is_bool($value) ? (int) $value : $value }}">@endif
@endforeach
