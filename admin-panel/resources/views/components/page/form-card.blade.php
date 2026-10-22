{{--
    A form in a Material 3 outlined card that saves without reloading the page.
    page.js sends it with fetch and the CSRF token, shows each validation error
    under its field (a 422 response's `errors`), moves focus to the first one,
    and keeps what was typed when saving fails. A draft is also kept in
    sessionStorage until it saves, so an expired session or a closed tab does
    not lose the typing.

    <x-page.form-card id="notice-form" :action="url('announcement')" :title="__('send_a_notice')"
        :submit-label="__('send')" reset-on-success refresh-table="table_list">
        <x-page.field name="title" :label="__('title')" required maxlength="255" />
    </x-page.form-card>

    Lay several fields out with the M3 grid: <div class="m3-grid"> with
    m3-col-12 m3-col-expanded-4 children.

    The endpoint answers JSON: {error: false, message, data?, redirect?} on
    success; 422 with {message, errors: {field: [messages]}} or
    {error: true, message} otherwise. After a save the form can go to
    `redirect` (the prop or the response's), reset itself, refresh a table, or
    fire a `page-form:saved` event for page code.
--}}
@props([
    'id',
    'action',
    'method' => 'POST',
    'title' => null,
    'subtitle' => null,
    'submitLabel' => null,
    'redirect' => null,
    'resetOnSuccess' => false,
    'refreshTable' => null,
    'draft' => true,
    'write' => true,
])
<section {{ $attributes->merge(['class' => 'm3 m3-card m3-card--outlined page-form-card']) }} @if ($title) aria-labelledby="{{ $id }}-title" @endif>
    <div class="m3-card__content page-card__content">
        @if ($title)
            <h2 class="page-card-title" id="{{ $id }}-title">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <p class="page-card-subtitle">{{ $subtitle }}</p>
        @endif
        <form id="{{ $id }}" class="page-form" action="{{ $action }}" method="POST" novalidate data-page-form
            @if ($redirect) data-redirect="{{ $redirect }}" @endif
            @if ($resetOnSuccess) data-reset-on-success @endif
            @if ($refreshTable) data-refresh-table="{{ $refreshTable }}" @endif
            @if ($draft) data-draft="{{ $id }}" @endif
            @if (! $write) data-year-safe @endif>
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif
            <div class="m3-banner m3-banner--danger page-form-summary" role="alert" data-form-summary hidden></div>
            {{ $slot }}
            <div class="page-form-actions">
                {{ $actions ?? '' }}
                <button type="submit" class="m3-button m3-button--filled" data-submit>{{ $submitLabel ?? __('save') }}</button>
            </div>
        </form>
    </div>
</section>
