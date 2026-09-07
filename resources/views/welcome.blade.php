<x-layouts.marketing title="Proactive Compliance by Empower — Healthcare Compliance Portal" :on-home-page="true">

    {{-- Hero --}}
    <section id="home" class="py-16 lg:py-20"
        style="background: radial-gradient(circle at 84% 18%, rgba(11, 158, 208, 0.36), transparent 34%), radial-gradient(circle at 8% 0%, rgba(34, 153, 221, 0.20), transparent 30%), linear-gradient(115deg, #f2f8fd 0%, #dff1fb 44%, #c7e7f6 100%);">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="w-full">
                <span
                    class="inline-block rounded-full border border-[#0b9ed0]/30 bg-[#e9f7fc] px-4 py-1.5 text-xs font-semibold text-[#087fa9] tracking-wide mb-5">
                    Proactive Compliance &middot; In Collaboration with Frier Levitt
                </span>
                <h1 class="text-3xl lg:text-4xl font-bold text-[#0e3a61] mb-4 leading-tight">
                    Proactive Compliance by Empower
                </h1>
                <p class="w-full text-base lg:text-lg text-[#5c778d] mb-6 leading-relaxed">
                    A guided 5-step compliance onboarding flow for healthcare practices: simulated payment, profile
                    confirmation, intake upload, admin review, and document delivery in your portal dashboard.
                </p>
                <div class="flex flex-wrap gap-3 mb-8">
                    <a href="#pricing"
                        class="rounded-lg bg-[#2299dd] px-6 py-3 text-sm font-semibold text-white hover:bg-[#087fa9] transition-colors shadow-lg">Explore
                        Packages</a>
                    <a href="#services"
                        class="rounded-lg border border-[#9ed3e9] bg-white px-6 py-3 text-sm font-semibold text-[#087fa9] hover:bg-[#eef8fd] transition-colors">View
                        Services</a>
                </div>
                <div class="flex flex-wrap gap-6 text-sm text-[#5c778d]">
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#0b9ed0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Full Launch: September 8, 2026
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#0b9ed0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        5-step guided portal flow
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#0b9ed0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        AI-assisted intake extraction
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats strip --}}
    <section class="bg-white border-b border-[#d4e5f1]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-px bg-[#d4e5f1]">
                @foreach([
                ['HIPAA', 'Privacy and security focus'],
                ['Ethics', 'Program and training support'],
                ['Audit', 'Risk visibility at a glance'],
                ['Managed', 'End-to-end compliance options'],
                ] as [$title, $sub])
                <div class="bg-white py-7 px-6 text-center">
                    <div class="text-xl font-bold text-[#0e3a61] mb-1">{{ $title }}</div>
                    <div class="text-xs text-[#5c778d]">{{ $sub }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section id="services" class="py-14 lg:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <span class="text-xs font-bold tracking-widest uppercase text-[#0b9ed0]">Services</span>
                <h2 class="mt-3 text-3xl font-bold text-[#0e3a61]">Built for your full compliance lifecycle.</h2>
                <p class="mt-4 text-[#5c778d] max-w-2xl mx-auto leading-relaxed">
                    Start with package selection and portal onboarding, then move through profile capture, intake
                    upload, review, and finalized document delivery with ongoing compliance support.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach([
                ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0
                01.293.707V19a2 2 0 01-2 2z', 'Compliance & Ethics Program', 'Development of a written compliance
                program covering the seven core elements per OIG guidance, tailored to the practice.'],
                ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                'HIPAA Privacy & Security Policies', 'Comprehensive HIPAA Privacy and Security policies and procedures
                customized to the practice\'s operations.'],
                ['M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5
                18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477
                18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'Training & Manual Support', 'Access to Empower\'s
                compliance training platform, plus employee manual review, ongoing updates, or full creation depending
                on your package.'],
                ['M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'Ongoing Monitoring', 'Exclusions screening against OIG,
                SAM, and state databases, workplace safety reviews for OSHA alignment, and a Security Risk Assessment
                (SRA) to catch issues early.'],
                ['M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0
                01.293.707V19a2 2 0 01-2 2z', 'Audit Reporting', 'Coding & Documentation Mini Audits (10
                encounters/provider) compiled into an Executive Summary Report for leadership.'],
                ['M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42
                3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42
                0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0
                01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0
                013.138-3.138z', 'Managed Compliance', 'From compliance department creation & oversight to Empower, by
                CareCloud, operating as your fully managed compliance department at the Complete tier.'],
                ] as [$icon, $title, $desc])
                <div
                    class="rounded-2xl bg-white border border-[#d4e5f1] p-7 shadow-sm hover:shadow-md transition-shadow">
                    <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#e9f7fc]">
                        <svg class="h-5 w-5 text-[#0b9ed0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-[#0e3a61] mb-2">{{ $title }}</h3>
                    <p class="text-sm text-[#5c778d] leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA (repeated above Pricing) --}}
    <section class="py-14 lg:py-16 bg-gradient-to-br from-[#0b2e4b] via-[#0e3a61] to-[#16638e]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-bold tracking-widest uppercase text-[#8ddaf2]">Ready to get started?</span>
                <h2 class="mt-3 text-3xl font-bold text-white">Proactive Compliance by Empower launches September 8,
                    2026.</h2>
                <p class="mt-4 text-white/70 leading-relaxed">
                    Select your package and begin the 5-step onboarding flow today, or contact us to map the right tier
                    and legal review options for your practice.
                </p>
                <a href="{{ route('contact') }}"
                    class="mt-8 inline-block rounded-xl bg-[#2299dd] px-8 py-3.5 text-sm font-semibold text-white hover:bg-[#087fa9] transition-colors shadow-lg">Contact
                    Us</a>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="py-14 lg:py-16 bg-white">
        @php
            $formatPrice = fn (?float $price) => number_format($price ?? 0, ((int) ($price ?? 0)) == ($price ?? 0) ? 0 : 2);
        @endphp
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <span class="text-xs font-bold tracking-widest uppercase text-[#0b9ed0]">Pricing</span>
                <h2 class="mt-3 text-3xl font-bold text-[#0e3a61]">Choose Your Compliance Package</h2>
                <p class="mt-4 text-[#5c778d] max-w-2xl mx-auto leading-relaxed">
                    Every package is billed per billable provider, per year (or monthly), and includes annual renewal.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 items-stretch">

                {{-- Essential --}}
                <div class="relative rounded-2xl border border-[#d4e5f1] bg-[#f2f8fd] p-7 flex flex-col">
                    <div class="flex items-center gap-1.5 mb-3">
                        <span class="text-xs font-bold tracking-widest uppercase text-[#5c778d]">Essential</span>
                        @if($packages['essential']->description ?? null)
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @mouseenter="open = true" @mouseleave="open = false"
                                @click="open = !open"
                                class="flex h-5 w-5 items-center justify-center text-[#7fb8d4] hover:text-[#087fa9] transition-colors"
                                aria-label="Package description">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition
                                class="absolute left-0 top-6 z-20 w-64 rounded-xl border border-[#d4e5f1] bg-white p-3 text-xs leading-relaxed text-[#5c778d] shadow-lg whitespace-pre-line">
                                {{ $packages['essential']->description }}</div>
                        </div>
                        @endif
                    </div>
                    <div class="text-4xl font-extrabold text-[#0e3a61]">${{
                        $formatPrice($packages['essential']->annual_price ?? null) }}</div>
                    <div class="text-sm text-[#5c778d] mt-1 mb-1">/ billable provider / year</div>
                    <div class="text-xs text-[#5c778d] mb-6">${{ $formatPrice($packages['essential']->monthly_price ??
                        null) }}/mo billed monthly</div>
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
                        @if($packages['professional']->description ?? null)
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @mouseenter="open = true" @mouseleave="open = false"
                                @click="open = !open"
                                class="flex h-5 w-5 items-center justify-center text-[#7fb8d4] hover:text-[#087fa9] transition-colors"
                                aria-label="Package description">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition
                                class="absolute left-0 top-6 z-20 w-64 rounded-xl border border-[#d4e5f1] bg-white p-3 text-xs leading-relaxed text-[#5c778d] shadow-lg whitespace-pre-line">
                                {{ $packages['professional']->description }}</div>
                        </div>
                        @endif
                    </div>
                    <div class="text-4xl font-extrabold text-[#0e3a61]">${{
                        $formatPrice($packages['professional']->annual_price ?? null) }}</div>
                    <div class="text-sm text-[#5c778d] mt-1 mb-1">/ billable provider / year</div>
                    <div class="text-xs text-[#5c778d] mb-6">${{ $formatPrice($packages['professional']->monthly_price
                        ?? null) }}/mo billed monthly</div>
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
                        @if($packages['advanced']->description ?? null)
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @mouseenter="open = true" @mouseleave="open = false"
                                @click="open = !open"
                                class="flex h-5 w-5 items-center justify-center text-white/60 hover:text-white transition-colors"
                                aria-label="Package description">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition
                                class="absolute left-0 top-6 z-20 w-64 rounded-xl border border-[#d4e5f1] bg-white p-3 text-xs leading-relaxed text-[#5c778d] shadow-lg whitespace-pre-line">
                                {{ $packages['advanced']->description }}</div>
                        </div>
                        @endif
                    </div>
                    <div class="text-4xl font-extrabold text-white">${{
                        $formatPrice($packages['advanced']->annual_price ?? null) }}</div>
                    <div class="text-sm text-white/60 mt-1 mb-1">/ billable provider / year</div>
                    <div class="text-xs text-white/50 mb-6">${{ $formatPrice($packages['advanced']->monthly_price ??
                        null) }}/mo billed monthly</div>
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
                        @if($packages['complete']->description ?? null)
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @mouseenter="open = true" @mouseleave="open = false"
                                @click="open = !open"
                                class="flex h-5 w-5 items-center justify-center text-[#7fb8d4] hover:text-[#087fa9] transition-colors"
                                aria-label="Package description">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition
                                class="absolute left-0 top-6 z-20 w-64 rounded-xl border border-[#d4e5f1] bg-white p-3 text-xs leading-relaxed text-[#5c778d] shadow-lg whitespace-pre-line">
                                {{ $packages['complete']->description }}</div>
                        </div>
                        @endif
                    </div>
                    <div class="text-4xl font-extrabold text-[#0e3a61]">Call</div>
                    <div class="text-sm text-[#5c778d] mt-1 mb-6">for pricing</div>
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
                                Levitt</h3>
                            <p class="mt-2 text-sm text-[#5c778d] leading-relaxed">Delivered under a Kovel Expert
                                Engagement, so findings are shielded by attorney-client privilege — unlike the Advanced
                                tier's coding audit. Includes an initial risk assessment call, a Kovel-protected coding
                                &amp; documentation review, a privileged legal analysis letter, a post-report
                                implementation call, and Business Associate Agreements in place before any work begins.
                            </p>
                            <p class="mt-2 text-xs text-[#5c778d]">Empower and Frier Levitt are credentialed with most
                                major malpractice insurance carriers nationally — your practice may already be covered.
                            </p>
                        </div>
                    </div>
                    <div class="lg:text-right shrink-0">
                        <div class="text-3xl font-extrabold text-[#0e3a61]">$2,500</div>
                        <div class="text-xs text-[#5c778d] mt-1">flat-fee retainer / practice &middot; Kovel &middot;
                            Attorney-Client Privilege</div>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach(['I|Risk Assessment & Strategy Call', 'II|Coding & Documentation Review', 'III|Legal
                    Analysis & Summary Letter', 'IV|Post-Report Implementation Call', 'V|Business Associate Agreements']
                    as $step)
                    @php [$num, $label] = explode('|', $step) @endphp
                    <div class="rounded-xl border border-[#d4e5f1] bg-[#f9fcff] p-3.5">
                        <span
                            class="block text-[0.65rem] font-extrabold tracking-wider uppercase text-[#0b9ed0] mb-1">{{
                            $num }}</span>
                        <span class="text-xs font-semibold text-[#173a59] leading-snug">{{ $label }}</span>
                    </div>
                    @endforeach
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
                    <h2 class="mt-3 text-3xl font-bold text-[#0e3a61]">A 5-step flow from payment to compliance
                        documents.</h2>
                    <p class="mt-4 text-[#5c778d] leading-relaxed">
                        Your portal walks each practice through a fixed sequence: simulated payment, profile lock,
                        intake uploads with AI extraction, admin review, and dashboard-based delivery.
                    </p>
                    <a href="{{ route('contact') }}"
                        class="mt-8 inline-block rounded-xl bg-[#0e3a61] px-6 py-3 text-sm font-semibold text-white hover:bg-[#0b2e4b] transition-colors">Talk
                        to the team</a>
                </div>

                <div class="rounded-2xl bg-white border border-[#d4e5f1] shadow-sm divide-y divide-[#d4e5f1]">
                    @foreach([
                    ['1', 'Payment (simulated)', 'Select your package and complete simulated payment to activate
                    onboarding immediately.'],
                    ['2', 'Practice Profile', 'Submit practice details and OSHA locations; core profile fields lock
                    after submission for document consistency.'],
                    ['3', 'Intake Upload', 'Upload package-required forms and handbook inputs; AI extracts structured
                    data from files for drafting.'],
                    ['4', 'Review Status', 'Your submission moves through submitted and under-review states until admin
                    approval or requested changes.'],
                    ['5', 'Dashboard & Documents', 'Access history, payments, and generated files from your dashboard,
                    with stale indicators when profile data changes.'],
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

    {{-- CTA --}}
    <section id="contact" class="py-14 lg:py-16 bg-gradient-to-br from-[#0b2e4b] via-[#0e3a61] to-[#16638e]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-bold tracking-widest uppercase text-[#8ddaf2]">Ready to get started?</span>
                <h2 class="mt-3 text-3xl font-bold text-white">Proactive Compliance by Empower launches September 8,
                    2026.</h2>
                <p class="mt-4 text-white/70 leading-relaxed">
                    Select your package and begin the 5-step onboarding flow today, or contact us to map the right tier
                    and legal review options for your practice.
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