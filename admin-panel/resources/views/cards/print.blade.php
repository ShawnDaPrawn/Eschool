<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><title>{{ __('School identity cards') }}</title>
<style>@page{margin:0}body{margin:0;font-family:DejaVu Sans,sans-serif;font-size:8pt}.identity-card{width:{{ $width-4 }}mm;height:{{ $height-4 }}mm;padding:2mm;position:relative;overflow:hidden}.school{font-size:9pt;font-weight:bold;height:7mm;overflow:hidden}.holder{font-size:10pt;font-weight:bold;max-height:10mm;overflow:hidden}.photo{width:{{ $portrait?25:24 }}mm;height:{{ $portrait?31:30 }}mm;float:{{ $portrait?'none':'left' }};margin-right:2mm}.locator{width:{{ $portrait?23:21 }}mm;height:{{ $portrait?23:21 }}mm}.footer{position:absolute;bottom:2mm;left:2mm;right:2mm;font-size:5pt;line-height:1.2}.kind{font-size:7pt}</style></head><body>
@foreach($cards as $card)
<section class="identity-card" @if(!$loop->last)style="page-break-after:always"@endif>
<div class="school">{{ $card['school'] }}</div><img class="photo" src="{{ $card['photo'] }}" alt=""><div class="holder">{{ $card['name'] }}</div><div class="kind">{{ __(ucfirst($card['type'])) }}</div><img class="locator" src="{{ $card['qr'] }}" alt=""><div class="footer">{{ __('Tap the secure card or use the app code at the gate.') }}<br>{{ substr($card['id'],-8) }}</div>
</section>
@endforeach
</body></html>
