<style>
body { font-family: helvetica, sans-serif; font-size: 10pt; color: #222222; margin: 0; padding: 0; }
h1 { font-size: 18pt; color: #12304f; margin: 0 0 2px 0; }
p { margin: 2px 0; line-height: 1.5; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
td, th { border: 1px solid #dbe4ee; padding: 6px 10px; font-size: 9pt; }
th { background-color: #12304f; color: #ffffff; text-align: left; }
.label { font-weight: bold; width: 40%; background-color: #edf2f7; }
.total-row td { font-weight: bold; background-color: #dff7f0; }
.footer { font-size: 8pt; color: #5d6e7f; border-top: 1px solid #dbe4ee; padding-top: 6px; margin-top: 30px; }
</style>

<table style="border: none; margin-bottom: 10px;">
    <tr>
        <td style="border: none; padding: 0;">
            <h1>EMPOWER MARKETPLACE</h1>
            <p style="color: #5d6e7f;">Payment Receipt</p>
        </td>
    </tr>
</table>

<table>
    <tr><td class="label">Receipt #</td><td>{{ $order->id }}</td></tr>
    <tr><td class="label">Date</td><td>{{ $order->paid_at?->format('F j, Y') ?? '—' }}</td></tr>
    <tr><td class="label">Practice</td><td>{{ $practice?->name ?? '—' }}</td></tr>
    <tr><td class="label">Package</td><td>{{ $order->package?->name ?? '—' }}</td></tr>
    <tr><td class="label">Payment Status</td><td>Paid (simulated)</td></tr>
    <tr class="total-row"><td class="label">Amount Paid</td><td>${{ number_format((float) $order->amount_paid, 2) }}</td></tr>
</table>

<div class="footer">
    Thank you for your business. &bull; Generated {{ now()->format('F j, Y') }}
</div>
