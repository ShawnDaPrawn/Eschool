{{--
    M3 dialog: a native <dialog> opened with data-m3-dialog-open="id" or M3.dialog.open('id')
    (and, while the admin still has Bootstrap, data-toggle="modal" / $('#id').modal('show')
    through m3-bridge.js).

    The usual edit dialog: give it a form and a submit label.

        <x-m3.dialog id="editModal" :title="__('edit_medium')" :form-action="url('medium')"
            form-id="edit-form" form-class="medium-edit-form" :submit="__('edit')">
            <input type="hidden" name="id" id="id">
            <x-m3.text-field name="name" id="name" :label="__('name')" required />
        </x-m3.dialog>

    Without form-action/form-id the slot is the content, and an `actions` slot replaces the
    default Close button.

    wide          a wider dialog (560px → 720px).
    persistent    clicking the scrim doesn't close it.
    submit        the submit button's label (with a form). cancel: the cancel label.
    Every other attribute goes on the <dialog>.
--}}
@props([
    'id',
    'title',
    'wide' => false,
    'persistent' => false,
    'formAction' => null,
    'formId' => null,
    'formClass' => null,
    'formMethod' => 'POST',
    'submit' => null,
    'cancel' => null,
])
@php
    $hasForm = $formAction !== null || $formId !== null;
@endphp
<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-title" @if ($persistent) data-m3-persistent @endif
    {{ $attributes->class(['m3-dialog', 'm3-dialog--wide' => $wide]) }}>
    @if ($hasForm)
        <form @class(['m3-dialog__form', $formClass]) @if ($formId) id="{{ $formId }}" @endif
            @if ($formAction !== null) action="{{ $formAction }}" @endif method="{{ $formMethod }}" novalidate>
            @csrf
    @else
        <div class="m3-dialog__form">
    @endif
        <div class="m3-dialog__header">
            <h2 class="m3-dialog__headline" id="{{ $id }}-title">{{ $title }}</h2>
            <x-m3.icon-button icon="close" :label="__('close')" data-m3-dialog-close :tooltip="false" />
        </div>
        <div class="m3-dialog__content m3-stack m3-gap-4">
            {{ $slot }}
        </div>
        <div class="m3-dialog__actions">
            @isset($actions)
                {{ $actions }}
            @else
                <x-m3.button variant="text" data-m3-dialog-close>{{ $cancel ?? __('close') }}</x-m3.button>
                @if ($hasForm)
                    <x-m3.button type="submit">{{ $submit ?? __('submit') }}</x-m3.button>
                @endif
            @endisset
        </div>
    @if ($hasForm)
        </form>
    @else
        </div>
    @endif
</dialog>
