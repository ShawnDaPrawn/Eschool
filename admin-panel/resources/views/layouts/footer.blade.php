 @php
     $footer_version = getSettings('system_version')['system_version'];
 @endphp
 <footer class="footer">
     <div class="d-sm-flex justify-content-between align-items-center">
         <span class="footer-version">v{{ $footer_version }}</span>
         <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">{{ __('copyright') }} ©
             <?= date('Y') ?> <a class="text-theme" href="{{ config('app.url') }}" target="_blank">{{ config('app.name') }}</a>.
             {{ __('all_rights_reserved') }}. Tokensoft, Pretoria.</span>
     </div>
 </footer>
