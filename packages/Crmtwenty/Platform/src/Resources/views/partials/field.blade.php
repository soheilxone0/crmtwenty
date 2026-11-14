{{--
    Text field. Variables: $name, $label, $type = 'text', $icon = null, $value = null,
    $required = false, $hint = null, $ltr = false, $autocomplete = null, $placeholder = null.
--}}
@php
    $type ??= 'text';
    $id = 'f-'.str_replace(['[', ']', '.'], '-', $name);
    $isPassword = $type === 'password';
    $current = $isPassword ? null : old($name, $value ?? null);
    $style = trim((($icon ?? null) ? '' : 'padding-inline-start: 14px;').($isPassword ? ' padding-inline-end: 44px;' : ''));
@endphp

<div class="field">
    <label for="{{ $id }}">{{ $label }}@if ($required ?? false)<span class="req">*</span>@endif</label>

    <div class="input-wrap">
        @if ($icon ?? null)
            <i class="pi pi-{{ $icon }}"></i>
        @endif

        <input
            id="{{ $id }}"
            class="input @error($name) is-invalid @enderror"
            type="{{ $type }}"
            name="{{ $name }}"
            @if (! is_null($current)) value="{{ $current }}" @endif
            @if ($required ?? false) required @endif
            @if (($ltr ?? false) || in_array($type, ['email', 'password'])) dir="ltr" @endif
            @if ($autocomplete ?? null) autocomplete="{{ $autocomplete }}" @endif
            @if ($placeholder ?? null) placeholder="{{ $placeholder }}" @endif
            @if ($style) style="{{ $style }}" @endif
        >

        @if ($isPassword)
            <button type="button" class="icon-btn" data-toggle-password="{{ $id }}" style="position:absolute; inset-inline-end:4px; top:4px; color:#94a3b8" aria-label="show">
                <i class="pi pi-eye" style="position:static; transform:none"></i>
            </button>
        @endif
    </div>

    @if ($hint ?? null)
        <span class="hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="error">{{ $message }}</span>
    @enderror
</div>
