<x-layouts.marketing title="Proactive Compliance by Empower: Healthcare Compliance Portal" :on-home-page="true">

    {{-- Hero --}}
    <section id="home" class="py-16 lg:py-20"
        style="background: radial-gradient(circle at 84% 18%, rgba(11, 158, 208, 0.36), transparent 34%), radial-gradient(circle at 8% 0%, rgba(34, 153, 221, 0.20), transparent 30%), linear-gradient(115deg, #f2f8fd 0%, #dff1fb 44%, #c7e7f6 100%);">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="w-full">
                <span
                    class="inline-block rounded-full border border-[#0b9ed0]/30 bg-[#e9f7fc] px-4 py-1.5 text-xs font-semibold text-[#087fa9] tracking-wide mb-5">
                    Proactive Compliance
                </span>
                <h1 class="text-3xl lg:text-4xl font-bold text-[#0e3a61] mb-4 leading-tight">
                    Proactive Compliance<br class="hidden sm:block">
                    by Empower
                </h1>
                <p class="w-full max-w-3xl text-base lg:text-lg text-[#5c778d] mb-6 leading-relaxed">
                    Empower helps healthcare practices build and maintain a compliance program structured on the
                    seven elements described in OIG guidance, with the policies, training, and ongoing support to
                    stay audit-ready. Support scales from reviewing your current program to a fully custom one.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="#pricing"
                        class="rounded-lg bg-[#2299dd] px-6 py-3 text-sm font-semibold text-white hover:bg-[#087fa9] transition-colors shadow-lg">Explore
                        Packages</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Why now / Who is this for --}}
    <section id="services" class="py-14 lg:py-16 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 items-start">
                <div>
                    <span class="text-xs font-bold tracking-widest uppercase text-[#0b9ed0]">Why Now</span>
                    <h2 class="mt-3 text-3xl font-bold text-[#0e3a61] leading-tight">
                        Documentation and coding remain among the most common subjects of payor and regulatory
                        review.
                    </h2>
                    <p class="mt-4 text-[#5c778d] leading-relaxed">
                        Payors and regulators expect a documented, active program: written policies, real training,
                        exclusions screening, a reporting channel, and evidence that someone owns it. Gaps in any one
                        of these can expose a practice to audits, overpayment demands, and penalties.
                    </p>
                </div>

                <div class="rounded-2xl border border-[#d4e5f1] bg-[#f9fcff] p-7">
                    <h3 class="font-semibold text-[#0e3a61] mb-3">Who is this for?</h3>
                    <p class="text-sm text-[#5c778d] leading-relaxed mb-4">
                        Compliance programs are required by regulation for certain entity types and are expected of
                        all providers under OIG guidance; many payor participation agreements require one.
                    </p>
                    <ul class="space-y-2.5 text-sm text-[#173a59]">
                        @foreach([
                        'Practices that bill federal healthcare programs.',
                        'Commonly required in payor participation agreements.',
                        'Expected under OIG guidance; required by regulation for certain entity types.',
                        ] as $point)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#0b9ed0]"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- How your program works: the seven elements --}}
    <section id="how-it-works" class="py-14 lg:py-16 bg-gradient-to-br from-[#0b2e4b] via-[#0e3a61] to-[#16638e]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <span class="text-xs font-bold tracking-widest uppercase text-[#8ddaf2]">How Your Program Works</span>
                <h2 class="mt-3 text-3xl font-bold text-white">The seven elements, mapped</h2>
            </div>

            <div class="rounded-2xl border border-white/15 bg-white/5 overflow-hidden">
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 px-6 py-4 border-b border-white/10">
                    <span class="text-sm font-semibold text-white">Structured on the seven elements described in OIG
                        guidance</span>
                    <span class="text-xs font-semibold text-[#8ddaf2] uppercase tracking-wide">Structured on OIG
                        guidance</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-white/10">
                    <div class="p-7">
                        <span class="text-xs font-bold tracking-widest uppercase text-[#8ddaf2]">Your Program
                            Documents</span>
                        <ul class="mt-4 space-y-3 text-sm text-white/85">
                            @foreach([
                            ['Written standards & policies', 'tailored to your practice'],
                            ['Compliance oversight structure', 'and designated roles'],
                            ['Training & education', 'across required topics'],
                            ['Reporting channels', 'including a compliance hotline'],
                            ] as [$lead, $rest])
                            <li class="flex items-start gap-2">
                                <svg class="h-4 w-4 mt-0.5 shrink-0 text-[#8ddaf2]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span><span class="font-semibold text-white">{{ $lead }}</span> {{ $rest }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="p-7">
                        <span class="text-xs font-bold tracking-widest uppercase text-[#8ddaf2]">Your Practice
                            Operates, With Our Support</span>
                        <ul class="mt-4 space-y-3 text-sm text-white/85">
                            @foreach([
                            ['Ongoing monitoring & auditing', 'of day-to-day operations'],
                            ['Enforcement & discipline', 'through your own policies'],
                            ['Corrective action', 'when a review surfaces a finding'],
                            ] as [$lead, $rest])
                            <li class="flex items-start gap-2">
                                <svg class="h-4 w-4 mt-0.5 shrink-0 text-[#8ddaf2]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span><span class="font-semibold text-white">{{ $lead }}</span> {{ $rest }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="py-14 lg:py-16 bg-white">
        @php
            $formatPrice = fn (?float $price) => number_format($price ?? 0, ((int) ($price ?? 0)) == ($price ?? 0) ? 0 : 2);

            $leadLines = [
                'essential' => 'Reviews & updates the documents you already have.',
                'professional' => 'Everything in Essential',
                'advanced' => 'Everything in Essential & Professional',
                'complete' => 'Everything in Essential, Professional & Advanced',
            ];

            $disclaimers = [
                'essential' => 'Listed trainings are general in nature. Harassment prevention (general) does not substitute for state-mandated training where subject- or frequency-specific training is required. Assigning and maintaining compliance officer responsibilities remains the practice\'s responsibility at this tier.',
                'professional' => 'Listed trainings are general in nature. Harassment prevention (general) does not substitute for state-mandated training where subject- or frequency-specific training is required. Compliance officer responsibilities remain the practice\'s responsibility at this tier.',
                'advanced' => 'The Coding & Documentation Mini Audit is not conducted under attorney-client privilege. Identified overpayments must be reported and returned within 60 days under federal law, and we will recommend independent legal counsel where findings suggest material exposure. Compliance officer responsibilities remain the practice\'s, with our review and guidance.',
                'complete' => 'Scope, deliverables, and pricing at this tier are customized per practice and confirmed in a separate written services agreement. Co-sourced, fractional, or outsourced compliance officer staffing is scoped individually and does not itself create an employment relationship with Empower.',
            ];
        @endphp
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <span class="text-xs font-bold tracking-widest uppercase text-[#0b9ed0]">Pricing</span>
                <h2 class="mt-3 text-3xl font-bold text-[#0e3a61]">Choose Your Compliance Package</h2>
                <p class="mt-4 text-[#5c778d] max-w-2xl mx-auto leading-relaxed">
                    Every package is billed per billable provider, per year (or monthly), and includes annual
                    renewal.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 items-stretch">

                {{-- Essential --}}
                <div class="relative rounded-2xl border border-[#d4e5f1] bg-[#f2f8fd] p-7 flex flex-col">
                    <div class="flex items-center gap-1.5 mb-3">
                        <span class="text-xs font-bold tracking-widest uppercase text-[#5c778d]">Essential</span>
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @mouseenter="open = true" @mouseleave="open = false"
                                @click="open = !open"
                                class="flex items-center gap-1 text-[0.65rem] font-semibold uppercase tracking-wide text-[#7fb8d4] hover:text-[#087fa9] transition-colors"
                                aria-label="Package disclaimer">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>Disclaimer
                            </button>
                            <div x-show="open" x-cloak x-transition
                                class="absolute left-0 top-6 z-20 w-64 rounded-xl border border-[#d4e5f1] bg-white p-3 text-xs leading-relaxed text-[#5c778d] shadow-lg whitespace-pre-line">
                                {{ $disclaimers['essential'] }}</div>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-[#0e3a61]">${{
                        $formatPrice($packages['essential']->monthly_price ?? null) }}</div>
                    <div class="text-sm text-[#5c778d] mt-1 mb-1">/ billable provider / month</div>
                    <div class="text-xs text-[#5c778d] mb-6">${{ $formatPrice($packages['essential']->annual_price ??
                        null) }}/yr billed annually</div>
                    <p class="text-sm font-semibold text-[#173a59] mb-3">{{ $leadLines['essential'] }}</p>
                    <ul class="space-y-2.5 text-sm text-[#173a59] mb-8 grow">
                        @foreach($packages['essential']->features ?? [] as $f)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#0b9ed0]"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('portal', ['package' => 'essential']) }}"
                        class="block w-full rounded-xl bg-[#0e3a61] py-3 text-center text-sm font-semibold text-white hover:bg-[#0b2e4b] transition-colors">Select
                        Package</a>
                </div>

                {{-- Professional --}}
                <div class="relative rounded-2xl border border-[#d4e5f1] bg-[#f2f8fd] p-7 flex flex-col">
                    <div class="flex items-center gap-1.5 mb-3">
                        <span class="text-xs font-bold tracking-widest uppercase text-[#5c778d]">Professional</span>
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @mouseenter="open = true" @mouseleave="open = false"
                                @click="open = !open"
                                class="flex items-center gap-1 text-[0.65rem] font-semibold uppercase tracking-wide text-[#7fb8d4] hover:text-[#087fa9] transition-colors"
                                aria-label="Package disclaimer">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>Disclaimer
                            </button>
                            <div x-show="open" x-cloak x-transition
                                class="absolute left-0 top-6 z-20 w-64 rounded-xl border border-[#d4e5f1] bg-white p-3 text-xs leading-relaxed text-[#5c778d] shadow-lg whitespace-pre-line">
                                {{ $disclaimers['professional'] }}</div>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-[#0e3a61]">${{
                        $formatPrice($packages['professional']->monthly_price ?? null) }}</div>
                    <div class="text-sm text-[#5c778d] mt-1 mb-1">/ billable provider / month</div>
                    <div class="text-xs text-[#5c778d] mb-6">${{
                        $formatPrice($packages['professional']->annual_price ?? null) }}/yr billed annually</div>
                    <p class="text-sm font-semibold text-[#173a59] mb-3">{{ $leadLines['professional'] }}</p>
                    <ul class="space-y-2.5 text-sm text-[#173a59] mb-8 grow">
                        @foreach($packages['professional']->features ?? [] as $f)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#0b9ed0]"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('portal', ['package' => 'professional']) }}"
                        class="block w-full rounded-xl bg-[#0e3a61] py-3 text-center text-sm font-semibold text-white hover:bg-[#0b2e4b] transition-colors">Select
                        Package</a>
                </div>

                {{-- Advanced (Popular) --}}
                <div class="rounded-2xl border-2 border-[#0b9ed0] bg-[#0e3a61] p-7 flex flex-col relative">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                        <span
                            class="rounded-full bg-[#0b9ed0] px-4 py-1 text-xs font-bold text-white shadow">Popular</span>
                    </div>
                    <div class="flex items-center gap-1.5 mb-3">
                        <span class="text-xs font-bold tracking-widest uppercase text-[#8ddaf2]">Advanced</span>
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @mouseenter="open = true" @mouseleave="open = false"
                                @click="open = !open"
                                class="flex items-center gap-1 text-[0.65rem] font-semibold uppercase tracking-wide text-white/60 hover:text-white transition-colors"
                                aria-label="Package disclaimer">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>Disclaimer
                            </button>
                            <div x-show="open" x-cloak x-transition
                                class="absolute left-0 top-6 z-20 w-64 rounded-xl border border-[#d4e5f1] bg-white p-3 text-xs leading-relaxed text-[#5c778d] shadow-lg whitespace-pre-line">
                                {{ $disclaimers['advanced'] }}</div>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-white">${{
                        $formatPrice($packages['advanced']->monthly_price ?? null) }}</div>
                    <div class="text-sm text-white/60 mt-1 mb-1">/ billable provider / month</div>
                    <div class="text-xs text-white/50 mb-6">${{ $formatPrice($packages['advanced']->annual_price ??
                        null) }}/yr billed annually</div>
                    <p class="text-sm font-semibold text-white mb-3">{{ $leadLines['advanced'] }}</p>
                    <ul class="space-y-2.5 text-sm text-white/85 mb-8 grow">
                        @foreach($packages['advanced']->features ?? [] as $f)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#8ddaf2]"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('portal', ['package' => 'advanced']) }}"
                        class="block w-full rounded-xl bg-[#2299dd] py-3 text-center text-sm font-semibold text-white hover:bg-[#087fa9] transition-colors">Select
                        Package</a>
                </div>

                {{-- Complete --}}
                <div class="relative rounded-2xl border border-[#d4e5f1] bg-[#f2f8fd] p-7 flex flex-col">
                    <div class="flex items-center gap-1.5 mb-3">
                        <span class="text-xs font-bold tracking-widest uppercase text-[#5c778d]">Complete</span>
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @mouseenter="open = true" @mouseleave="open = false"
                                @click="open = !open"
                                class="flex items-center gap-1 text-[0.65rem] font-semibold uppercase tracking-wide text-[#7fb8d4] hover:text-[#087fa9] transition-colors"
                                aria-label="Package disclaimer">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>Disclaimer
                            </button>
                            <div x-show="open" x-cloak x-transition
                                class="absolute left-0 top-6 z-20 w-64 rounded-xl border border-[#d4e5f1] bg-white p-3 text-xs leading-relaxed text-[#5c778d] shadow-lg whitespace-pre-line">
                                {{ $disclaimers['complete'] }}</div>
                        </div>
                    </div>
                    <div class="text-4xl font-extrabold text-[#0e3a61]">Call</div>
                    <div class="text-sm text-[#5c778d] mt-1 mb-6">for pricing</div>
                    <p class="text-sm font-semibold text-[#173a59] mb-3">{{ $leadLines['complete'] }}</p>
                    <ul class="space-y-2.5 text-sm text-[#173a59] mb-8 grow">
                        @foreach($packages['complete']->features ?? [] as $f)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#0b9ed0]"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('contact') }}?package=complete"
                        class="block w-full rounded-xl bg-[#0e3a61] py-3 text-center text-sm font-semibold text-white hover:bg-[#0b2e4b] transition-colors">Request
                        a Quote</a>
                </div>

            </div>

            <p class="mt-6 text-xs text-[#8598ab] leading-relaxed max-w-5xl mx-auto">
                Priced per billable provider: counts physicians and non-physician practitioners billing under the
                group NPI; mid-term joiners are prorated and trued up at renewal.
                <sup>1</sup> Harassment prevention (general) does not substitute for state-mandated training where
                subject- or frequency-specific training is required.
                <sup>2</sup> Coding &amp; Documentation Mini Audit: a review that identifies a potential overpayment
                must be reported and returned within 60 days under federal law. Your program includes a defined
                escalation path, and we will recommend independent legal counsel where findings suggest material
                exposure.
            </p>

            {{-- Legal Add-on --}}
            <div
                class="mt-6 rounded-2xl border border-[#d4e5f1] bg-gradient-to-r from-[#e9f7fc] to-white p-7 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <div class="flex items-start gap-4 flex-1">
                        <span
                            class="flex-shrink-0 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#0e3a61] text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <div>
                            <span class="text-xs font-bold tracking-widest uppercase text-[#0b9ed0]">Add-on &middot;
                                Available for Any Package</span>
                            <h3 class="mt-1 font-semibold text-[#0e3a61]">Legal Review &amp; Risk Assessment, by Frier
                                Levitt (or comparable independent counsel)</h3>
                            <p class="mt-2 text-sm text-[#5c778d] leading-relaxed">Conducted at the direction of
                                independent counsel, structured with the intent that counsel's analysis be protected
                                by attorney-client privilege. Privilege is fact-specific and cannot be guaranteed.
                                Underlying records, claims data, and codes submitted are not privileged. The Advanced
                                tier's mini audit is not conducted under privilege. Includes an initial risk
                                assessment call, a coding and documentation review conducted at the direction of
                                counsel, a privileged legal analysis letter, a post-report implementation call, and
                                Business Associate Agreements in place before any work begins.
                            </p>
                            <p class="mt-2 text-xs text-[#5c778d]">Coverage varies by carrier and policy; we can
                                confirm what applies to your practice during scoping.
                            </p>
                        </div>
                    </div>
                    <div class="lg:text-right shrink-0">
                        <a href="{{ route('contact') }}?addon=legal-review"
                            class="inline-block rounded-lg bg-[#0e3a61] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0b2e4b] transition-colors">Contact
                            us about this add-on</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Process --}}
    <section id="process" class="py-14 lg:py-16 bg-[#f2f8fd]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                <div>
                    <span class="text-xs font-bold tracking-widest uppercase text-[#0b9ed0]">Process</span>
                    <h2 class="mt-3 text-3xl font-bold text-[#0e3a61]">A 5-step flow from billing to compliance
                        documents.</h2>
                    <p class="mt-4 text-[#5c778d] leading-relaxed">
                        Your portal walks each practice through a fixed sequence: billing setup, profile lock, intake
                        uploads with AI extraction, admin review, and dashboard-based delivery.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="#pricing"
                            class="inline-block rounded-xl bg-[#2299dd] px-6 py-3 text-sm font-semibold text-white hover:bg-[#087fa9] transition-colors shadow-lg">Explore
                            Packages</a>
                        <a href="{{ route('contact') }}"
                            class="inline-block rounded-xl border border-[#9ed3e9] bg-white px-6 py-3 text-sm font-semibold text-[#087fa9] hover:bg-[#eef8fd] transition-colors">Talk
                            to the team</a>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-[#d4e5f1] shadow-sm divide-y divide-[#d4e5f1]">
                    @foreach([
                    ['1', 'Billing & Activation', 'Select your package and complete billing setup to activate
                    onboarding immediately.'],
                    ['2', 'Practice Profile', 'Submit practice details and OSHA locations; core profile fields lock
                    after submission for document consistency.'],
                    ['3', 'Intake Upload', 'Upload package-required forms and handbook inputs; AI extracts structured
                    data from files for drafting.'],
                    ['4', 'Review Status', 'Your submission moves through submitted and under-review states until
                    admin approval or requested changes.'],
                    ['5', 'Dashboard & Documents', 'Access history, payments, and generated files from your
                    dashboard, with stale indicators when profile data changes.'],
                    ] as [$num, $title, $desc])
                    <div class="flex items-start gap-4 p-5">
                        <span
                            class="flex-shrink-0 inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#0b9ed0] text-xs font-bold text-white">{{
                            $num }}</span>
                        <div>
                            <h3 class="text-sm font-semibold text-[#0e3a61]">{{ $title }}</h3>
                            <p class="mt-1 text-xs text-[#5c778d] leading-relaxed">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="py-14 lg:py-16 bg-white">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <span class="text-xs font-bold tracking-widest uppercase text-[#0b9ed0]">FAQ</span>
                <h2 class="mt-3 text-3xl font-bold text-[#0e3a61]">Frequently asked questions</h2>
            </div>

            <div class="divide-y divide-[#d4e5f1] rounded-2xl border border-[#d4e5f1] bg-white">
                @foreach([
                ['What is a healthcare compliance program, and does my practice need one?', 'A compliance program is
                a documented set of written standards, training, oversight, monitoring, and reporting that helps a
                practice meet its regulatory obligations. It is required by regulation for certain entity types and
                is expected of all providers under OIG guidance; many payor participation agreements require one as
                well.'],
                ['Is Proactive Compliance built on the OIG\'s seven elements?', 'Yes. Your program is structured on
                the seven elements described in OIG guidance: written standards, oversight, training, and reporting
                channels are documented and operated with our support, while enforcement and corrective action are
                operated by your practice, with our support.'],
                ['How is Empower related to CareCloud?', 'Empower is part of CareCloud. Empower Healthcare &amp;
                Compliance Inc. delivers the compliance programs and audit support described here, and works with the
                systems you already use. Where CareCloud provides revenue cycle services, review of coding and
                billing is performed independently of the teams that deliver them.'],
                ['How much does a compliance program cost?', 'Every package is priced per billable provider, per year
                (or monthly), and includes annual renewal. Pricing for the Essential, Professional, and Advanced
                tiers is available at empowerhci.com. The Complete tier is quoted based on your practice.'],
                ['What happens if a coding or documentation audit finds a problem?', 'Identified overpayments must be
                reported and returned within 60 days under federal law. Your program includes a defined escalation
                path, and we will recommend independent legal counsel where findings suggest material exposure.'],
                ] as [$q, $a])
                <div x-data="{ open: false }" class="p-5">
                    <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between gap-4 text-left">
                        <span class="text-sm font-semibold text-[#0e3a61]">{{ $q }}</span>
                        <svg :class="open ? 'rotate-180' : ''"
                            class="h-4 w-4 shrink-0 text-[#0b9ed0] transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <p x-show="open" x-cloak x-transition class="mt-3 text-sm text-[#5c778d] leading-relaxed">{{ $a
                        }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section id="contact" class="py-14 lg:py-16 bg-gradient-to-br from-[#0b2e4b] via-[#0e3a61] to-[#16638e]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-bold tracking-widest uppercase text-[#8ddaf2]">Ready to get started?</span>
                <h2 class="mt-3 text-3xl font-bold text-white">Proactive Compliance by Empower.</h2>
                <p class="mt-4 text-white/70 leading-relaxed">
                    Select your package and begin the 5-step onboarding flow today, or contact us to discuss the
                    right tier.
                </p>
                <a href="{{ route('contact') }}"
                    class="mt-8 inline-block rounded-xl bg-[#2299dd] px-8 py-3.5 text-sm font-semibold text-white hover:bg-[#087fa9] transition-colors shadow-lg">Contact
                    Us</a>
            </div>
        </div>
    </section>

    <div x-data="{ show: false, message: '' }"
        x-on:toast.window="message = $event.detail.message; show = true; clearTimeout(hideTimer); hideTimer = setTimeout(() => show = false, 3000)"
        x-init="hideTimer = null" x-show="show" x-transition x-cloak class="fixed bottom-6 right-6 z-[100]">
        <div
            class="flex items-center gap-2 rounded-xl bg-[#0e3a61] text-white pl-4 pr-5 py-3 shadow-[0_18px_50px_rgba(10,32,55,0.25)]">
            <span class="text-[#8ddaf2] font-bold">&#10003;</span>
            <span class="text-sm font-semibold" x-text="message"></span>
        </div>
    </div>
</x-layouts.marketing>
