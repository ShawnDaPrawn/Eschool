 @php
     $footer_version = getSettings('system_version')['system_version'];
 @endphp
 <footer class="footer">
     <div class="d-sm-flex justify-content-between align-items-center">
         <span class="footer-version">v{{ $footer_version }}</span>
         <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">{{ __('copyright') }} ©
             <?= date('Y') ?> <a class="text-theme" href="{{ env('APP_URL') }}" target="_blank">{{ env('APP_NAME') }}</a>.
             {{ __('all_rights_reserved') }}.</span>
     </div>
 </footer>
