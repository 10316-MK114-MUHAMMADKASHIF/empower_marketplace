<?php

use App\Enums\UserRole;
use App\Models\User;
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
    public string $role = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->withCount('orders')
            ->with('practice')
            ->when($this->search !== '', function ($q) {
                $search = $this->search;
                $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($this->role !== '', fn ($q) => $q->where('role', $this->role))
            ->latest()
            ->paginate(10);
    }
};
?>

<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3 justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search name or email…"
                class="w-full sm:w-64 rounded-xl border border-empower-border bg-white px-4 py-2 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">

            <select wire:model.live="role"
                class="rounded-xl border border-empower-border bg-white px-4 py-2 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                <option value="">All roles</option>
                @foreach(UserRole::cases() as $case)
                    <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                @endforeach
            </select>
        </div>

        <a href="{{ route('admin.users.create') }}" wire:navigate
            class="inline-flex items-center gap-1 rounded bg-navy px-4 py-2 text-xs font-bold text-white hover:bg-navy-dark transition-colors">
            + New User
        </a>
    </div>

    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">
        <div class="w-full overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
            <thead>
                <tr class="bg-page text-left text-xs font-extrabold uppercase tracking-wider text-empower-muted">
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Practice</th>
                    <th class="px-5 py-3">Orders</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-empower-border">
                @forelse($this->users as $user)
                    <tr class="hover:bg-page/60 transition-colors">
                        <td class="px-5 py-3.5 font-semibold text-navy">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="text-xs font-normal text-empower-muted">(you)</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-empower-text">{{ $user->email }}</td>
                        <td class="px-5 py-3.5 text-empower-text capitalize">{{ $user->role->value }}</td>
                        <td class="px-5 py-3.5 text-empower-text">{{ $user->practice?->name ?: '—' }}</td>
                        <td class="px-5 py-3.5 text-empower-text">{{ $user->orders_count }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[0.68rem] font-extrabold uppercase tracking-wider {{ $user->is_active ? 'bg-[#dff7f0] text-[#0f7a4f]' : 'bg-[#fde8e8] text-red-700' }}">
                                {{ $user->is_active ? 'Active' : 'Deactivated' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" wire:navigate class="text-xs font-bold text-[#0b9ed0] hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-sm text-empower-muted italic">No users yet.</td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </div>

    <div>{{ $this->users->links() }}</div>
</div>
