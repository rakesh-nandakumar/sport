@if (session()->has('message') || $errors->any())
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
         class="fixed top-20 left-1/2 -translate-x-1/2 z-[100001] w-[92%] max-w-lg">
        @if (session('message'))
            <div class="rounded-xl bg-gray-900 text-white px-5 py-3 shadow-2xl flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-emerald-400 mt-1"></i>
                <p class="text-sm">{{ session('message') }}</p>
                <button @click="show = false" class="ml-auto text-gray-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
        @if ($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
            <div class="mt-2 rounded-xl bg-rose-600 text-white px-5 py-3 shadow-2xl flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                <div class="text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
                <button @click="show = false" class="ml-auto text-rose-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>
@endif
