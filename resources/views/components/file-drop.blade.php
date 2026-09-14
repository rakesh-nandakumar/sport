@props([
    'name' => null,
    'accept' => null,
    'multiple' => false,
    'required' => false,
    'maxSize' => null,
    'hint' => null,
])

@php
    $acceptLabel = $accept ? strtoupper(str_replace(['.', ','], ['', ' '], $accept)) : null;
    $defaultHint = trim(($acceptLabel ?? 'Any file').($maxSize ? ' · up to '.$maxSize.' MB' : ''));
@endphp

<div
    x-data="{
        dragging: false,
        focused: false,
        error: null,
        names: [],
        preview: null,
        accept: @js($accept),
        maxSize: @js($maxSize),
        multiple: @js((bool) $multiple),
        open() { this.$refs.input.click() },
        matches(file) {
            if (! this.accept) return true;
            const type = (file.type || '').toLowerCase();
            const name = (file.name || '').toLowerCase();
            return this.accept.split(',').some((rule) => {
                rule = rule.trim().toLowerCase();
                if (rule === '') return false;
                if (rule.endsWith('/*')) return type.startsWith(rule.slice(0, -1));
                if (rule.startsWith('.')) return name.endsWith(rule);
                return type === rule;
            });
        },
        size(bytes) {
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
            if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
            return bytes + ' B';
        },
        handle(fileList) {
            const files = Array.from(fileList || []);
            if (! files.length) return;
            this.error = null;
            const accepted = [];
            for (const file of files) {
                if (! this.matches(file)) { this.error = 'Unsupported file type.'; return; }
                if (this.maxSize && file.size > this.maxSize * 1024 * 1024) { this.error = 'File is larger than {{ $maxSize }} MB.'; return; }
                accepted.push(file);
            }
            const list = this.multiple ? accepted : accepted.slice(0, 1);
            const transfer = new DataTransfer();
            list.forEach((file) => transfer.items.add(file));
            this.$refs.input.files = transfer.files;
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.names = list.map((file) => file.name + ' · ' + this.size(file.size));
            this.preview = null;
            const image = list.find((file) => (file.type || '').startsWith('image/'));
            if (image) this.preview = URL.createObjectURL(image);
        },
        onPaste(event) {
            const files = event.clipboardData ? event.clipboardData.files : null;
            if (files && files.length) { event.preventDefault(); this.handle(files); }
        },
    }"
    x-on:dragover.prevent="dragging = true"
    x-on:dragleave.prevent="dragging = false"
    x-on:drop.prevent="dragging = false; handle($event.dataTransfer.files)"
    x-on:click="if (! $event.target.closest('input')) { $el.focus(); open() }"
    x-on:keydown.enter.prevent="open()"
    x-on:keydown.space.prevent="open()"
    x-on:paste="onPaste($event)"
    x-on:focusin="focused = true"
    x-on:focusout="focused = false"
    x-bind:class="{ 'ep-drop--dragging': dragging, 'ep-drop--focused': focused }"
    class="ep-drop"
    tabindex="0"
    role="button"
    aria-label="Upload a file: drag and drop, paste, or browse"
>
    <input
        x-ref="input"
        type="file"
        @if($name) name="{{ $name }}" @endif
        @if($accept) accept="{{ $accept }}" @endif
        @if($multiple) multiple @endif
        @if($required) required @endif
        class="ep-drop__input"
        {{ $attributes }}
    >

    <span class="ep-drop__icon" x-show="! preview" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"/></svg>
    </span>
    <img x-show="preview" x-cloak x-bind:src="preview" class="ep-drop__preview" alt="">

    <span class="ep-drop__text">
        <span class="ep-drop__title">Drag &amp; drop, paste, or <span class="ep-drop__browse">browse</span></span>
        <span class="ep-drop__meta">{{ $hint ?? $defaultHint }}</span>
        <template x-if="names.length">
            <span class="ep-drop__file" x-text="names.join(', ')"></span>
        </template>
        <template x-if="error">
            <span class="ep-drop__error" x-text="error"></span>
        </template>
    </span>
</div>
