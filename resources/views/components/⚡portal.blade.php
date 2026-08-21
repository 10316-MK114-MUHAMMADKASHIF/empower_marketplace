<?php

use App\Enums\AiExtractionStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\IntakeSubmissionStatus;
use App\Enums\IntakeUploadType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Jobs\GenerateComplianceDocument;
use App\Jobs\ProcessIntakeUpload;
use App\Models\ActivityLog;
use App\Models\GeneratedDocument;
use App\Models\IntakeSubmission;
use App\Models\IntakeUpload;
use App\Models\Order;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use App\Support\Questionnaires;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $step = 1;

    // The batch of orders created by the most recent checkout — drives Steps 1/3/4.
    public array $orderIds = [];

    // Which order's documents are shown in the Step 5 Documents tab.
    public ?int $dashboardOrderId = null;

    // Step 1
    public ?int $selectedPackageId = null;

    public string $accountName = '';

    public string $accountEmail = '';

    public string $accountPassword = '';

    public string $cardName = '';

    public string $cardNumber = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    // Step 2
    public $logoFile = null;

    public string $practiceName = '';

    public string $practiceAddress = '';

    public string $npiNumber = '';

    public string $specialty = 'General Practice';

    public int $billableProviders = 1;

    public bool $editingProfile = false;

    // Step 5
    public string $dashboardTab = 'documents';

    // Step 3 — one slot per questionnaire shown in Step 2, keyed by IntakeUploadType::value.
    public array $questionnaireFiles = [];

    #[Computed]
    public function packages(): Collection
    {
        return Package::where('is_active', true)->orderBy('sort_order')->get();
    }

    private function defaultPackageId(): ?int
    {
        return Package::where('is_active', true)
            ->where('slug', '!=', 'complete')
            ->orderBy('sort_order')
            ->value('id');
    }

    #[Computed]
    public function selectedPackage(): ?Package
    {
        return $this->selectedPackageId ? Package::find($this->selectedPackageId) : null;
    }

    /** Every order created by the checkout batch currently being walked through Steps 1/3/4. */
    #[Computed]
    public function batchOrders(): Collection
    {
        if (empty($this->orderIds)) {
            return collect();
        }

        return Order::with(['package', 'intakeSubmission.intakeUploads'])
            ->whereIn('id', $this->orderIds)
            ->get();
    }

    /** The questionnaires the client needs to fill out, based on every package tier they've purchased (or are checking out). */
    #[Computed]
    public function applicableQuestionnaires(): Collection
    {
        $orders = $this->batchOrders->isNotEmpty() ? $this->batchOrders : $this->userOrders;

        $tierValues = $orders
            ->map(fn (Order $order) => $order->package?->tier()?->value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Questionnaires::forTiers($tierValues);
    }

    #[Computed]
    public function rejectedSubmission(): ?IntakeSubmission
    {
        return $this->batchOrders
            ->map(fn ($o) => $o->intakeSubmission)
            ->first(fn ($s) => $s?->status === IntakeSubmissionStatus::Rejected);
    }

    /** The order whose documents are currently displayed on the Step 5 dashboard. */
    #[Computed]
    public function currentOrder(): ?Order
    {
        if (! $this->dashboardOrderId) {
            return null;
        }

        return Order::with(['package', 'intakeSubmission.intakeUploads'])->find($this->dashboardOrderId);
    }

    #[Computed]
    public function practice(): ?Practice
    {
        return auth()->user()?->practice;
    }

    #[Computed]
    public function oshaLocations(): Collection
    {
        return $this->practice?->oshaLocations ?? collect();
    }

    #[Computed]
    public function intakeSubmission(): ?IntakeSubmission
    {
        return $this->currentOrder?->intakeSubmission;
    }

    #[Computed]
    public function generatedDocuments(): Collection
    {
        if (! $this->dashboardOrderId) {
            return collect();
        }

        return GeneratedDocument::where('order_id', $this->dashboardOrderId)
            ->orderBy('document_type')
            ->get();
    }

    /** Every document the active package entitles this practice to, paired with its generated row (if any). */
    #[Computed]
    public function expectedDocuments(): Collection
    {
        $order = $this->currentOrder;
        if (! $order) {
            return collect();
        }

        $docs = $this->generatedDocuments;
        $locations = $this->oshaLocations;
        $rows = collect();

        foreach (DocumentType::forTier($order->package->tier()) as $docType) {
            if ($docType->isPerLocation()) {
                if ($locations->isEmpty()) {
                    $rows->push(['type' => $docType, 'location' => null, 'document' => $docs->first(fn ($d) => $d->document_type === $docType && ! $d->osha_location_id)]);
                }
                foreach ($locations as $location) {
                    $rows->push(['type' => $docType, 'location' => $location, 'document' => $docs->first(fn ($d) => $d->document_type === $docType && $d->osha_location_id === $location->id)]);
                }
            } else {
                $rows->push(['type' => $docType, 'location' => null, 'document' => $docs->first(fn ($d) => $d->document_type === $docType)]);
            }
        }

        return $rows;
    }

    #[Computed]
    public function activityLog(): Collection
    {
        return ActivityLog::where('user_id', auth()->id())->latest()->limit(50)->get();
    }

    #[Computed]
    public function userOrders(): Collection
    {
        return auth()->user()->orders()->with('package')->get();
    }

    #[Computed]
    public function practiceEffectiveDate(): ?Carbon
    {
        return $this->userOrders->pluck('paid_at')->filter()->min();
    }

    #[Computed]
    public function completedMilestone(): int
    {
        $orders = $this->batchOrders;

        if ($orders->isEmpty() || $orders->contains(fn ($o) => $o->payment_status !== PaymentStatus::SimulatedPaid)) {
            return 0;
        }
        if (! $this->practice?->is_profile_locked) {
            return 1;
        }

        $submissions = $orders->map(fn ($o) => $o->intakeSubmission);

        if ($submissions->contains(null)) {
            return 2;
        }
        if ($submissions->contains(fn ($s) => $s->status !== IntakeSubmissionStatus::Approved)) {
            return 3;
        }

        return 4;
    }

    public function canReach(int $step): bool
    {
        return match ($step) {
            1 => true,
            2 => $this->completedMilestone >= 1,
            3 => $this->completedMilestone >= 2,
            4 => $this->completedMilestone >= 3,
            5 => $this->completedMilestone >= 4,
            default => false,
        };
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            $this->redirect(route('admin.dashboard'), navigate: true);

            return;
        }

        if (! $user) {
            $this->step = 1;
            $slug = request()->query('package') ?? session()->pull('intended_package');
            $resolvedId = $slug ? Package::where('slug', $slug)->where('is_active', true)->value('id') : null;

            if ($resolvedId) {
                $this->selectedPackageId = $resolvedId;
            } else {
                $this->selectedPackageId = $this->defaultPackageId();
            }

            return;
        }

        $practice = $user->practice ?? Practice::create([
            'user_id' => $user->id,
            'name' => '',
        ]);

        $this->practiceName = $practice->name ?? '';
        $this->practiceAddress = $practice->address ?? '';
        $this->npiNumber = $practice->npi_number ?? '';
        $this->specialty = $practice->specialty ?? 'General Practice';
        $this->billableProviders = $practice->billable_providers_count ?? 1;

        $requestedSlug = request()->query('package');

        if ($requestedSlug) {
            $requestedPackage = Package::where('slug', $requestedSlug)->where('is_active', true)->first();

            if ($requestedPackage) {
                $existingOrder = $user->orders()->where('package_id', $requestedPackage->id)->latest()->first();

                if (! $existingOrder) {
                    $this->step = 1;
                    $this->selectedPackageId = $requestedPackage->id;

                    return;
                }
            }
        }

        $latestOrder = $user->orders()->with(['package', 'intakeSubmission'])->latest()->first();

        if (! $latestOrder || $latestOrder->payment_status !== PaymentStatus::SimulatedPaid) {
            $this->step = 1;

            if ($latestOrder) {
                $this->orderIds = [$latestOrder->id];
                $this->selectedPackageId = $latestOrder->package_id;
            } else {
                $slug = request()->query('package') ?? session()->pull('intended_package');
                $resolvedId = $slug ? Package::where('slug', $slug)->where('is_active', true)->value('id') : null;

                if ($resolvedId) {
                    $this->selectedPackageId = $resolvedId;
                } else {
                    $this->selectedPackageId = $this->defaultPackageId();
                }
            }

            return;
        }

        $this->orderIds = $latestOrder->checkout_batch_id
            ? $user->orders()->where('checkout_batch_id', $latestOrder->checkout_batch_id)->pluck('id')->all()
            : [$latestOrder->id];

        $this->dashboardOrderId = $user->orders()->where('payment_status', PaymentStatus::SimulatedPaid)->latest()->value('id');
        $this->selectedPackageId = $latestOrder->package_id;

        if (! $practice?->is_profile_locked) {
            $this->step = 1;

            return;
        }

        $submissions = $this->batchOrders->map(fn ($o) => $o->intakeSubmission);

        if ($submissions->contains(fn ($s) => $s === null || $s->status === IntakeSubmissionStatus::Rejected)) {
            $this->step = 3;

            return;
        }

        if ($submissions->contains(fn ($s) => in_array($s->status, [
            IntakeSubmissionStatus::Pending,
            IntakeSubmissionStatus::Submitted,
            IntakeSubmissionStatus::UnderReview,
        ]))) {
            $this->step = 4;

            return;
        }

        $this->step = 5;
    }

    public function goToStep(int $step): void
    {
        if ($this->canReach($step)) {
            $this->step = $step;
        }
    }

    public function editProfile(): void
    {
        abort_unless(auth()->check(), 403);

        $this->editingProfile = true;
        $this->step = 2;
    }

    public function cancelEditProfile(): void
    {
        $this->editingProfile = false;
        $this->step = 5;
    }

    public function switchOrder(int $orderId): void
    {
        abort_unless(auth()->check(), 403);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->firstOrFail();

        $this->dashboardOrderId = $order->id;
        unset($this->currentOrder, $this->generatedDocuments, $this->expectedDocuments);
    }

    public function regenerateDocument(int $documentId): void
    {
        abort_unless(auth()->check(), 403);

        $document = GeneratedDocument::with(['oshaLocation', 'order'])->findOrFail($documentId);

        abort_unless($document->order_id === $this->dashboardOrderId, 403);

        GenerateComplianceDocument::dispatch($document->order, $document->document_type, $document->oshaLocation);

        ActivityLog::record(
            'document.regenerate_requested',
            "Regeneration requested for {$document->document_type->label()}.",
            user: auth()->user(),
            order: $document->order,
            subject: $document,
        );

        unset($this->generatedDocuments, $this->expectedDocuments);
    }

    /** @return array<string, mixed> */
    private function paymentRules(): array
    {
        $rules = [
            'cardName' => 'required|string|max:255',
            'cardNumber' => 'required|digits_between:13,19',
            'cardExpiry' => [
                'required',
                'string',
                function (string $attribute, $value, $fail) {
                    if (! preg_match('/^(\d{2})\/(\d{2})$/', (string) $value, $matches)) {
                        $fail('The card expiry field must be in MM/YY format.');

                        return;
                    }

                    [, $month, $year] = $matches;

                    if ($month < '01' || $month > '12') {
                        $fail('The card expiry month must be between 01 and 12.');

                        return;
                    }

                    if ((int) ('20'.$year) < (int) date('Y')) {
                        $fail('The card has expired.');
                    }
                },
            ],
            'cardCvc' => 'required|digits_between:3,4',
            'selectedPackageId' => 'required|exists:packages,id',
        ];

        if (auth()->guest()) {
            $rules = array_merge($rules, [
                'accountName' => 'required|string|max:100',
                'accountEmail' => 'required|email|max:150|unique:users,email',
                'accountPassword' => 'required|string|min:8',
            ]);
        }

        return $rules;
    }

    public function updated(string $property): void
    {
        $paymentFields = ['cardName', 'cardNumber', 'cardExpiry', 'cardCvc', 'selectedPackageId', 'accountName', 'accountEmail', 'accountPassword'];

        if (! in_array($property, $paymentFields, true)) {
            return;
        }

        if ($property === 'cardNumber') {
            $this->cardNumber = preg_replace('/\D/', '', $this->cardNumber ?? '');
        }

        $rules = $this->paymentRules();

        if (! array_key_exists($property, $rules)) {
            return;
        }

        $this->validateOnly($property, [$property => $rules[$property]]);
    }

    public function pay(): void
    {
        $this->cardNumber = preg_replace('/\D/', '', $this->cardNumber ?? '');

        $this->validate($this->paymentRules());

        $packages = Package::whereIn('id', array_filter([$this->selectedPackageId]))->get();

        if ($packages->isEmpty()) {
            $this->addError('selectedPackageId', 'Please select at least one package.');

            return;
        }

        if ($packages->count() === 1 && $packages->first()->isCustomQuote()) {
            $this->redirect(route('contact', ['package' => $packages->first()->slug]), navigate: true);

            return;
        }

        $packages = $packages->reject(fn ($p) => $p->isCustomQuote());

        if ($packages->isEmpty()) {
            $this->addError('selectedPackageId', 'Please select at least one package.');

            return;
        }

        if (auth()->guest()) {
            $user = User::create([
                'name' => $this->accountName,
                'email' => $this->accountEmail,
                'password' => $this->accountPassword,
                'role' => UserRole::Client,
            ]);

            Practice::create([
                'user_id' => $user->id,
                'name' => '',
            ]);

            Auth::login($user);
        }

        $batchId = (string) Str::ulid();
        $orderIds = [];

        foreach ($packages as $package) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'package_id' => $package->id,
                'checkout_batch_id' => $batchId,
                'status' => OrderStatus::Paid,
                'payment_status' => PaymentStatus::SimulatedPaid,
                'amount_paid' => $package->annual_price,
                'paid_at' => now(),
            ]);

            ActivityLog::record(
                'order.paid',
                "Payment received for {$package->name} (\${$order->amount_paid}).",
                user: auth()->user(),
                order: $order,
            );

            $orderIds[] = $order->id;
        }

        $this->orderIds = $orderIds;
        $this->dashboardOrderId = end($orderIds);

        $practice = auth()->user()->practice;
        $this->practiceName = $practice->name ?? '';
        $this->practiceAddress = $practice->address ?? '';
        $this->npiNumber = $practice->npi_number ?? '';
        $this->specialty = $practice->specialty ?? 'General Practice';
        $this->billableProviders = $practice->billable_providers_count ?? 1;

        unset($this->batchOrders, $this->completedMilestone, $this->practice, $this->selectedPackage, $this->userOrders);
    }

    public function saveProfile(): void
    {
        abort_unless(auth()->check(), 403);

        $isLocked = (bool) auth()->user()->practice?->is_profile_locked;

        $this->validate([
            'practiceName' => 'required|string|max:150',
            'logoFile' => $isLocked ? 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048' : 'required|file|mimes:png,jpg,jpeg,svg|max:2048',
            'practiceAddress' => 'required|string|max:255',
            'npiNumber' => 'required|digits:10',
            'specialty' => 'required|string|max:100',
            'billableProviders' => 'required|integer|min:1|max:9999',
        ]);

        $practice = auth()->user()->practice ?? Practice::create([
            'user_id' => auth()->id(),
            'name' => $this->practiceName,
        ]);

        $logoPath = $practice->is_profile_locked
            ? $practice->logo_path
            : ($this->logoFile ? $this->logoFile->store('logos', 'public') : $practice->logo_path);

        $practice->update([
            'name' => $practice->is_profile_locked ? $practice->name : $this->practiceName,
            'logo_path' => $logoPath,
            'address' => $this->practiceAddress ?: null,
            'npi_number' => $this->npiNumber ?: null,
            'specialty' => $this->specialty ?: null,
            'billable_providers_count' => $this->billableProviders,
            'is_profile_locked' => true,
            'locked_at' => $practice->locked_at ?? now(),
        ]);

        $this->logoFile = null;
        unset($this->practice, $this->completedMilestone);

        if ($this->editingProfile) {
            $this->editingProfile = false;
            ActivityLog::record(
                'practice.updated',
                'Practice details updated from the dashboard.',
                user: auth()->user(),
                order: $this->currentOrder,
                subject: $practice,
            );
            $this->step = 5;

            return;
        }

        $this->step = 3;
    }

    public function submitIntake(): void
    {
        abort_unless(auth()->check(), 403);

        $requiredKey = IntakeUploadType::PracticeIntake->value;

        if (empty($this->questionnaireFiles[$requiredKey])) {
            $this->addError("questionnaireFiles.{$requiredKey}", 'Please upload your Practice Intake Form.');

            return;
        }

        $this->validate([
            'questionnaireFiles.*' => 'nullable|file|max:20480',
        ]);

        $orders = $this->batchOrders;

        if ($orders->isEmpty()) {
            $this->addError("questionnaireFiles.{$requiredKey}", 'No active order found for this submission.');

            return;
        }

        $batchToken = (string) Str::ulid();

        // Store every present file once — reused across every order in the batch.
        $storedFiles = [];
        foreach ($this->questionnaireFiles as $key => $file) {
            if ($file) {
                $storedFiles[$key] = [
                    'path' => $file->store("uploads/batch/{$batchToken}"),
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ];
            }
        }

        $primaryUploadsByType = [];

        // Create every order's submission/upload rows first — the sibling-propagation
        // logic in ProcessIntakeUpload needs all of them to already exist in the database
        // before the primary upload's job runs, which can happen immediately if the queue
        // connection is synchronous.
        foreach ($orders as $order) {
            $submission = IntakeSubmission::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'status' => IntakeSubmissionStatus::Submitted,
                    'reviewer_notes' => null,
                    'submitted_at' => now(),
                ]
            );

            foreach ($storedFiles as $key => $meta) {
                $upload = IntakeUpload::create([
                    'intake_submission_id' => $submission->id,
                    'upload_type' => IntakeUploadType::from($key),
                    'original_filename' => $meta['original_filename'],
                    'storage_path' => $meta['path'],
                    'mime_type' => $meta['mime_type'],
                    'file_size' => $meta['file_size'],
                    'ai_extraction_status' => AiExtractionStatus::Pending,
                ]);

                $primaryUploadsByType[$key] ??= $upload;
            }

            Order::where('id', $order->id)->update(['status' => OrderStatus::IntakeSubmitted]);

            ActivityLog::record(
                'submission.submitted',
                "Intake form submitted for order #{$order->id}.",
                user: auth()->user(),
                order: $order,
                subject: $submission,
            );
        }

        // Only the primary upload of each type runs the actual AI extraction — its sibling
        // rows (created above) get their result copied over once it completes.
        foreach ($primaryUploadsByType as $upload) {
            ProcessIntakeUpload::dispatch($upload);
        }

        $this->questionnaireFiles = [];
        unset($this->intakeSubmission, $this->currentOrder, $this->completedMilestone, $this->batchOrders, $this->rejectedSubmission);
        $this->step = 4;
    }

    public function checkApproval(): void
    {
        $orders = $this->batchOrders;

        if ($orders->isNotEmpty() && $orders->every(fn ($o) => $o->intakeSubmission?->status === IntakeSubmissionStatus::Approved)) {
            $this->dashboardOrderId ??= $orders->max('id');
            unset($this->completedMilestone, $this->batchOrders);
            $this->step = 5;
        }
    }

    #[On('osha-location-saved')]
    public function refreshOshaLocations(): void
    {
        unset($this->oshaLocations, $this->practice);
    }
};
?>

