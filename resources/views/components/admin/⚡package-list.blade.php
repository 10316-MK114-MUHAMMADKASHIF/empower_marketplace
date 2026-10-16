<?php

use App\Models\ActivityLog;
use App\Models\Package;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function packages(): Collection
    {
        return Package::orderBy('sort_order')->get();
    }

    public function toggleActive(int $packageId): void
    {
        $package = Package::findOrFail($packageId);
        $package->update(['is_active' => ! $package->is_active]);

        ActivityLog::record(
            $package->is_active ? 'package.activated' : 'package.deactivated',
            "{$package->name} was ".($package->is_active ? 'activated' : 'deactivated').'.',
            user: auth()->user(),
            subject: $package,
        );

        unset($this->packages);

        $this->dispatch('toast', message: "{$package->name} ".($package->is_active ? 'activated' : 'deactivated').'.', type: 'success');
    }

    public function delete(int $packageId): void
    {
        $package = Package::findOrFail($packageId);

        if ($package->orders()->exists()) {
            $this->addError('delete', "{$package->name} has existing orders and can't be deleted. Deactivate it instead.");
            $this->dispatch('toast', message: "{$package->name} has existing orders and can't be deleted.", type: 'error');

            return;
        }

        $name = $package->name;
        $package->delete();

        ActivityLog::record('package.deleted', "{$name} was deleted.", user: auth()->user());

        unset($this->packages);

        $this->dispatch('toast', message: "{$name} deleted.", type: 'success');
    }
};
?>

<div class="space-y-4" x-data="{ confirmId: null, confirmLabel: '' }">
    @error('delete')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="flex justify-between items-center">
        <h1 data-tour="page-title" class="text-2xl font-bold text-navy">Packages</h1>
        <a data-tour="new" href="{{ route('admin.packages.create') }}" wire:navigate
            class="inline-flex items-center gap-1 rounded-lg bg-[#2299dd] px-4 py-2 text-xs font-bold text-white hover:bg-[#087fa9] transition-colors">
            + New Package
        </a>
    </div>

    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">
        <div class="w-full overflow-x-auto">
            <table data-tour="table" class="w-full min-w-[860px] text-sm">
            <thead>
                <tr class="bg-page text-left text-xs font-extrabold uppercase tracking-wider text-empower-muted">
                    <th class="px-5 py-3">Package</th>
                    <th class="px-5 py-3">Billing</th>
                    <th class="px-5 py-3">Annual Price</th>
                    <th class="px-5 py-3">Monthly Price</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-empower-border">
                @forelse($this->packages as $package)
                    <tr class="hover:bg-page/60 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="font-semibold text-navy">{{ $package->name }}</div>
                            <div class="text-xs text-empower-muted">{{ $package->slug }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-empower-text capitalize">{{ $package->billing_type }}</td>
                        <td class="px-5 py-3.5 text-empower-text">
                            {{ $package->annual_price !== null ? '$'.number_format($package->annual_price, 2) : 'Custom quote' }}
                        </td>
                        <td class="px-5 py-3.5 text-empower-text">
                            {{ $package->monthly_price !== null ? '$'.number_format($package->monthly_price, 2) : '—' }}
                        </td>
                        <td class="px-5 py-3.5">
                            <button wire:click="toggleActive({{ $package->id }})" wire:target="toggleActive({{ $package->id }})"
                                wire:loading.attr="disabled" wire:target="toggleActive({{ $package->id }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[0.68rem] font-extrabold uppercase tracking-wider transition-colors {{ $package->is_active ? 'bg-[#dff7f0] text-[#0f7a4f]' : 'bg-[#edf2f7] text-empower-muted' }}">
                                <span wire:loading.remove wire:target="toggleActive({{ $package->id }})">{{ $package->is_active ? 'Active' : 'Inactive' }}</span>
                                <span wire:loading wire:target="toggleActive({{ $package->id }})"><x-spinner class="h-3 w-3" /></span>
                            </button>
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-3">
                            <a href="{{ route('admin.packages.edit', $package) }}" wire:navigate title="Edit" aria-label="Edit" class="inline-flex items-center p-1 rounded hover:bg-page text-[#0b9ed0]"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg></a>
                            <button type="button" x-on:click="confirmId = {{ $package->id }}; confirmLabel = @js($package->name)"
                                title="Delete" aria-label="Delete" class="inline-flex items-center p-1 rounded hover:bg-page text-red-600"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-empower-muted italic">No packages yet.</td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </div>

    <div x-show="confirmId !== null" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
        <div class="w-full max-w-sm bg-white rounded-[1.25rem] shadow-xl p-6" x-on:click.outside="confirmId = null">
            <h3 class="text-base font-semibold text-navy mb-2">Delete <span x-text="confirmLabel"></span>?</h3>
            <p class="text-sm text-empower-muted mb-5">This cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button type="button" x-on:click="confirmId = null"
                    class="rounded-lg border border-empower-border px-4 py-2 text-sm font-semibold text-empower-muted hover:bg-page transition-colors">
                    Cancel
                </button>
                <button type="button" wire:target="delete"
                    x-on:click="$wire.delete(confirmId).then(() => confirmId = null).catch(() => {})"
                    wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed" wire:target="delete"
                    class="inline-flex items-center gap-1 rounded px-5 py-2 text-sm font-bold transition-colors bg-red-600 text-white hover:bg-red-700">
                    <span wire:loading.remove wire:target="delete">Delete</span>
                    <span wire:loading.inline-flex wire:target="delete" class="inline-flex items-center gap-1.5"><x-spinner class="h-3.5 w-3.5" /> Deleting…</span>
                </button>
            </div>
        </div>
    </div>
</div>
