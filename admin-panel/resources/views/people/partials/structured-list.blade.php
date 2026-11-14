{{-- Progressive row editing over an existing source field; its name and payload remain unchanged. --}}
<div data-structured-list data-source="{{ $sourceField }}" data-separator="{{ $separator ?? 'newline' }}" data-maximum="{{ $maximum }}" data-row-maxlength="{{ $rowMaxlength }}" data-title="{{ $listTitle }}" data-row-label="{{ $rowLabel }}" data-add-label="{{ $addLabel }}" data-remove-label="{{ __('Remove row') }}" @if(!empty($pattern)) data-pattern="{{ $pattern }}" @endif></div>
@once
<script src="{{ asset('assets/js/custom/structured-list.js') }}?v={{ filemtime(public_path('assets/js/custom/structured-list.js')) }}" defer></script>
@endonce
