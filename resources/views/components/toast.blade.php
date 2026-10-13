{{-- Global toast — any Livewire component on any page can trigger this with
$this->dispatch('toast', message: '...', type: 'success' | 'error'), and pure Alpine code can
do the same with $dispatch('toast', { message: '...', type: '...' }) (e.g. a clipboard-copy
confirmation that never hits the server). An action that redirects (e.g. after a
wire:navigate) instead flashes session('toast'/'toast_type'), which this same component fires
on load — a live dispatch can't survive the page swap. --}}
<div x-data="{ show: false, message: '', type: 'success', hideTimer: null }" @if(session('toast')) x-init="
        message = @js(session('toast'));
        type = @js(session('toast_type', 'success'));
        show = true;
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => show = false, 3000)
    " @endif x-on:toast.window="
        message = $event.detail.message;
        type = $event.detail.type ?? 'success';
        show = true;
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => show = false, 3000)
    " x-show="show" x-transition x-cloak class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[100]">
    <div x-on:click="show = false; clearTimeout(hideTimer)"
        class="flex items-center gap-2 rounded-xl pl-4 pr-5 py-3 shadow-[0_18px_50px_rgba(10,32,55,0.25)] text-white cursor-pointer"
        x-bind:class="type === 'error' ? 'bg-red-600' : 'bg-green-600'">
        <span class="font-bold" x-text="type === 'error' ? '&#9888;' : '&#9432;'"
            x-bind:class="type === 'error' ? 'text-red-200' : 'text-green-200'"></span>
        <span class="text-sm font-semibold" x-text="message"></span>
    </div>
</div>
