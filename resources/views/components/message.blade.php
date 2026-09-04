@if (session()->has('message'))
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 4000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-4"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-4"
        x-cloak
        class="toast-message"
        role="status"
    >
        <span class="toast-message__icon"><i class="fa-solid fa-circle-check"></i></span>
        <p class="toast-message__text">{{ session('message') }}</p>
        <button type="button" class="toast-message__close" @click="show = false" aria-label="Dismiss">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
@endif
