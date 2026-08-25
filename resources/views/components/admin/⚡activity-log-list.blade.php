<?php

use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        return ActivityLog::query()
            ->with('user', 'order')
            ->when($this->search !== '', function ($q) {
                $search = $this->search;
                $q->where(fn ($q) => $q->where('event_type', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(10);
    }
};
?>

<div class="space-y-4">
    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search event type or description…"
        class="w-full sm:w-80 rounded-xl border border-empower-border bg-white px-4 py-2 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">

    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">
        <div class="w-full overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
            <thead>
                <tr class="bg-page text-left text-xs font-extrabold uppercase tracking-wider text-empower-muted">
                    <th class="px-5 py-3">When</th>
                    <th class="px-5 py-3">Actor</th>
                    <th class="px-5 py-3">Event</th>
                    <th class="px-5 py-3">Description</th>
                    <th class="px-5 py-3">Order</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-empower-border">
                @forelse($this->logs as $log)
                    <tr class="hover:bg-page/60 transition-colors">
                        <td class="px-5 py-3.5 text-empower-muted text-xs whitespace-nowrap">{{ $log->created_at?->format('M j, Y g:ia') }}</td>
                        <td class="px-5 py-3.5 text-empower-text">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[0.65rem] font-extrabold uppercase tracking-wider bg-[#edf2f7] text-empower-muted">
                                {{ $log->event_type }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-empower-text">{{ $log->description }}</td>
                        <td class="px-5 py-3.5 text-empower-text">
                            @if($log->order)
                                <a href="{{ route('admin.orders.edit', $log->order) }}" wire:navigate class="text-xs font-bold text-[#1a7aad] hover:underline">#{{ $log->order_id }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-empower-muted italic">No activity recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </div>

    <div>{{ $this->logs->links() }}</div>
</div>
