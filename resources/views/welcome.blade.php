<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proactive Compliance by Empower — Healthcare Compliance Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#f4f7fb] text-[#173045] antialiased font-sans">

{{-- Sticky Nav --}}
<nav class="sticky top-0 z-50 bg-[#12304f]/96 backdrop-blur border-b border-white/8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <a href="#home" class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1.5">
                    <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px]" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-[#12304f] text-sm\'>EMPOWER</span>'">
                </span>
                <span class="hidden sm:block text-[0.6rem] font-extrabold tracking-widest uppercase text-[#9fb4ce]">Marketplace</span>
            </a>

            <div class="hidden md:flex items-center gap-7">
                <a href="#services" class="text-sm font-medium text-white/78 hover:text-white transition-colors">Services</a>
                <a href="#process" class="text-sm font-medium text-white/78 hover:text-white transition-colors">Process</a>
                <a href="#pricing" class="text-sm font-medium text-white/78 hover:text-white transition-colors">Pricing</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium text-white/78 hover:text-white transition-colors">Contact</a>
            </div>

            <div class="flex items-center gap-2">
                <livewire:cart-badge />
                @auth
                    <a href="{{ route('portal') }}" class="rounded-lg bg-[#76c8c0] px-4 py-2 text-sm font-semibold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors">My Portal</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-white/78 hover:text-white transition-colors">Log in</a>
                    <a href="#pricing" class="rounded-lg bg-[#76c8c0] px-4 py-2 text-sm font-semibold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors">Get Started</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<main>

    {{-- Hero --}}
    <section id="home" class="bg-gradient-to-br from-[#0a2037] via-[#12304f] to-[#1a4a70] py-24 lg:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="inline-block rounded-full border border-[#76c8c0]/40 bg-[#76c8c0]/10 px-4 py-1.5 text-xs font-semibold text-[#76c8c0] tracking-wide mb-5">
                    Proactive Compliance &middot; In Collaboration with Frier Levitt
                </span>
                <h1 class="text-4xl lg:text-5xl font-bold text-white mb-5 leading-tight">
                    Proactive Compliance<br>by Empower
                </h1>
                <p class="text-lg text-white/60 mb-8 leading-relaxed">
                    A fully managed compliance program for healthcare practices — HIPAA policies, a Compliance &amp; Ethics Program, staff training, and ongoing monitoring, with an optional Kovel-protected legal review from Frier Levitt.
                </p>
                <div class="flex flex-wrap gap-3 mb-10">
                    <a href="#pricing" class="rounded-lg bg-[#76c8c0] px-6 py-3 text-sm font-semibold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors shadow-lg">Explore Packages</a>
                    <a href="#services" class="rounded-lg border border-white/25 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition-colors">View Services</a>
                </div>
                <div class="flex flex-wrap gap-6 text-sm text-white/50">
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#76c8c0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Full Launch: September 8, 2026
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#76c8c0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        HIPAA-ready workflows
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#76c8c0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Built for multi-provider teams
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats strip --}}
    <section class="bg-white border-b border-[#dbe4ee]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-[#dbe4ee]">
                @foreach([
                    ['HIPAA', 'Privacy and security focus'],
                    ['Ethics', 'Program and training support'],
                    ['Audit', 'Risk visibility at a glance'],
                    ['Managed', 'End-to-end compliance options'],
                ] as [$title, $sub])
                <div class="py-7 px-6 text-center">
                    <div class="text-xl font-bold text-[#12304f] mb-1">{{ $title }}</div>
                    <div class="text-xs text-[#5d6e7f]">{{ $sub }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section id="services" class="py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="text-xs font-bold tracking-widest uppercase text-[#76c8c0]">Services</span>
                <h2 class="mt-3 text-3xl font-bold text-[#12304f]">Everything organized into a clear compliance offering.</h2>
                <p class="mt-4 text-[#5d6e7f] max-w-2xl mx-auto leading-relaxed">
                    From written policies and staff training to ongoing monitoring and an optional privileged legal review, every core element of an effective compliance program is covered.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach([
                    ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'Compliance & Ethics Program', 'Development of a written compliance program covering the seven core elements per OIG guidance, tailored to the practice.'],
                    ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'HIPAA Privacy & Security Policies', 'Comprehensive HIPAA Privacy and Security policies and procedures customized to the practice\'s operations.'],
                    ['M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'Training & Manual Support', 'Access to Empower\'s compliance training platform, plus employee manual review, ongoing updates, or full creation depending on your package.'],
                    ['M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'Ongoing Monitoring', 'Exclusions screening against OIG, SAM, and state databases, workplace safety reviews for OSHA alignment, and a Security Risk Assessment (SRA) to catch issues early.'],
                    ['M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'Audit Reporting', 'Coding & Documentation Mini Audits (10 encounters/provider) compiled into an Executive Summary Report for leadership.'],
                    ['M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'Managed Compliance', 'From compliance department creation & oversight to Empower, by CareCloud, operating as your fully managed compliance department at the Complete tier.'],
                ] as [$icon, $title, $desc])
                <div class="rounded-2xl bg-white border border-[#dbe4ee] p-7 shadow-sm hover:shadow-md transition-shadow">
                    <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#f4f7fb]">
                        <svg class="h-5 w-5 text-[#12304f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-[#12304f] mb-2">{{ $title }}</h3>
                    <p class="text-sm text-[#5d6e7f] leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="py-20 lg:py-24 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="text-xs font-bold tracking-widest uppercase text-[#76c8c0]">Pricing</span>
                <h2 class="mt-3 text-3xl font-bold text-[#12304f]">Choose Your Compliance Package</h2>
                <p class="mt-4 text-[#5d6e7f] max-w-2xl mx-auto leading-relaxed">
                    Every package is billed per billable provider, per year (or monthly), and includes annual renewal.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 items-stretch">

                {{-- Essential --}}
                <div class="rounded-2xl border border-[#dbe4ee] bg-[#f4f7fb] p-7 flex flex-col">
                    <div class="text-xs font-bold tracking-widest uppercase text-[#5d6e7f] mb-3">Essential</div>
                    <div class="text-4xl font-extrabold text-[#12304f]">${{ number_format($packages['essential']->annual_price ?? 0) }}</div>
                    <div class="text-sm text-[#5d6e7f] mt-1 mb-1">/ billable provider / year</div>
                    <div class="text-xs text-[#5d6e7f] mb-6">${{ number_format($packages['essential']->monthly_price ?? 0) }}/mo billed monthly</div>
                    <ul class="space-y-2.5 text-sm text-[#173045] mb-8 grow">
                        @foreach(['Compliance & Ethics Program', 'HIPAA Policies', 'Training Platform', 'Employee Manual Review'] as $f)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#76c8c0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <livewire:add-to-cart-button :packageId="$packages['essential']->id ?? 0" variant="navy" :key="'atc-essential'" />
                </div>

                {{-- Professional --}}
                <div class="rounded-2xl border border-[#dbe4ee] bg-[#f4f7fb] p-7 flex flex-col">
                    <div class="text-xs font-bold tracking-widest uppercase text-[#5d6e7f] mb-3">Professional</div>
                    <div class="text-4xl font-extrabold text-[#12304f]">${{ number_format($packages['professional']->annual_price ?? 0) }}</div>
                    <div class="text-sm text-[#5d6e7f] mt-1 mb-1">/ billable provider / year</div>
                    <div class="text-xs text-[#5d6e7f] mb-6">${{ number_format($packages['professional']->monthly_price ?? 0) }}/mo billed monthly</div>
                    <ul class="space-y-2.5 text-sm text-[#173045] mb-8 grow">
                        @foreach(['Everything in Essential', 'Exclusions Screening', 'Compliance Hotline', 'Manuals & Manual Updates', 'Safety Review', 'Quarterly Compliance Meeting', 'Employee Manual Updates'] as $f)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#76c8c0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <livewire:add-to-cart-button :packageId="$packages['professional']->id ?? 0" variant="navy" :key="'atc-professional'" />
                </div>

                {{-- Advanced (Popular) --}}
                <div class="rounded-2xl border-2 border-[#76c8c0] bg-[#12304f] p-7 flex flex-col relative">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                        <span class="rounded-full bg-[#76c8c0] px-4 py-1 text-xs font-bold text-[#0a2037] shadow">Popular</span>
                    </div>
                    <div class="text-xs font-bold tracking-widest uppercase text-[#76c8c0] mb-3">Advanced</div>
                    <div class="text-4xl font-extrabold text-white">${{ number_format($packages['advanced']->annual_price ?? 0) }}</div>
                    <div class="text-sm text-white/60 mt-1 mb-1">/ billable provider / year</div>
                    <div class="text-xs text-white/50 mb-6">${{ number_format($packages['advanced']->monthly_price ?? 0) }}/mo billed monthly</div>
                    <ul class="space-y-2.5 text-sm text-white/85 mb-8 grow">
                        @foreach(['Everything in Essential & Professional', 'Coding & Documentation Mini Audit (10 encounters/provider)', 'Security Risk Assessment (SRA)', 'Creation & Oversight of Compliance Department', 'Monthly Compliance Meeting', 'Employee Manual Creation'] as $f)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#76c8c0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <livewire:add-to-cart-button :packageId="$packages['advanced']->id ?? 0" variant="accent" :key="'atc-advanced'" />
                </div>

                {{-- Complete --}}
                <div class="rounded-2xl border border-[#dbe4ee] bg-[#f4f7fb] p-7 flex flex-col">
                    <div class="text-xs font-bold tracking-widest uppercase text-[#5d6e7f] mb-3">Complete</div>
                    <div class="text-4xl font-extrabold text-[#12304f]">Call</div>
                    <div class="text-sm text-[#5d6e7f] mt-1 mb-6">for pricing</div>
                    <ul class="space-y-2.5 text-sm text-[#173045] mb-8 grow">
                        @foreach(['Everything in Essential, Professional & Advanced', 'Empower, by CareCloud operates as your fully operational compliance department', 'End-to-end ownership & oversight of your compliance program'] as $f)
                        <li class="flex items-start gap-2"><svg class="h-4 w-4 mt-0.5 shrink-0 text-[#76c8c0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('contact') }}?package=complete" class="block w-full rounded-xl bg-[#12304f] py-3 text-center text-sm font-semibold text-white hover:bg-[#0a2037] transition-colors">Request a Quote</a>
                </div>

            </div>

            {{-- Legal Add-on --}}
            <div class="mt-8 rounded-2xl border border-[#dbe4ee] bg-gradient-to-r from-[#76c8c0]/12 to-white p-7 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <div class="flex items-start gap-4 flex-1">
                        <span class="flex-shrink-0 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#12304f] text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <div>
                            <span class="text-xs font-bold tracking-widest uppercase text-[#76c8c0]">Add-on &middot; Available for Any Package</span>
                            <h3 class="mt-1 font-semibold text-[#12304f]">Legal Review &amp; Risk Assessment, by Frier Levitt</h3>
                            <p class="mt-2 text-sm text-[#5d6e7f] leading-relaxed">Delivered under a Kovel Expert Engagement, so findings are shielded by attorney-client privilege — unlike the Advanced tier's coding audit. Includes an initial risk assessment call, a Kovel-protected coding &amp; documentation review, a privileged legal analysis letter, a post-report implementation call, and Business Associate Agreements in place before any work begins.</p>
                            <p class="mt-2 text-xs text-[#5d6e7f]">Empower and Frier Levitt are credentialed with most major malpractice insurance carriers nationally — your practice may already be covered.</p>
                        </div>
                    </div>
                    <div class="lg:text-right shrink-0">
                        <div class="text-3xl font-extrabold text-[#12304f]">$2,500</div>
                        <div class="text-xs text-[#5d6e7f] mt-1">flat-fee retainer / practice &middot; Kovel &middot; Attorney-Client Privilege</div>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach(['I|Risk Assessment & Strategy Call', 'II|Coding & Documentation Review', 'III|Legal Analysis & Summary Letter', 'IV|Post-Report Implementation Call', 'V|Business Associate Agreements'] as $step)
                    @php [$num, $label] = explode('|', $step) @endphp
                    <div class="rounded-xl border border-[#dbe4ee] bg-[#f8fbfd] p-3.5">
                        <span class="block text-[0.65rem] font-extrabold tracking-wider uppercase text-[#5bb2aa] mb-1">{{ $num }}</span>
                        <span class="text-xs font-semibold text-[#173045] leading-snug">{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Process --}}
    <section id="process" class="py-20 lg:py-24 bg-[#f4f7fb]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div>
                    <span class="text-xs font-bold tracking-widest uppercase text-[#76c8c0]">Process</span>
                    <h2 class="mt-3 text-3xl font-bold text-[#12304f]">A simple, guided onboarding flow.</h2>
                    <p class="mt-4 text-[#5d6e7f] leading-relaxed">
                        Pay for your package, complete a short practice intake, and Empower verifies everything before your documents are generated — accurate practice details in, accurate compliance documents out.
                    </p>
                    <a href="{{ route('contact') }}" class="mt-8 inline-block rounded-xl bg-[#12304f] px-6 py-3 text-sm font-semibold text-white hover:bg-[#0a2037] transition-colors">Talk to the team</a>
                </div>

                <div class="rounded-2xl bg-white border border-[#dbe4ee] shadow-sm divide-y divide-[#dbe4ee]">
                    @foreach([
                        ['1', 'Create your account & pay', 'Select a package, sign up, and complete payment — no waiting on a review before you get started.'],
                        ['2', 'Complete your practice intake', 'Confirm your key practice details and download any additional forms required for your package.'],
                        ['3', 'Upload & confirm your details', 'Upload your completed forms and lock in your practice name, logo, address, and provider count.'],
                        ['4', 'Empower reviews your submission', 'Our compliance team verifies your details before your documents are generated.'],
                        ['5', 'Manage everything from your dashboard', 'Generate, preview, and download every document in your package — add more packages or OSHA locations anytime.'],
                    ] as [$num, $title, $desc])
                    <div class="flex items-start gap-4 p-5">
                        <span class="flex-shrink-0 inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#12304f] text-xs font-bold text-white">{{ $num }}</span>
                        <div>
                            <h3 class="text-sm font-semibold text-[#12304f]">{{ $title }}</h3>
                            <p class="mt-1 text-xs text-[#5d6e7f] leading-relaxed">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section id="contact" class="py-20 lg:py-24 bg-gradient-to-br from-[#0a2037] via-[#12304f] to-[#1a4a70]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-bold tracking-widest uppercase text-[#76c8c0]">Ready to get started?</span>
                <h2 class="mt-3 text-3xl font-bold text-white">Proactive Compliance by Empower launches September 8, 2026.</h2>
                <p class="mt-4 text-white/60 leading-relaxed">
                    Select a package to begin onboarding today, or reach out to talk through which compliance tier — or the Frier Levitt legal review add-on — is the right fit for your practice.
                </p>
                <a href="{{ route('contact') }}" class="mt-8 inline-block rounded-xl bg-[#76c8c0] px-8 py-3.5 text-sm font-semibold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors shadow-lg">Contact Us</a>
            </div>
        </div>
    </section>

</main>

<footer class="bg-[#0a2037] py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
        <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1.5">
            <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px]" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-[#12304f] text-sm\'>EMPOWER</span>'">
        </span>
        <p class="text-xs text-white/40 text-center">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
    </div>
</footer>

<div
    x-data="{ show: false, message: '' }"
    x-on:toast.window="message = $event.detail.message; show = true; clearTimeout(hideTimer); hideTimer = setTimeout(() => show = false, 3000)"
    x-init="hideTimer = null"
    x-show="show"
    x-transition
    x-cloak
    class="fixed bottom-6 right-6 z-[100]"
>
    <div class="flex items-center gap-2 rounded-xl bg-[#12304f] text-white pl-4 pr-5 py-3 shadow-[0_18px_50px_rgba(10,32,55,0.25)]">
        <span class="text-[#76c8c0] font-bold">&#10003;</span>
        <span class="text-sm font-semibold" x-text="message"></span>
    </div>
</div>

@livewireScripts
</body>
</html>
