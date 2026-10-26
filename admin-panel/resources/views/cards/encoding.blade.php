@extends('layouts.master')
@section('title', __('Personalize secure chip'))
@section('content')
<div class="content-wrapper m3 page-content chip-encoding-workbench">
<x-page.header :title="__('Personalize secure chip')" :subtitle="__('Only a verified personalized chip becomes active for secure taps. Preparing a plan does not activate a chip.')" />
<p><a href="{{ route('cards-panel.index') }}">{{ __('Back to school cards') }}</a></p>
<div data-encoding-private data-lease-url="{{ route('cards-encoding.index', ['card'=>$identity->id,'expected_account_id'=>$account,'expected_school_id'=>$school]) }}">
<section class="page-section" aria-labelledby="encoding-local"><h2 id="encoding-local">{{ __('Prepare the local encoder') }}</h2>
<p>{{ __('Use a school-controlled computer and contactless reader. Private keys and current chip keys are entered only into the local utility.') }}</p>
<p><a href="{{ route('cards-encoding.runtime',['expected_account_id'=>$account,'expected_school_id'=>$school]) }}">{{ __('Download local encoding utility') }}</a></p>
<ol><li>{{ __('Extract the utility, install its pinned requirements and create an encrypted local key. Keep its password private.') }}<pre><code>python3 -m pip install -r requirements.txt
python3 -m nexencoder --initialize-key --key-file ./encoder-private.pem</code></pre></li>
<li>{{ __('Register the displayed public key as an encoder in School card readers. Keep the private reader credential for the local utility.') }} <a href="{{ route('card-readers-panel.index') }}">{{ __('School card readers') }}</a></li>
<li>{{ __('Inspect the chip on your controlled reader, then enter its UID below. The utility requests existing chip keys privately and replaces them with independent random keys.') }}<pre><code>python3 -m nexencoder --inspect-uid --execute-on-reader</code></pre></li></ol>
</section>
<section class="page-section" aria-labelledby="encoding-plan"><h2 id="encoding-plan">{{ __('Chip personalization plan') }}</h2>
<p>{{ __('Card reference') }}: {{ substr($identity->id, -8) }}</p>
@if($plan)
<p role="status">{{ $plan->encoded_at ? __('Secure chip verified') : __('Awaiting verified chip personalization') }}</p>
<p>{{ __('Plan reference') }}: <code>{{ $plan->id }}</code></p>
<p>{{ __('Run the utility from its extracted directory. It requests the private reader credential and key password without putting them in command arguments. Interrupted work resumes the original plan.') }}</p>
<pre><code>python3 -m nexencoder --server {{ escapeshellarg((string) config('app.url')) }} --plan {{ $plan->id }} --key-file ./encoder-private.pem --outbox ./private-receipts --execute-on-reader</code></pre>
<p>{{ __('Reload this page after the utility confirms the fresh SUN proof. A printed locator, checkbox or ordinary account token cannot confirm a chip.') }}</p>
@else
@unless($canWrite)<p role="status">{{ __('Card changes are read-only. Chip preparation is unavailable.') }}</p>@endunless
<form method="POST" action="{{ route('cards-encoding.prepare',['card'=>$identity->id]) }}">@include('cards.hidden')
<x-page.field id="encoding-device" name="device_id" type="select" :label="__('Registered encoder')" :options="$devices->pluck('name','id')->all()" required :restore-old-input="false" />
<x-page.field id="encoding-uid" name="uid" :label="__('Inspected chip UID')" maxlength="14" pattern="04[a-fA-F0-9]{12}" required :restore-old-input="false" />
<p>{{ __('Check the UID and reader before preparing. One immutable plan is retained for recovery; another chip requires another school card.') }}</p>
<button class="m3-button m3-button--filled" type="submit" @disabled(!$canWrite || $devices->isEmpty())>{{ __('Prepare encrypted chip plan') }}</button>
</form>
@endif
</section>
</div>
<p data-encoding-retired role="status" hidden>{{ __('This private view has expired. Reload to check current access.') }}</p>
<button type="button" class="m3-button m3-button--outlined" data-encoding-reload>{{ __('Reload current view') }}</button>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/card-encoding-workbench.js') }}" defer></script>@endsection

@section('css')<link rel="stylesheet" href="{{ asset('assets/css/card-encoding.css') }}">@endsection