@php
    $steps = [
        1 => 'Payment',
        2 => 'Practice Intake',
        3 => 'Upload & Confirm',
        4 => 'Review',
        5 => 'Dashboard',
    ];
    $milestone = $this->completedMilestone;
    $progressPct = ($milestone / 4) * 100;
@endphp

<div class="space-y-4">

    {{-- ── Portal preview hero ── --}}
    @php
        $heroPackages = $milestone >= 1 ? $this->batchOrders->pluck('package')->filter()->values() : collect([$this->selectedPackage])->filter()->values();
        $heroTotal = $heroPackages->sum('annual_price');
    @endphp
    <div class="rounded-[1.25rem] p-6 sm:p-7" style="background: radial-gradient(circle at top right, rgba(118,200,192,0.2), transparent 32%), linear-gradient(145deg, #12304f 0%, #1c416a 100%);">
        <div class="flex flex-col lg:flex-row lg:items-center gap-5">
            <div class="flex-1">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-[0.7rem] font-extrabold tracking-[0.08em] uppercase bg-accent/16 text-[#dff7f3] mb-2">Portal preview</span>
                <h1 class="text-xl sm:text-2xl font-bold text-white mb-1">
                    @if($heroPackages->isEmpty())
                        Choose a package
                    @elseif($heroPackages->count() === 1)
                        Selected package: {{ $heroPackages->first()->name }}
                    @else
                        {{ $heroPackages->count() }} packages selected
                    @endif
                </h1>
                <p class="text-white/60 text-sm">Payment, practice intake, review, and document generation.</p>
            </div>
            @if($heroPackages->isNotEmpty())
                <div class="bg-white/92 rounded-[1.25rem] p-4 min-w-48">
                    <div class="text-empower-muted text-xs uppercase tracking-wider font-semibold mb-1">Summary</div>
                    <div class="text-xl font-extrabold text-navy mb-0.5">${{ number_format($heroTotal) }}</div>
                    <div class="text-empower-muted text-xs">per provider / year</div>
                    <div class="text-empower-muted text-xs mt-1">
                        {{ $heroPackages->pluck('name')->implode(' + ') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Stepper ── --}}
    <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
        <div class="flex justify-between items-center mb-4">
            <p class="text-xs text-[#5d6e7f]">Complete each step to receive your compliance documents.</p>
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-[#12304f]/[0.08] text-[#12304f] text-[0.72rem] font-extrabold tracking-wide uppercase">
                Step {{ $step }} of 5
            </span>
        </div>

        <div class="flex items-start gap-1.5 overflow-x-auto pb-1 -mb-1">
            @foreach ($steps as $n => $title)
                @php
                    $isDone = $n <= $milestone;
                    $isActive = $n === $step;
                    $reachable = $this->canReach($n);
                @endphp
                <div class="flex flex-col items-center gap-1.5 flex-shrink-0 min-w-[4.5rem] {{ $reachable ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' }}"
                     @if($reachable && !$isActive) wire:click="goToStep({{ $n }})" @endif>
                    <div class="w-9 h-9 rounded-full inline-flex items-center justify-center text-sm font-extrabold flex-shrink-0
                        {{ $isActive ? 'bg-[#12304f] text-white' : ($isDone ? 'bg-[#d7f3ea] text-[#117a51]' : 'bg-[#edf2f7] text-[#5d6e7f]') }}">
                        @if($isDone && !$isActive) ✓ @else {{ $n }} @endif
                    </div>
                    <div class="text-[0.78rem] text-center leading-tight max-w-[6rem]
                        {{ $isActive ? 'font-bold text-[#12304f]' : 'text-[#5d6e7f]' }}">
                        {{ $title }}
                    </div>
                </div>
                @if(!$loop->last)
                    <div class="flex-1 h-0.5 mt-[1.125rem] min-w-4 {{ $n < $milestone ? 'bg-[#b8e8d7]' : 'bg-[#dfe7ef]' }}"></div>
                @endif
            @endforeach
        </div>

        <div class="mt-4 h-1.5 bg-[#dbe4ee] rounded-full overflow-hidden">
            <div class="h-full bg-[#12304f] rounded-full transition-all duration-500" style="width: {{ $progressPct }}%"></div>
        </div>
    </div>

    {{-- ── Step 1: Payment ── --}}
    @if($step === 1)
        <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
            <p class="text-xs font-extrabold uppercase tracking-widest text-empower-muted mb-1">Step 1</p>
            <h2 class="text-lg font-semibold text-navy mb-1">{{ $milestone >= 1 ? 'Payment' : 'Selected Package' }}</h2>
            <p class="text-sm text-empower-muted">
                Complete payment first to unlock your practice intake form. Your documents are generated automatically once intake is submitted and reviewed.
            </p>
            @if($milestone < 1)
                <p class="text-sm text-empower-muted mt-2 mb-0">Your final invoice reflects the provider count you confirm during intake in the next step.</p>
            @endif
        </div>

        @if($milestone >= 1)
            <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                <div class="flex items-center gap-3 rounded-xl bg-[#eef8f3] border border-[#bfe3d2] px-4 py-3.5 mb-4">
                    <span class="text-[#117a51]">&#10003;</span>
                    <p class="text-sm font-semibold text-[#0f7a4f]">Payment received. Continue to download your practice intake form.</p>
                </div>
                <div class="divide-y divide-[#eef2f6]">
                    @foreach($this->batchOrders as $order)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <span class="text-sm font-semibold text-[#173045]">{{ $order->package?->name }}</span>
                            <a href="{{ route('orders.receipt', $order) }}"
                                class="inline-flex items-center gap-1.5 rounded border border-empower-border px-3 py-1.5 text-xs font-semibold text-navy hover:bg-page transition-colors">
                                &#8681; Download Receipt
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                <h3 class="text-sm font-semibold text-navy mb-3">Selected Package</h3>
                @if(! $this->selectedPackage)
                    <p class="text-sm text-empower-muted italic mb-3">No package selected.</p>
                    <a href="{{ route('home') }}#pricing" class="text-xs font-bold text-[#1a7aad] hover:underline">Browse packages &rarr;</a>
                @else
                    <div class="flex items-center justify-between gap-3 py-3 border-b border-[#eef2f6] mb-3">
                        <div>
                            <p class="text-sm font-semibold text-[#173045]">{{ $this->selectedPackage->name }}</p>
                            <p class="text-xs text-empower-muted">${{ number_format($this->selectedPackage->annual_price) }} / year</p>
                        </div>
                        <a href="{{ route('home') }}#pricing" class="text-xs font-semibold text-[#1a7aad] hover:underline">Change package</a>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-[#eef2f6]">
                        <span class="text-sm font-semibold text-[#173045]">Total</span>
                        <span class="text-lg font-extrabold text-navy">${{ number_format($this->selectedPackage->annual_price) }}</span>
                    </div>
                @endif
            </div>

            @auth
            @else
                    <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                        <h3 class="text-sm font-semibold text-navy mb-1">Account Information</h3>
                        <p class="text-xs text-empower-muted mb-4">Create the account that will manage this practice's Empower portal.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Your name <span class="text-red-500">*</span></label>
                                <input wire:model.blur="accountName" type="text" placeholder="Jane Provider"
                                    class="w-full rounded-xl border border-empower-border bg-[#f8fbfd] px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                                @error('accountName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Email address <span class="text-red-500">*</span></label>
                                <input wire:model.blur="accountEmail" type="email" placeholder="jane@practice.com"
                                    class="w-full rounded-xl border border-empower-border bg-[#f8fbfd] px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                                @error('accountEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Password <span class="text-red-500">*</span></label>
                                <input wire:model.blur="accountPassword" type="password" placeholder="Min. 8 characters"
                                    class="w-full rounded-xl border border-empower-border bg-[#f8fbfd] px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                                @error('accountPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endauth

                <div class="bg-white border border-empower-border rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                    <h3 class="text-sm font-semibold text-navy mb-1">Payment Details</h3>
                    <p class="text-xs text-empower-muted mb-4">Preview form only — no real payment is processed here.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Name on card</label>
                            <input wire:model.blur="cardName" type="text" placeholder="Jane Provider"
                                class="w-full rounded-xl border border-empower-border bg-[#f8fbfd] px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                            @error('cardName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Card number</label>
                            <input wire:model.blur="cardNumber" type="text" placeholder="4242 4242 4242 4242"
                                class="w-full rounded-xl border border-empower-border bg-[#f8fbfd] px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                            @error('cardNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Expiry</label>
                            <input wire:model.blur="cardExpiry" type="text" placeholder="MM / YY" inputmode="numeric" maxlength="5"
                                x-on:input="let digits = $el.value.replace(/[^0-9]/g, '').slice(0, 4); let deleting = ($event.inputType || '').startsWith('delete'); $el.value = (digits.length >= 2 && !deleting) ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits"
                                class="w-full rounded-xl border border-empower-border bg-[#f8fbfd] px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                            @error('cardExpiry') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-[#31465b] mb-1.5">CVC</label>
                            <input wire:model.blur="cardCvc" type="text" placeholder="123"
                                class="w-full rounded-xl border border-empower-border bg-[#f8fbfd] px-4 py-2.5 text-sm text-empower-text focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition">
                            @error('cardCvc') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button wire:click="pay"
                            class="inline-flex items-center gap-1 rounded bg-accent px-5 py-2 text-sm font-bold text-navy-dark hover:bg-accent-dark transition-colors"
                            wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
                            <span wire:loading.remove>Pay ${{ number_format($this->selectedPackage?->annual_price ?? 0) }} &rarr;</span>
                            <span wire:loading>Processing…</span>
                        </button>
                    </div>
                </div>
        @endif

        <div class="flex justify-end">
            <button wire:click="goToStep(2)" @disabled($milestone < 1)
                class="inline-flex items-center gap-1 rounded bg-accent px-5 py-2 text-sm font-bold text-navy-dark hover:bg-accent-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                Continue to Intake Form &rarr;
            </button>
        </div>
    @endif

    {{-- ── Step 2: Practice Intake ── --}}
    @if($step === 2)
        <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
            @if($editingProfile)
                <div class="flex items-center gap-2 rounded-xl bg-[#edf6ff] border border-[#bfdcf3] px-4 py-3 mb-4 text-sm text-[#12304f]">
                    You're updating practice details for an already-paid plan. Documents you've already generated will be marked outdated until you regenerate them.
                </div>
                <h2 class="text-lg font-semibold text-[#12304f] mb-1">Update Your Practice Details</h2>
            @else
                <p class="text-xs font-extrabold uppercase tracking-widest text-[#5d6e7f] mb-1">Step 2</p>
                <h2 class="text-lg font-semibold text-[#12304f] mb-1">Confirm Key Details</h2>
            @endif
            <p class="text-sm text-[#5d6e7f] mb-5">This information is inserted directly into your compliance documents — please check accuracy. Practice Name and Logo lock permanently after your first submission.</p>

            <div class="flex items-start gap-4 mb-5">
                <div class="flex-shrink-0 w-16 h-16 rounded-xl border-2 border-dashed border-[#b9cfe0] bg-[#f7fbfd] flex items-center justify-center overflow-hidden">
                    @if($this->practice?->logo_path)
                        <img src="{{ Storage::disk('public')->url($this->practice->logo_path) }}" alt="Practice logo" class="w-full h-full object-contain">
                    @else
                        <span class="text-[0.62rem] font-bold text-[#5d6e7f] uppercase tracking-wider">Logo</span>
                    @endif
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-[#31465b] mb-1.5">
                        Practice Logo
                        @if($this->practice?->is_profile_locked)
                            <span class="ml-1 inline-flex items-center gap-0.5 text-[0.68rem] font-extrabold text-[#9a6700] bg-[#fff3cd] rounded px-1.5 py-0.5 uppercase tracking-wider">🔒 Locked</span>
                        @else
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    @unless($this->practice?->is_profile_locked)
                        <input wire:model="logoFile" type="file" accept=".png,.jpg,.jpeg,.svg"
                            class="block w-full text-sm text-[#5d6e7f] file:mr-3 file:py-1.5 file:px-4 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[#12304f] file:text-white hover:file:bg-[#0a2037] cursor-pointer">
                        @error('logoFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @endunless
                    <p class="mt-1 text-xs text-[#5d6e7f]">PNG or SVG recommended, square aspect ratio.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-[#31465b] mb-1.5">
                        Practice Name <span class="text-red-500">*</span>
                        @if($this->practice?->is_profile_locked)
                            <span class="ml-1 inline-flex items-center gap-0.5 text-[0.68rem] font-extrabold text-[#9a6700] bg-[#fff3cd] rounded px-1.5 py-0.5 uppercase tracking-wider">🔒 Locked</span>
                        @endif
                    </label>
                    <input wire:model="practiceName" type="text" placeholder="Riverside Family Medicine"
                        {{ $this->practice?->is_profile_locked ? 'disabled' : '' }}
                        class="w-full rounded-xl border {{ $errors->has('practiceName') ? 'border-red-400' : 'border-[#dbe4ee]' }} {{ $this->practice?->is_profile_locked ? 'bg-[#f0f4f8] cursor-not-allowed' : 'bg-[#f8fbfd]' }} px-4 py-2.5 text-sm text-[#173045] focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition">
                    @error('practiceName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Practice Address <span class="text-red-500">*</span></label>
                    <input wire:model="practiceAddress" type="text" placeholder="123 Main St, Springfield, IL"
                        class="w-full rounded-xl border {{ $errors->has('practiceAddress') ? 'border-red-400' : 'border-[#dbe4ee]' }} bg-[#f8fbfd] px-4 py-2.5 text-sm text-[#173045] focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition">
                    @error('practiceAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#31465b] mb-1.5">NPI Number <span class="text-red-500">*</span></label>
                    <input wire:model="npiNumber" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="10" placeholder="1234567890"
                        x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '')"
                        class="w-full rounded-xl border {{ $errors->has('npiNumber') ? 'border-red-400' : 'border-[#dbe4ee]' }} bg-[#f8fbfd] px-4 py-2.5 text-sm text-[#173045] focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition">
                    @error('npiNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Specialty <span class="text-red-500">*</span></label>
                    <select wire:model="specialty"
                        class="w-full rounded-xl border {{ $errors->has('specialty') ? 'border-red-400' : 'border-[#dbe4ee]' }} bg-[#f8fbfd] px-4 py-2.5 text-sm text-[#173045] focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition">
                        @foreach(['General Practice','Dermatology','Cardiology','Behavioral Health','Pediatrics','Orthopedics','Dental','Other'] as $s)
                            <option value="{{ $s }}" @selected($specialty === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('specialty') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#31465b] mb-1.5">Billable Providers <span class="text-red-500">*</span></label>
                    <input wire:model="billableProviders" type="number" min="1"
                        class="w-full rounded-xl border {{ $errors->has('billableProviders') ? 'border-red-400' : 'border-[#dbe4ee]' }} bg-[#f8fbfd] px-4 py-2.5 text-sm text-[#173045] focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition">
                    @error('billableProviders') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- OSHA Locations --}}
        <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-[#12304f]">OSHA Locations</h3>
                    <p class="text-xs text-[#5d6e7f] mt-0.5">Add every practice location that needs an OSHA safety questionnaire on file.</p>
                </div>
                <button type="button" wire:click="$dispatch('open-osha-modal')"
                    class="inline-flex items-center gap-1 rounded bg-[#12304f] px-4 py-2 text-xs font-bold text-white hover:bg-[#0a2037] transition-colors">
                    + Add Location
                </button>
            </div>

            @if($this->oshaLocations->isEmpty())
                <p class="text-sm text-[#5d6e7f] italic">No locations added yet.</p>
            @else
                <div class="divide-y divide-[#eef2f6]">
                    @foreach($this->oshaLocations as $loc)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-semibold text-[#12304f]">{{ $loc->name }}</p>
                                @if($loc->address)
                                    <p class="text-xs text-[#5d6e7f]">{{ $loc->address }}</p>
                                @endif
                            </div>
                            <button type="button" wire:click="$dispatch('open-osha-modal', { locationId: {{ $loc->id }} })"
                                class="text-xs font-semibold text-[#1a7aad] hover:underline flex-shrink-0">
                                Edit
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @unless($editingProfile)
            {{-- Questionnaire downloads — one per file the client's purchased package(s) need --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->applicableQuestionnaires as $questionnaire)
                    @php $downloadKey = 'questionnaire-downloaded-'.auth()->id().'-'.$questionnaire['uploadType']->value; @endphp
                    <div
                        x-data="{ downloaded: localStorage.getItem('{{ $downloadKey }}') === '1' }"
                        class="border-2 border-dashed border-[#b9cfe0] rounded-[1.25rem] bg-[#f7fbfd] p-6 text-center flex flex-col"
                    >
                        <div class="w-14 h-14 rounded-full bg-[#12304f]/[0.08] text-[#12304f] inline-flex items-center justify-center text-2xl mb-3 mx-auto">📄</div>
                        <p class="font-semibold text-sm text-[#12304f] mb-1">{{ $questionnaire['title'] }}</p>
                        <p class="text-xs text-[#5d6e7f] mb-4 flex-1">{{ $questionnaire['description'] }}</p>
                        <a href="{{ Questionnaires::url($questionnaire['file']) }}"
                            @click="downloaded = true; localStorage.setItem('{{ $downloadKey }}', '1')"
                            class="inline-flex items-center justify-center gap-1.5 rounded bg-[#12304f] px-5 py-2 text-sm font-bold text-white hover:bg-[#0a2037] transition-colors">
                            &#8681; Download Form
                        </a>
                        <p x-show="downloaded" x-cloak class="mt-2 text-xs font-semibold text-[#0f7a4f]">
                            &#10003; Downloaded
                        </p>
                        <p x-show="!downloaded" x-cloak class="mt-2 text-xs text-[#5d6e7f]">
                            Not downloaded yet
                        </p>
                    </div>
                @endforeach
            </div>
        @endunless

        <div class="flex justify-between">
            @if($editingProfile)
                <button wire:click="cancelEditProfile"
                    class="rounded border border-[#dbe4ee] px-5 py-2 text-sm font-semibold text-[#5d6e7f] hover:bg-[#f4f7fb] transition-colors">
                    Cancel
                </button>
            @else
                <button wire:click="goToStep(1)"
                    class="rounded border border-[#dbe4ee] px-5 py-2 text-sm font-semibold text-[#5d6e7f] hover:bg-[#f4f7fb] transition-colors">
                    &larr; Back
                </button>
            @endif
            <button wire:click="saveProfile"
                class="inline-flex items-center gap-1 rounded bg-[#76c8c0] px-5 py-2 text-sm font-bold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors"
                wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
                <span wire:loading.remove>{{ $editingProfile ? 'Save Changes' : 'Submit Profile & Continue' }} &rarr;</span>
                <span wire:loading>Saving…</span>
            </button>
        </div>

        <livewire:portal.osha-location-modal :practiceId="$this->practice?->id ?? 0" />
    @endif

    {{-- ── Step 3: Intake Upload ── --}}
    @if($step === 3)
        @php $rejected = (bool) $this->rejectedSubmission; @endphp

        <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
            <p class="text-xs font-extrabold uppercase tracking-widest text-[#5d6e7f] mb-1">Step 3</p>
            <h2 class="text-lg font-semibold text-[#12304f] mb-1">Intake Upload</h2>
            <p class="text-sm text-[#5d6e7f] mb-5">
                @if($rejected)
                    Your previous submission was rejected. Please address the reviewer's notes and re-upload.
                @else
                    Upload your completed intake documents. Our team will review them before generating your compliance documents.
                @endif
            </p>

            @if($rejected && $this->rejectedSubmission?->reviewer_notes)
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold mb-0.5">Reviewer notes:</p>
                    <p>{{ $this->rejectedSubmission->reviewer_notes }}</p>
                </div>
            @endif

            {{-- One upload box per questionnaire shown for download in Step 2 --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                @foreach($this->applicableQuestionnaires as $questionnaire)
                    @php
                        $uploadKey = $questionnaire['uploadType']->value;
                        $uploadedFile = $this->questionnaireFiles[$uploadKey] ?? null;
                    @endphp
                    <div class="border-2 border-dashed border-[#b9cfe0] rounded-[1rem] bg-[#f7fbfd] p-6 text-center">
                        <div class="w-14 h-14 rounded-full bg-[#12304f]/[0.08] text-[#12304f] inline-flex items-center justify-center text-2xl mb-3">📄</div>
                        <p class="font-semibold text-sm text-[#12304f] mb-1">
                            {{ $questionnaire['title'] }}
                            @unless($questionnaire['required'])
                                <span class="text-[#5d6e7f] font-normal">(optional)</span>
                            @endunless
                        </p>
                        <p class="text-xs text-[#5d6e7f] mb-3">{{ $questionnaire['description'] }}</p>
                        <input type="file" wire:model="questionnaireFiles.{{ $uploadKey }}" accept=".pdf,.jpg,.jpeg,.png,.docx"
                            class="block mx-auto text-sm text-[#5d6e7f] file:mr-3 file:py-1.5 file:px-4 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[#12304f] file:text-white hover:file:bg-[#0a2037] cursor-pointer">
                        @error("questionnaireFiles.{$uploadKey}") <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                        @if($uploadedFile)
                            <div wire:loading.remove wire:target="questionnaireFiles.{{ $uploadKey }}">
                                <span class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#edf6ff] text-[#12304f] text-sm font-semibold">
                                    ✓ {{ $uploadedFile->getClientOriginalName() }}
                                </span>
                            </div>
                            <div wire:loading wire:target="questionnaireFiles.{{ $uploadKey }}" class="mt-2 text-xs text-[#5d6e7f]">Uploading…</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <button wire:click="submitIntake"
                    class="inline-flex items-center gap-1 rounded bg-[#76c8c0] px-5 py-2 text-sm font-bold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors"
                    wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
                    <span wire:loading.remove>Submit for Review &rarr;</span>
                    <span wire:loading>Submitting…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- ── Step 4: Review Status ── --}}
    @if($step === 4)
        <div wire:poll.5s="checkApproval"
             class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
            <p class="text-xs font-extrabold uppercase tracking-widest text-[#5d6e7f] mb-1">Step 4</p>
            <h2 class="text-lg font-semibold text-[#12304f] mb-1">Review Status</h2>
            <p class="text-sm text-[#5d6e7f] mb-5">Our team is reviewing your submission. This page refreshes automatically.</p>

            <div class="space-y-3">
                @forelse($this->batchOrders as $order)
                    @php
                        $status = $order->intakeSubmission?->status;
                        [$cardClasses, $iconClasses, $icon, $label] = match(true) {
                            $status === IntakeSubmissionStatus::Approved => ['bg-[#f0fdf4] border-[#86efac]', 'bg-[#dcfce7] text-[#166534]', '✅', 'Approved — documents are being generated'],
                            $status === IntakeSubmissionStatus::UnderReview => ['bg-[#fffbf0] border-[#fde68a]', 'bg-[#fef3c7] text-[#92400e]', '🔍', 'Under review'],
                            $status === IntakeSubmissionStatus::Rejected => ['bg-[#fff1f2] border-[#fecdd3]', 'bg-[#fee2e2] text-[#9f1239]', '❌', 'Submission rejected'],
                            default => ['bg-[#f4f7fb] border-[#dbe4ee]', 'bg-[#12304f]/[0.08] text-[#12304f]', '⏳', 'Submission received'],
                        };
                        $textClass = match(true) {
                            $status === IntakeSubmissionStatus::Approved => 'text-[#166534]',
                            $status === IntakeSubmissionStatus::UnderReview => 'text-[#92400e]',
                            $status === IntakeSubmissionStatus::Rejected => 'text-[#9f1239]',
                            default => 'text-[#12304f]',
                        };
                    @endphp
                    <div class="flex items-start gap-4 rounded-xl border p-4 {{ $cardClasses }}">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg flex-shrink-0 {{ $iconClasses }}">{{ $icon }}</div>
                        <div class="flex-1">
                            <p class="font-semibold {{ $textClass }}">{{ $order->package?->name }} &middot; {{ $label }}</p>
                            @if($status === IntakeSubmissionStatus::Rejected && $order->intakeSubmission?->reviewer_notes)
                                <p class="text-sm text-[#881337] mt-1">{{ $order->intakeSubmission->reviewer_notes }}</p>
                                <button wire:click="goToStep(3)" class="mt-2 inline-flex items-center gap-1 rounded bg-[#9f1239] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#881337] transition-colors">
                                    Re-upload &rarr;
                                </button>
                            @elseif(! $status)
                                <p class="text-sm text-[#5d6e7f]">No submission found.</p>
                            @else
                                <p class="text-sm text-[#5d6e7f]">
                                    {{ $status === IntakeSubmissionStatus::UnderReview ? "An Empower compliance specialist is reviewing your submission." : 'Your intake documents are in the queue for review.' }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-[#5d6e7f] italic">No submission found.</p>
                @endforelse
            </div>

            @if($milestone >= 4)
                <button wire:click="goToStep(5)" class="mt-4 inline-flex items-center gap-1 rounded bg-[#76c8c0] px-4 py-1.5 text-xs font-bold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors">
                    Go to Dashboard &rarr;
                </button>
            @endif
        </div>
    @endif

    {{-- ── Step 5: Dashboard ── --}}
    @if($step === 5)
        <p class="text-[0.65rem] font-extrabold uppercase tracking-widest text-[#5d6e7f]">Your Dashboard</p>

        {{-- Practice info bar --}}
        <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-4 flex flex-wrap items-center gap-3">
            <div class="w-11 h-11 rounded-xl border-2 border-dashed border-[#b9cfe0] bg-[#f7fbfd] flex items-center justify-center overflow-hidden flex-shrink-0">
                @if($this->practice?->logo_path)
                    <img src="{{ Storage::disk('public')->url($this->practice->logo_path) }}" alt="Practice logo" class="w-full h-full object-contain">
                @endif
            </div>
            <div class="flex-1 min-w-[200px]">
                <div class="font-bold text-[#12304f] text-sm">
                    {{ $this->practice?->name ?: 'Practice name not set' }}
                    @if($this->practice?->is_profile_locked)
                        <span class="ml-1 inline-flex items-center gap-0.5 text-[0.62rem] font-extrabold text-[#9a6700] bg-[#fff3cd] rounded px-1.5 py-0.5 uppercase tracking-wider">🔒 Locked</span>
                    @endif
                </div>
                <div class="text-xs text-[#5d6e7f]">
                    {{ auth()->user()->email }}
                    &middot; Effective {{ $this->practiceEffectiveDate?->format('M j, Y') }}
                    &middot; Renews {{ $this->practiceEffectiveDate?->copy()->addYear()->format('M j, Y') }}
                </div>
            </div>
            <button wire:click="editProfile"
                class="rounded border border-[#dbe4ee] px-3.5 py-1.5 text-xs font-semibold text-[#12304f] hover:bg-[#f4f7fb] transition-colors">
                &#9998; Update Practice Info
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 border-b border-[#dbe4ee]">
            @foreach(['history' => 'History', 'payments' => 'Payments', 'documents' => 'Documents'] as $tabKey => $tabLabel)
                <button wire:click="$set('dashboardTab', '{{ $tabKey }}')"
                    class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $dashboardTab === $tabKey ? 'border-[#12304f] text-[#12304f]' : 'border-transparent text-[#5d6e7f] hover:text-[#12304f]' }}">
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>

        @if($dashboardTab === 'documents')
            @if($this->userOrders->count() > 1)
                <div class="flex flex-wrap gap-2">
                    @foreach($this->userOrders as $order)
                        <button type="button" wire:click="switchOrder({{ $order->id }})"
                            class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-bold transition-colors {{ $this->dashboardOrderId === $order->id ? 'bg-navy text-white' : 'bg-white border border-empower-border text-empower-muted hover:border-navy/40' }}">
                            {{ $order->package?->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                <h3 class="text-sm font-semibold text-[#12304f]">
                    {{ $this->currentOrder?->package?->name }}
                    <span class="text-xs font-normal text-[#5d6e7f]">&middot; {{ $this->expectedDocuments->count() }} doc(s) &middot; purchased {{ $this->currentOrder?->paid_at?->format('M j, Y') }}</span>
                </h3>

                <div class="divide-y divide-[#eef2f6] mt-3">
                    @foreach($this->expectedDocuments as $row)
                        @php
                            $type = $row['type'];
                            $location = $row['location'];
                            $doc = $row['document'];
                            $title = $type->label().($location ? ' — '.$location->name : '');
                        @endphp
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                    <p class="text-sm font-bold text-[#12304f]">{{ $title }}</p>
                                    @if(! $doc)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6rem] font-extrabold uppercase tracking-wider bg-[#fff3cd] text-[#9a6700]">Generating</span>
                                    @elseif($doc->is_stale)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6rem] font-extrabold uppercase tracking-wider bg-[#fde2e2] text-[#a53b3b]">Outdated</span>
                                    @elseif($doc->status === DocumentStatus::Completed)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6rem] font-extrabold uppercase tracking-wider bg-[#dff7f0] text-[#0f7a4f]">Ready</span>
                                    @elseif($doc->status === DocumentStatus::Failed)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6rem] font-extrabold uppercase tracking-wider bg-[#fde2e2] text-[#a53b3b]">Failed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6rem] font-extrabold uppercase tracking-wider bg-[#fff3cd] text-[#9a6700]">Generating</span>
                                    @endif
                                </div>
                                @if($doc?->generated_at)
                                    <p class="text-xs text-[#5d6e7f]">
                                        {{ $doc->is_stale ? 'Last generated' : 'Generated' }} {{ $doc->generated_at->format('M j, Y') }}{{ $doc->is_stale ? ' — details changed since.' : '' }}
                                    </p>
                                @else
                                    <p class="text-xs text-[#5d6e7f]">We'll notify you once this is ready.</p>
                                @endif
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                @if($doc?->is_stale)
                                    <button wire:click="regenerateDocument({{ $doc->id }})" wire:confirm="Regenerate this document with your latest details?"
                                        class="text-xs font-bold rounded bg-[#12304f] text-white px-3 py-1.5 hover:bg-[#0a2037] transition-colors">
                                        Regenerate
                                    </button>
                                @elseif($doc?->status === DocumentStatus::Completed && $doc->pdf_storage_path)
                                    <a href="{{ route('documents.download', $doc) }}"
                                        class="text-xs font-bold rounded bg-[#12304f] text-white px-3 py-1.5 hover:bg-[#0a2037] transition-colors">
                                        Download PDF
                                    </a>
                                @elseif($doc?->status === DocumentStatus::Completed && $doc->docx_storage_path)
                                    <a href="{{ route('documents.download', $doc) }}?format=docx"
                                        class="text-xs font-bold rounded bg-[#12304f] text-white px-3 py-1.5 hover:bg-[#0a2037] transition-colors">
                                        Download DOCX
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(! empty($this->currentOrder?->package?->features))
                    <p class="text-xs text-[#5d6e7f] mt-3"><strong>Services included:</strong> {{ implode(' &middot; ', $this->currentOrder->package->features) }}</p>
                @endif
                <p class="text-xs text-[#5d6e7f] mt-2">For any queries, <a href="{{ route('contact') }}" wire:navigate class="font-semibold text-[#1a7aad] hover:underline">contact us</a>.</p>
            </div>

            {{-- Add-on promo --}}
            <div class="rounded-2xl bg-gradient-to-r from-[#76c8c0]/12 to-white border border-[#dbe4ee] p-5">
                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#12304f] text-white text-sm">🛡</span>
                    <div class="flex-1">
                        <span class="text-[0.62rem] font-extrabold tracking-widest uppercase text-[#5bb2aa]">Add-on &middot; Available for Any Package</span>
                        <h3 class="text-sm font-semibold text-[#12304f] mt-1">Legal Review &amp; Risk Assessment, by Frier Levitt</h3>
                        <p class="text-xs text-[#5d6e7f] mt-1">Kovel-protected coding &amp; documentation review with a privileged legal analysis letter.</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-lg font-extrabold text-[#12304f]">$2,500</div>
                        <div class="text-[0.65rem] text-[#5d6e7f]">flat-fee / practice</div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-[#5d6e7f]">🔒 Documents are delivered as protected, locked PDFs. Need a change? Use <strong>Update Practice Info</strong> above and regenerate — included at no extra charge during your active plan year.</p>
        @elseif($dashboardTab === 'payments')
            <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                <h3 class="text-sm font-semibold text-[#12304f] mb-3">Purchase History</h3>
                @forelse($this->userOrders as $order)
                    <div class="flex items-center justify-between gap-3 py-2.5 border-b border-[#eef2f6] last:border-b-0">
                        <span class="text-sm font-semibold text-[#173045]">{{ $order->package?->name }}</span>
                        <span class="text-sm text-[#5d6e7f]">${{ number_format($order->amount_paid) }}</span>
                        <span class="text-xs text-[#5d6e7f]">{{ $order->paid_at?->format('M j, Y') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-[#5d6e7f] italic">No purchases yet.</p>
                @endforelse
            </div>

            <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                <h3 class="text-sm font-semibold text-[#12304f] mb-1">Add a Package</h3>
                <p class="text-xs text-[#5d6e7f] mb-3">Explore other compliance tiers for this practice.</p>
                <a href="{{ route('home') }}#pricing" class="text-xs font-bold text-[#1a7aad] hover:underline">View all packages &rarr;</a>
            </div>
        @else
            <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] p-5">
                <h3 class="text-sm font-semibold text-[#12304f] mb-3">Account Activity</h3>
                @forelse($this->activityLog as $log)
                    <div class="py-2.5 border-b border-[#eef2f6] last:border-b-0">
                        <p class="text-sm font-semibold text-[#173045]">{{ $log->description }}</p>
                        <p class="text-xs text-[#5d6e7f]">{{ $log->created_at->format('M j, Y g:ia') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-[#5d6e7f] italic">No activity yet.</p>
                @endforelse
            </div>
        @endif

        <div class="flex justify-start">
            <button wire:click="goToStep(4)" class="rounded border border-[#dbe4ee] px-4 py-2 text-sm font-semibold text-[#5d6e7f] hover:bg-[#f4f7fb] transition-colors">
                Back to Review
            </button>
        </div>
    @endif

</div>
