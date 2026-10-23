<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }} — {{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .receipt-card {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50 font-sans antialiased py-10 print:py-0">

    <div class="no-print mx-auto max-w-2xl px-4 mb-4 flex justify-end">
        <button onclick="window.print()"
            class="inline-flex items-center gap-2 rounded-lg bg-[#12304f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0a2037] transition-colors">
            Print / Save as PDF
        </button>
    </div>

    <div
        class="receipt-card mx-auto max-w-2xl bg-white border border-gray-200 rounded-2xl shadow-lg p-10 print:p-0 print:max-w-none print:rounded-none">

        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto"
            onerror="this.style.display='none'">

        <div class="mt-4 bg-[#1a9bd7] px-8 py-7 print:rounded-none">
            <h1 class="text-3xl font-extrabold tracking-wide text-white uppercase">Order Form</h1>

            <div class="mt-6 flex items-start justify-between">
                <div>
                    <p class="text-sm font-bold text-white">{{ $practice?->name ?: $order->user->name }}</p>
                    <p class="mt-1 text-sm text-white/90">{{ $order->user->email }}</p>
                </div>
                <div class="text-right text-sm text-white/90 leading-relaxed">
                    <p>Order #: {{ $order->id }}</p>
                    <p class="mt-1">Payment Date: {{ $order->paid_at?->format('F j, Y') ?? '—' }}</p>
                    @if($order->next_bill_date)
                    <p class="mt-1">Next Renewal: {{ $order->next_bill_date->format('F j, Y') }}</p>
                    @endif
                    <p class="mt-1">Receipt #: {{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</p>
                    <p class="mt-1">Created By: Admin</p>
                </div>
            </div>
        </div>

        <div class="mt-3 border border-gray-300 p-5">
            <p class="text-sm font-bold text-[#12304f]">Order Details</p>

            <div class="mt-3 border border-gray-200">
                <div class="grid grid-cols-2 gap-x-4 px-4 py-2.5 border-b border-gray-200">
                    <span class="text-sm text-gray-600">Package:</span>
                    <span class="text-sm text-[#1a6fb0]">{{ $order->package?->name ?? 'Compliance Package' }}</span>
                </div>
                @if($practice?->address)
                <div class="grid grid-cols-2 gap-x-4 px-4 py-2.5 border-b border-gray-200">
                    <span class="text-sm text-gray-600">Practice Address:</span>
                    <span class="text-sm text-[#1a6fb0]">{{ $practice->address }}</span>
                </div>
                @endif
                <div class="grid grid-cols-2 gap-x-4 px-4 py-2.5 border-b border-gray-200">
                    <span class="text-sm text-gray-600">Billing Cycle:</span>
                    <span class="text-sm text-[#1a6fb0]">{{ ($order->billing_cycle ??
                        \App\Enums\BillingCycle::Annual)->label() }}</span>
                </div>
                <div class="grid grid-cols-2 gap-x-4 px-4 py-2.5">
                    <span class="text-sm text-gray-600">Payment Status:</span>
                    <span class="text-sm font-bold text-[#0f7a4f]">PAID &middot; SIMULATED</span>
                </div>
            </div>
        </div>

        <p class="mt-4 mb-3 text-base font-bold text-[#12304f]">Products &amp; Services</p>

        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b-2 border-[#1a9bd7] text-left text-sm">
                    <th class="py-2 font-bold text-[#173045]">Item</th>
                    <th class="py-2 font-bold text-[#173045] text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-[#1a9bd7]">
                    <td class="py-3 text-sm text-gray-700">{{ $order->package?->name ?? 'Compliance Package' }} ({{
                        ($order->billing_cycle ?? \App\Enums\BillingCycle::Annual)->label() }})</td>
                    <td class="py-3 text-sm text-gray-700 text-right">${{ number_format((float) ($order->original_price
                        ?? $order->amount_paid), 2) }}</td>
                </tr>
                @if($order->discount_code)
                <tr class="border-b border-[#1a9bd7]">
                    <td class="py-3 text-sm text-gray-700">Discount ({{ $order->discount_code }})</td>
                    <td class="py-3 text-sm text-gray-700 text-right">-${{ number_format((float)
                        $order->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="py-3 text-base font-bold text-[#12304f]">Total Paid</td>
                    <td class="py-3 text-base font-bold text-[#12304f] text-right">${{ number_format((float)
                        $order->amount_paid, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-8">
            <p class="text-sm font-bold text-gray-800">Terms</p>
            <p class="mt-2 text-sm leading-relaxed">If applicable and quoted above, fees for Professional Services
                and/or Integration Services are due
                immediately and prior to CareCloud commencing any work. If applicable, Lab interface fees, if quoted at
                $0
                on this Order Form are subject to approval by Client's lab(s). If Client's lab(s) do not approve
                Client's
                interface(s), Client will have the option to cover the cost of the Lab Interface or proceed without a
                Lab
                Interface. Any products, work or services beyond the scope of any Overview are not included in this
                Order
                Form and will be quoted on a separate SOW or addendum.</p>

            <p class="mt-4 text-sm leading-relaxed">By signing this Order Form, Client agrees to pay the fees as quoted
                above for the period of the Initial Term
                and, as applicable, any Renewal Term. This Order Form is governed by CareCloud Inc.'s Master Service
                Agreement ("MSA") and Business Associate Agreement ("BAA") found at
                [<a href="https://carecloud.app.box.com/v/carecloudmsa314" target="_blank" rel="noopener noreferrer"
                    class="underline">https://carecloud.app.box.com/v/carecloudmsa314</a>] and the Product Specific
                Terms ("PST") found at
                [<a href="https://carecloud.app.box.com/v/productterms" target="_blank" rel="noopener noreferrer"
                    class="underline">https://carecloud.app.box.com/v/productterms</a>]. The MSA, BAA, and PST are
                incorporated by reference and
                are made a part hereof. This Order Form may be superseded by a subsequent order form executed by Client
                and CareCloud.</p>
        </div>

        <div class="mt-6">
            <p class="text-lg font-extrabold text-[#12304f]">Questions? Contact me</p>
            <p class="mt-2 text-sm text-gray-700">
                <a href="mailto:support@empowerhci.com" class="text-[#1a6fb0]">support@empowerhci.com</a>
            </p>
        </div>

        <div class="mt-8 border-t border-gray-200 pt-6 text-center text-xs text-gray-500 leading-relaxed">
            <p><strong class="text-gray-700">{{ config('app.name') }}</strong> &bull; Thank you for your business.</p>
            <p class="mt-1">This receipt was generated on {{ now()->format('F j, Y') }} and serves as confirmation of
                payment.</p>
        </div>

    </div>

</body>

</html>