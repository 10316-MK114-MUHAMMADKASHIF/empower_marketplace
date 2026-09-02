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
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .receipt-card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 font-sans antialiased py-10 print:py-0">

    <div class="no-print mx-auto max-w-2xl px-4 mb-4 flex justify-end">
        <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-[#12304f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0a2037] transition-colors">
            Print / Save as PDF
        </button>
    </div>

    <div class="receipt-card mx-auto max-w-2xl p-4 bg-white border border-gray-200 rounded-2xl shadow-lg p-10 print:p-0 print:max-w-none print:rounded-none">

        <div class="flex items-start justify-between">
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto" onerror="this.style.display='none'">
                <p class="mt-2 text-sm text-gray-500">Healthcare Compliance Portal</p>
            </div>
            <div class="text-right">
                <h1 class="text-2xl font-bold tracking-wide text-[#12304f]">Payment Receipt</h1>
                <p class="mt-2 text-sm text-gray-500 leading-relaxed">
                    Order #{{ $order->id }}<br>
                    Receipt #{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}<br>
                    {{ $order->paid_at?->format('F j, Y') ?? '—' }}
                </p>
            </div>
        </div>

        <div class="mt-8 border-t-2 border-[#009bde]"></div>

        <div class="mt-8 flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Billed To</p>
                <p class="mt-2 text-base font-bold text-[#12304f]">{{ $practice?->name ?: $order->user->name }}</p>
                @if($practice?->address)
                    <p class="mt-1 text-sm text-gray-500">{{ $practice->address }}</p>
                @endif
                <p class="mt-1 text-sm text-gray-500">{{ $order->user->email }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Payment Status</p>
                <span class="mt-2 inline-block rounded-full bg-[#dff7f0] px-3 py-1 text-xs font-bold tracking-wide text-[#0f7a4f]">PAID &middot; SIMULATED</span>
            </div>
        </div>

        <table class="mt-8 w-full border border-gray-200 rounded-lg border-collapse overflow-hidden">
            <thead>
                <tr class="bg-[#12304f] text-white text-left text-sm">
                    <th class="px-4 py-3 font-semibold">Description</th>
                    <th class="px-4 py-3 font-semibold text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-200">
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $order->package?->name ?? 'Compliance Package' }} — Annual Subscription</td>
                    <td class="px-4 py-3 text-sm text-gray-700 text-right">${{ number_format((float) ($order->original_price ?? $order->amount_paid), 2) }}</td>
                </tr>
                @if($order->discount_code)
                <tr class="border-b border-gray-200">
                    <td class="px-4 py-3 text-sm text-gray-700">Discount ({{ $order->discount_code }})</td>
                    <td class="px-4 py-3 text-sm text-gray-700 text-right">-${{ number_format((float) $order->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="bg-gray-50">
                    <td class="px-4 py-3 text-base font-bold text-[#12304f]">Total Paid</td>
                    <td class="px-4 py-3 text-base font-bold text-[#12304f] text-right">${{ number_format((float) $order->amount_paid, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-10 border-t border-gray-200 pt-6 text-center text-xs text-gray-500 leading-relaxed">
            <p><strong class="text-gray-700">{{ config('app.name') }}</strong> &bull; Thank you for your business.</p>
            <p class="mt-1">This receipt was generated on {{ now()->format('F j, Y') }} and serves as confirmation of payment.</p>
        </div>

    </div>

</body>
</html>
