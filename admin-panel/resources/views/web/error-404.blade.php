<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name') }}</title>
    {{-- Material 3 design tokens, generated from the apps' theme (packages/eschool_core). Load first. --}}
    <link rel="stylesheet" href="{{ asset('/assets/css/m3-tokens.css') }}?v={{ filemtime(public_path('assets/css/m3-tokens.css')) }}">
    <!-- plugins:css -->
    {{-- <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../../assets/vendors/ti-icons/css/themify-icons.css"> --}}
    <link rel="stylesheet" href="{{ asset('/assets/css/vendor.bundle.base.css') }}" async>
    <link rel="stylesheet" href="{{ asset('/assets/fonts/font-awesome.min.css') }}" async/>

    <link rel="stylesheet" href="{{ asset('/assets/css/style.css') }}">
    {{-- Typeface and legacy colour variables (--theme-color …) mapped to the tokens — after style.css. --}}
    <link rel="stylesheet" href="{{ asset('/assets/css/m3-base.css') }}?v={{ filemtime(public_path('assets/css/m3-base.css')) }}">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="{{ url(Storage::url(schoolSetting('favicon'))) }}"/>
  </head>
{{-- Colours come from the design tokens, not the school theme-colour setting. The page is a
     large solid block, so it is Dark Navy with white text; the link uses the purple
     that reads on navy. --}}
<style>
    .error-page.bg-primary {
        background-color: var(--md-sys-color-inverse-surface) !important;
        color: var(--md-sys-color-on-inverse-surface);
    }

    /* "Back to home": a Material 3 filled button on navy, in the inverse
       roles (inverse-primary with a dark primary label, 7.8:1), 40px high
       with the app's 10px corners. */
    .error-page a.text-white {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        padding: 0 24px;
        border-radius: var(--app-radius);
        background-color: var(--md-sys-color-inverse-primary);
        color: color-mix(in srgb, var(--md-sys-color-primary) 55%, var(--md-sys-color-on-surface)) !important;
        font-size: var(--md-sys-typescale-label-large-size);
        line-height: var(--md-sys-typescale-label-large-line-height);
        font-weight: var(--md-sys-typescale-label-large-weight);
        letter-spacing: var(--md-sys-typescale-label-large-tracking);
        text-decoration: none;
        transition: background-color 0.2s, box-shadow 0.2s;
    }

    .error-page a.text-white:hover {
        background-color: color-mix(in srgb, var(--md-sys-color-primary) var(--md-sys-state-hover-opacity), var(--md-sys-color-inverse-primary));
        box-shadow: var(--md-sys-elevation-level1);
    }

    .error-page a.text-white:focus-visible {
        outline: 3px solid var(--md-sys-color-inverse-primary);
        outline-offset: 2px;
    }
</style>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center text-center error-page bg-primary">
          <div class="row flex-grow">
            <div class="col-lg-7 mx-auto text-white">
              <div class="row align-items-center d-flex flex-row">
                <div class="col-lg-6 text-lg-right pr-lg-4">
                  <h1 class="display-1 mb-0">404</h1>
                </div>
                <div class="col-lg-6 error-page-divider text-lg-left pl-lg-4">
                  <h2>SORRY!</h2>
                  <h3 class="font-weight-light">The page you’re looking for was not found.</h3>
                </div>
              </div>
              <div class="row mt-5">
                <div class="col-12 text-center mt-xl-2">
                  <a class="text-white font-weight-medium" href="{{ url('/')}}">{{ __('back_to_home') }}</a>
                </div>
              </div>
              <div class="row mt-5">
                <div class="col-12 mt-xl-2">
                  <p class="text-white font-weight-medium text-center">Copyright &copy; <?= date('Y') ?> Tokensoft, Pretoria. All rights reserved.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="{{ asset('/assets/js/vendor.bundle.base.js') }}"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="{{ asset('/assets/js/off-canvas.js') }}"></script>
    <script src="{{ asset('/assets/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('/assets/js/misc.js') }}"></script>
    <script src="{{ asset('/assets/js/settings.js') }}"></script>
    <script src="{{ asset('/assets/js/todolist.js') }}"></script>
    <script src="{{ asset('/assets/js/jquery.cookie.js') }}"></script>

    <!-- endinject -->
  </body>
</html>

