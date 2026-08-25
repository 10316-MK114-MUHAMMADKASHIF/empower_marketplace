<?php

use App\Enums\OrderStatus;
use App\Models\Order;
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

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'package'])
            ->when($this->search !== '', function ($q) {
                $search = $this->search;
                $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(10);
    }
};
?>

<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search client name or email…"
            class="w-full sm:w-64 rounded-xl border border-empower-border bg-white px-4 py-2 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">

        <select wire:model.live="status"
            class="rounded-xl border border-empower-border bg-white px-4 py-2 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
            <option value="">All statuses</option>
            @foreach(OrderStatus::cases() as $case)
                <option value="{{ $case->value }}">{{ ucwords(str_replace('_', ' ', $case->value)) }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">
        <div class="w-full overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
            <thead>
                <tr class="bg-page text-left text-xs font-extrabold uppercase tracking-wider text-empower-muted">
                    <th class="px-5 py-3">Client</th>
                    <th class="px-5 py-3">Package</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Amount Paid</th>
                    <th class="px-5 py-3">Placed</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-empower-border">
                @forelse($this->orders as $order)
                    <tr class="hover:bg-page/60 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="font-semibold text-navy">{{ $order->user->name }}</div>
                            <div class="text-xs text-empower-muted">{{ $order->user->email }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-empower-text">{{ $order->package->name }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[0.68rem] font-extrabold uppercase tracking-wider bg-[#edf2f7] text-empower-muted">
                                {{ ucwords(str_replace('_', ' ', $order->status->value)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-empower-text">
                            {{ $order->amount_paid !== null ? '$'.number_format((float) $order->amount_paid, 2) : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-empower-muted text-xs">{{ $order->created_at?->diffForHumans() }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('admin.orders.edit', $order) }}" wire:navigate class="text-xs font-bold text-[#1a7aad] hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-empower-muted italic">No orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </div>

    <div>{{ $this->orders->links() }}</div>
</div>
