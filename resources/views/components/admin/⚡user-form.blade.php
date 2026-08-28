<?php

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\OshaLocation;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'client';

    public bool $isActive = true;

    public string $password = '';

    public ?int $practiceId = null;

    public string $practiceName = '';

    public string $practiceAddress = '';

    public string $practiceNpiNumber = '';

    public string $practiceSpecialty = '';

    public ?int $practiceBillableProvidersCount = null;

    public bool $practiceIsLocked = false;

    /** @var array<int, array<string, mixed>> */
    public array $oshaLocations = [];

    public ?int $editingLocationIndex = null;

    public function mount(?User $user = null): void
    {
        if (! $user) {
            return;
        }

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->isActive = $user->is_active;

        $practice = $user->practice;

        if (! $practice) {
            return;
        }

        $this->practiceId = $practice->id;
        $this->practiceName = $practice->name;
        $this->practiceAddress = $practice->address ?? '';
        $this->practiceNpiNumber = $practice->npi_number ?? '';
        $this->practiceSpecialty = $practice->specialty ?? '';
        $this->practiceBillableProvidersCount = $practice->billable_providers_count;
        $this->practiceIsLocked = $practice->is_profile_locked;

        $this->oshaLocations = $practice->oshaLocations->map(fn (OshaLocation $location) => $this->locationToArray($location))->all();
    }

    /** @return array<string, mixed> */
    private function locationToArray(?OshaLocation $location = null): array
    {
        return [
            'id' => $location?->id,
            'name' => $location?->name ?? '',
            'address' => $location?->address ?? '',
            'osha_officer' => $location?->osha_officer ?? '',
            'safety_coordinator' => $location?->safety_coordinator ?? '',
            'uses_hazardous_drugs' => $location?->uses_hazardous_drugs ?? false,
            'has_operating_rooms' => $location?->has_operating_rooms ?? false,
            'cleaning_provider' => $location?->cleaning_provider ?? '',
            'cleaning_frequency' => $location?->cleaning_frequency ?? '',
            'offers_hep_b_vaccination' => $location?->offers_hep_b_vaccination ?? false,
            'offers_tb_screening' => $location?->offers_tb_screening ?? false,
            'employees_per_year' => $location?->employees_per_year,
            'waste_hauler' => $location?->waste_hauler ?? '',
        ];
    }

    /** An admin editing their own account can't change their own role, active state, or delete themselves. */
    public function isEditingSelf(): bool
    {
        return $this->userId !== null && $this->userId === auth()->id();
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:255|unique:users,email'.($this->userId ? ",{$this->userId}" : ''),
            'role' => 'required|in:'.implode(',', array_map(fn ($case) => $case->value, UserRole::cases())),
            'password' => $this->userId ? 'nullable|string|min:8' : 'required|string|min:8',
        ];

        $this->validate($rules);

        if ($this->isEditingSelf() && $this->role !== UserRole::Admin->value) {
            $this->addError('role', "You can't change your own role.");

            return;
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if (! $this->isEditingSelf()) {
            $data['is_active'] = $this->isActive;
        }

        if ($this->password !== '') {
            $data['password'] = $this->password;
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update($data);

            ActivityLog::record('user.updated', "{$user->name} ({$user->email}) was updated.", user: auth()->user(), subject: $user);
        } else {
            $user = User::create($data);

            ActivityLog::record('user.created', "{$user->name} ({$user->email}) was created.", user: auth()->user(), subject: $user);
        }

        if ($this->practiceId) {
            $this->validate([
                'practiceName' => 'required|string|max:150',
                'practiceAddress' => 'nullable|string|max:255',
                'practiceNpiNumber' => 'nullable|string|max:20',
                'practiceSpecialty' => 'nullable|string|max:100',
                'practiceBillableProvidersCount' => 'nullable|integer|min:0',
            ]);

            $practice = Practice::findOrFail($this->practiceId);
            $practice->update([
                'name' => $this->practiceName,
                'address' => $this->practiceAddress ?: null,
                'npi_number' => $this->practiceNpiNumber ?: null,
                'specialty' => $this->practiceSpecialty ?: null,
                'billable_providers_count' => $this->practiceBillableProvidersCount,
                'is_profile_locked' => $this->practiceIsLocked,
                'locked_at' => $this->practiceIsLocked ? ($practice->locked_at ?? now()) : null,
            ]);

            ActivityLog::record('practice.updated', "{$practice->name}'s practice profile was updated by an admin.", user: auth()->user(), subject: $practice);
        }

        $this->redirect(route('admin.users'), navigate: true);
    }

    public function addOshaLocation(): void
    {
        $this->oshaLocations[] = $this->locationToArray();
        $this->editingLocationIndex = array_key_last($this->oshaLocations);
    }

    public function saveOshaLocation(int $index): void
    {
        $this->validate([
            "oshaLocations.{$index}.name" => 'required|string|max:150',
            "oshaLocations.{$index}.address" => 'nullable|string|max:255',
            "oshaLocations.{$index}.osha_officer" => 'nullable|string|max:150',
            "oshaLocations.{$index}.safety_coordinator" => 'nullable|string|max:150',
            "oshaLocations.{$index}.cleaning_provider" => 'nullable|string|max:150',
            "oshaLocations.{$index}.cleaning_frequency" => 'nullable|string|max:100',
            "oshaLocations.{$index}.employees_per_year" => 'nullable|integer|min:0',
            "oshaLocations.{$index}.waste_hauler" => 'nullable|string|max:150',
        ]);

        $entry = $this->oshaLocations[$index];

        $data = [
            'practice_id' => $this->practiceId,
            'name' => $entry['name'],
            'address' => $entry['address'] ?: null,
            'osha_officer' => $entry['osha_officer'] ?: null,
            'safety_coordinator' => $entry['safety_coordinator'] ?: null,
            'uses_hazardous_drugs' => (bool) $entry['uses_hazardous_drugs'],
            'has_operating_rooms' => (bool) $entry['has_operating_rooms'],
            'cleaning_provider' => $entry['cleaning_provider'] ?: null,
            'cleaning_frequency' => $entry['cleaning_frequency'] ?: null,
            'offers_hep_b_vaccination' => (bool) $entry['offers_hep_b_vaccination'],
            'offers_tb_screening' => (bool) $entry['offers_tb_screening'],
            'employees_per_year' => $entry['employees_per_year'] !== '' ? $entry['employees_per_year'] : null,
            'waste_hauler' => $entry['waste_hauler'] ?: null,
        ];

        if ($entry['id']) {
            $location = OshaLocation::findOrFail($entry['id']);
            $location->update($data);

            ActivityLog::record('osha_location.updated', "{$location->name} was updated by an admin.", user: auth()->user(), subject: $location);
        } else {
            $data['sort_order'] = count($this->oshaLocations) - 1;
            $location = OshaLocation::create($data);
            $this->oshaLocations[$index]['id'] = $location->id;

            ActivityLog::record('osha_location.created', "{$location->name} was added by an admin.", user: auth()->user(), subject: $location);
        }

        $this->editingLocationIndex = null;
    }

    public function deleteOshaLocation(int $index): void
    {
        $entry = $this->oshaLocations[$index];

        if ($entry['id']) {
            $location = OshaLocation::findOrFail($entry['id']);
            $location->delete();

            ActivityLog::record('osha_location.deleted', "{$location->name} was deleted by an admin.", user: auth()->user());
        }

        unset($this->oshaLocations[$index]);
        $this->oshaLocations = array_values($this->oshaLocations);
        $this->editingLocationIndex = null;
    }

    public function delete(): void
    {
        if (! $this->userId) {
            return;
        }

        if ($this->isEditingSelf()) {
            $this->addError('delete', "You can't delete your own account.");

            return;
        }

        $user = User::findOrFail($this->userId);
        $name = "{$user->name} ({$user->email})";

        if ($user->practice?->logo_path) {
            Storage::disk('local')->delete($user->practice->logo_path);
        }

        foreach ($user->orders as $order) {
            $order->deleteCascadingFiles();
        }

        $user->delete();

        ActivityLog::record('user.deleted', "{$name} was deleted.", user: auth()->user());

        $this->redirect(route('admin.users'), navigate: true);
    }
};
?>

