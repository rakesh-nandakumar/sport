<?php


use function Livewire\Volt\{state, mount};

state([
    'notifications' => [],
]);

mount(function (){
    $this->notifications = auth()->user()->notifications;
});

$markAsRead = function ($notificationId) {

    $notification = $this->notifications->find($notificationId)->markAsRead();
    $this->notifications = auth()->user()->notifications;
};

?>

<div class="space-y-3">
    @if(count($notifications) === 0)
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-14 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                <i class="fa-regular fa-bell text-xl"></i>
            </div>
            <p class="font-medium text-gray-600">You don't have any notifications yet.</p>
        </div>
    @else
        @foreach($notifications as $notification)
            <div class="flex items-start gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-gray-100 text-gray-400' : 'bg-brand-50 text-brand-600' }}">
                    <i class="fa-solid fa-bell"></i>
                </span>
                <div class="flex-1">
                    <span class="font-medium text-sm text-gray-800">{{ $notification->data['message'] }}</span>
                    <p class="mt-1 text-xs text-gray-400">{{ $notification->created_at?->diffForHumans() }}</p>
                </div>
                <button wire:click="markAsRead('{{ $notification->id }}')"
                        class="flex-shrink-0 rounded-full px-3 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50">
                    Mark as read
                </button>
            </div>
        @endforeach
    @endif
</div>
