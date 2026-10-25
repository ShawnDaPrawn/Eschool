<svg viewBox="0 0 48 48" width="64" height="64" aria-hidden="true" focusable="false"><circle cx="24" cy="24" r="23" fill="{{ $house['secondary_colour'] }}" stroke="{{ $house['primary_colour'] }}" stroke-width="2"/>
@switch($house['emblem'])
@case('star')<path d="m24 7 5 11 12 2-9 9 2 12-10-6-10 6 2-12-9-9 12-2z" fill="{{ $house['primary_colour'] }}"/>@break
@case('mountain')<path d="m7 37 12-24 6 12 5-9 11 21z" fill="{{ $house['primary_colour'] }}"/>@break
@case('river')<path d="M27 8c-22 8 16 12-7 20s9 10 5 13" fill="none" stroke="{{ $house['primary_colour'] }}" stroke-width="7"/>@break
@case('forest')<path d="m13 8-8 19h5l-6 9h18l-6-9h5zm20 0-8 19h5l-6 9h18l-6-9h5z" fill="{{ $house['primary_colour'] }}"/>@break
@case('sun')<circle cx="24" cy="24" r="9" fill="{{ $house['primary_colour'] }}"/><path d="M24 5v7m0 24v7M5 24h7m24 0h7M10 10l5 5m18 18 5 5M38 10l-5 5m-18 18-5 5" stroke="{{ $house['primary_colour'] }}" stroke-width="3"/>@break
@default<path d="M24 7 10 12v12c0 8 7 13 14 17 7-4 14-9 14-17V12z" fill="{{ $house['primary_colour'] }}"/>
@endswitch</svg>
