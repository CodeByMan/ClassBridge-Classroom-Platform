@php
    $inputId = trim((string) $attributes->get('id'));
@endphp

<div class="gc-password-field">
    <input
        type="password"
        data-password-input
        {{ $attributes->merge(['class' => 'gc-input gc-password-field__input']) }}
    >

    <button
        type="button"
        class="gc-password-toggle"
        data-password-toggle="{{ $inputId }}"
        data-password-visible="false"
        aria-controls="{{ $inputId }}"
        aria-label="Show password"
        aria-pressed="false"
    >
        <span class="gc-password-toggle__halo" aria-hidden="true"></span>
        <svg class="gc-password-toggle__icon" viewBox="0 0 24 24" width="21" height="21" aria-hidden="true" focusable="false">
            <g data-password-show-icon>
                <path class="gc-password-toggle__eye" d="M2.2 12s3.6-6 9.8-6 9.8 6 9.8 6-3.6 6-9.8 6-9.8-6-9.8-6Z" />
                <circle class="gc-password-toggle__pupil" cx="12" cy="12" r="2.8" />
            </g>
            <path data-password-hide-icon class="gc-password-toggle__slash" d="M4 4 20 20" />
        </svg>
        <span class="sr-only" data-password-toggle-label>Show password</span>
    </button>
</div>
