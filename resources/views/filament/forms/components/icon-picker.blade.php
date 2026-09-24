<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            open: false,
            search: '',
            icons: [],
            loaded: false,
            style: @js($getStyle()),
            async ensureLoaded() {
                if (this.loaded) return;
                this.loaded = true;
                try {
                    const response = await fetch('{{ asset('data/fontawesome-solid-icons.json') }}');
                    this.icons = await response.json();
                } catch (error) {
                    this.icons = [];
                }
            },
            get currentName() {
                const parts = (this.state || '').trim().split(/\s+/);
                return (parts[parts.length - 1] || '').replace(/^fa-/, '');
            },
            get filteredIcons() {
                const query = this.search.trim().toLowerCase();
                const list = query ? this.icons.filter((name) => name.includes(query)) : this.icons;
                return list.slice(0, 240);
            },
            choose(name) {
                this.state = 'fa-' + this.style + ' fa-' + name;
                this.open = false;
                this.search = '';
            },
        }"
        x-on:click.outside="open = false"
        {{ $getExtraAttributeBag() }}
        class="fi-icon-picker relative"
    >
        <div class="flex items-center gap-2">
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-gray-50 text-lg text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
            >
                <i :class="state || 'fa-solid fa-medal'"></i>
            </span>

            <input
                type="text"
                x-model="state"
                placeholder="fa-solid fa-medal"
                class="fi-input block w-full rounded-lg border-gray-300 py-1.5 text-sm shadow-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
            />

            <button
                type="button"
                x-on:click="open = ! open; ensureLoaded()"
                class="fi-btn inline-flex h-9 shrink-0 items-center gap-1 rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                Browse
            </button>
        </div>

        <div
            x-show="open"
            x-cloak
            x-transition
            class="fi-icon-picker-panel absolute z-50 mt-2 w-full max-w-md rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-white/10 dark:bg-gray-900"
        >
            <input
                type="text"
                x-model="search"
                x-ref="search"
                placeholder="Search icons — futsal, medal, gamepad…"
                class="fi-input mb-2 block w-full rounded-lg border-gray-300 py-1.5 text-sm shadow-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
            />

            <div x-show="! loaded" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                Loading icons…
            </div>

            <div
                x-show="loaded"
                class="grid grid-cols-6 gap-1 max-h-64 overflow-y-auto sm:grid-cols-8"
            >
                <template x-for="name in filteredIcons" :key="name">
                    <button
                        type="button"
                        x-on:click="choose(name)"
                        :title="name"
                        :class="currentName === name ? 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5'"
                        class="flex h-9 w-9 items-center justify-center rounded-md text-base"
                    >
                        <i :class="'fa-' + style + ' fa-' + name"></i>
                    </button>
                </template>

                <p x-show="loaded && filteredIcons.length === 0" class="col-span-full py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    No icons match “<span x-text="search"></span>”.
                </p>
            </div>
        </div>
    </div>
</x-dynamic-component>