<div class="space-y-4" x-data="{ confirmDeleteUser: false }">
    <a href="{{ route('admin.users') }}" wire:navigate class="text-sm font-semibold text-[#0b9ed0] hover:underline">&larr; Back to users</a>

    @error('delete')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
        <h2 class="text-lg font-semibold text-navy mb-4">{{ $userId ? 'Edit User' : 'New User' }}</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-[#173a59] mb-1.5">Name</label>
                <input wire:model="name" type="text"
                    class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-[#173a59] mb-1.5">Email</label>
                <input wire:model="email" type="email"
                    class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-[#173a59] mb-1.5">Role</label>
                <select wire:model="role" @disabled($this->isEditingSelf())
                    class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition disabled:cursor-not-allowed disabled:bg-[#eef6fb]">
                    @foreach(UserRole::cases() as $case)
                        <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                    @endforeach
                </select>
                @if($this->isEditingSelf())
                    <p class="mt-1 text-xs text-empower-muted">You can't change your own role.</p>
                @endif
                @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-[#173a59] mb-1.5">
                    {{ $userId ? 'New Password' : 'Password' }}
                </label>
                <input wire:model="password" type="password" placeholder="{{ $userId ? 'Leave blank to keep current password' : '' }}"
                    class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if($userId)
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2 {{ $this->isEditingSelf() ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <input wire:model="isActive" type="checkbox" @disabled($this->isEditingSelf())
                            class="rounded border-empower-border text-navy focus:ring-accent">
                        <span class="text-sm font-semibold text-[#173a59]">Active (can log in)</span>
                    </label>
                    @if($this->isEditingSelf())
                        <p class="mt-1 text-xs text-empower-muted">You can't deactivate your own account.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-5 flex items-center justify-between">
            @if($userId && ! $this->isEditingSelf())
                <button type="button" x-on:click="confirmDeleteUser = true"
                    class="text-sm font-bold text-red-600 hover:underline">Delete User</button>
            @else
                <span></span>
            @endif

            <button wire:click="save" wire:target="save"
                class="inline-flex items-center gap-1 rounded bg-accent px-5 py-2 text-sm font-bold text-navy-dark hover:bg-accent-dark transition-colors"
                wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $userId ? 'Save Changes' : 'Create User' }} &rarr;</span>
                <span wire:loading.inline-flex wire:target="save" class="inline-flex items-center gap-1.5"><x-spinner class="h-3.5 w-3.5" /> Saving…</span>
            </button>
        </div>
    </div>

    @if($practiceId)
        <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
            <h2 class="text-lg font-semibold text-navy mb-4">Practice Profile</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-[#173a59] mb-1.5">Practice Name</label>
                    <input wire:model="practiceName" type="text"
                        class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                    @error('practiceName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-[#173a59] mb-1.5">Address</label>
                    <input wire:model="practiceAddress" type="text"
                        class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                    @error('practiceAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#173a59] mb-1.5">NPI Number</label>
                    <input wire:model="practiceNpiNumber" type="text"
                        class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                    @error('practiceNpiNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#173a59] mb-1.5">Specialty</label>
                    <select wire:model="practiceSpecialty"
                        class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                        @if($practiceSpecialty !== '' && ! in_array($practiceSpecialty, Practice::SPECIALTIES, true))
                            <option value="{{ $practiceSpecialty }}">{{ $practiceSpecialty }}</option>
                        @endif
                        @foreach(Practice::SPECIALTIES as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('practiceSpecialty') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#173a59] mb-1.5">Billable Providers</label>
                    <input wire:model="practiceBillableProvidersCount" type="number" min="0"
                        class="w-full rounded-xl border border-empower-border bg-page px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                    @error('practiceBillableProvidersCount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input wire:model="practiceIsLocked" type="checkbox" class="rounded border-empower-border text-navy focus:ring-accent">
                        <span class="text-sm font-semibold text-[#173a59]">Profile locked (client can no longer edit their intake)</span>
                    </label>
                </div>
            </div>
        </div>

    @endif

    <div x-show="confirmDeleteUser" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm bg-white rounded-[1.25rem] shadow-xl p-6" x-on:click.outside="confirmDeleteUser = false">
            <h3 class="text-base font-semibold text-navy mb-2">Delete {{ $name }} ({{ $email }})?</h3>
            <p class="text-sm text-empower-muted mb-5">This permanently deletes their practice, orders, submissions, uploads, and generated documents. This cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button type="button" x-on:click="confirmDeleteUser = false"
                    class="rounded-lg border border-empower-border px-4 py-2 text-sm font-semibold text-empower-muted hover:bg-page transition-colors">
                    Cancel
                </button>
                <button type="button" wire:target="delete"
                    x-on:click="$wire.delete().then(() => confirmDeleteUser = false).catch(() => {})"
                    wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed" wire:target="delete"
                    class="inline-flex items-center gap-1 rounded px-5 py-2 text-sm font-bold transition-colors bg-red-600 text-white hover:bg-red-700">
                    <span wire:loading.remove wire:target="delete">Delete</span>
                    <span wire:loading.inline-flex wire:target="delete" class="inline-flex items-center gap-1.5"><x-spinner class="h-3.5 w-3.5" /> Deleting…</span>
                </button>
            </div>
        </div>
    </div>
</div>
