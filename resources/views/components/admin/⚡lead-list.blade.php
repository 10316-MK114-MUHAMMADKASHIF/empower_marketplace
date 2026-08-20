<?php

use App\Models\Lead;
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

    public function markContacted(int $leadId): void
    {
        Lead::where('id', $leadId)->update([
            'is_contacted' => true,
            'contacted_at' => now(),
            'contacted_by' => auth()->id(),
        ]);

        unset($this->leads);
    }

    #[Computed]
    public function leads(): LengthAwarePaginator
    {
        return Lead::query()
            ->when($this->search !== '', function ($q) {
                $search = $this->search;
                $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(20);
    }
};
?>

<div class="space-y-4">
    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search name or email…"
        class="w-full sm:w-64 rounded-xl border border-empower-border bg-white px-4 py-2 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">

    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">
        <div class="w-full overflow-x-auto">
            <table class="w-full min-w-[760px] text-sm">
            <thead>
                <tr class="bg-page text-left text-xs font-extrabold uppercase tracking-wider text-empower-muted">
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Contact</th>
                    <th class="px-5 py-3">Package Interest</th>
                    <th class="px-5 py-3">Received</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-empower-border">
                @forelse($this->leads as $lead)
                    <tr class="hover:bg-page/60 transition-colors">
                        <td class="px-5 py-3.5 font-semibold text-navy">{{ $lead->name }}</td>
                        <td class="px-5 py-3.5 text-empower-text">
                            <div>{{ $lead->email }}</div>
                            <div class="text-xs text-empower-muted">{{ $lead->phone }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-empower-text">{{ $lead->package_interest ?: '—' }}</td>
                        <td class="px-5 py-3.5 text-empower-muted text-xs">{{ $lead->created_at?->diffForHumans() }}</td>
                        <td class="px-5 py-3.5 text-right">
                            @if($lead->is_contacted)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[0.68rem] font-extrabold uppercase tracking-wider bg-[#dff7f0] text-[#0f7a4f]">Contacted</span>
                            @else
                                <button wire:click="markContacted({{ $lead->id }})" class="text-xs font-bold text-[#1a7aad] hover:underline">Mark Contacted</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-empower-muted italic">No leads yet.</td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </div>

    <div>{{ $this->leads->links() }}</div>
</div>
