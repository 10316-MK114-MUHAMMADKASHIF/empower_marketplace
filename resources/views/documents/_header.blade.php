{{-- Shared header partial for all compliance document templates --}}
<style>
body { font-family: helvetica, sans-serif; font-size: 10pt; color: #222222; margin: 0; padding: 0; }
h1 { font-size: 16pt; color: #12304f; border-bottom: 2px solid #76c8c0; padding-bottom: 4px; margin-bottom: 6px; }
h2 { font-size: 12pt; color: #12304f; margin-top: 14px; margin-bottom: 4px; }
h3 { font-size: 10pt; color: #12304f; margin-top: 10px; margin-bottom: 3px; }
p { margin: 4px 0 8px 0; line-height: 1.5; }
table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
td, th { border: 1px solid #dbe4ee; padding: 5px 8px; font-size: 9pt; }
th { background-color: #12304f; color: #ffffff; text-align: left; }
tr:nth-child(even) td { background-color: #f4f7fb; }
.label { font-weight: bold; width: 35%; background-color: #edf2f7; }
.section { margin-bottom: 14px; }
.footer { font-size: 8pt; color: #5d6e7f; border-top: 1px solid #dbe4ee; padding-top: 6px; margin-top: 20px; }
.signature-line { border-bottom: 1px solid #333333; margin-top: 30px; width: 60%; }
.signature-label { font-size: 8pt; color: #5d6e7f; margin-top: 2px; }
</style>

<table style="border: none; margin-bottom: 16px;">
    <tr>
        <td style="border: none; padding: 0; width: 70%;">
            <h1 style="margin: 0;">{{ $documentType->label() }}</h1>
            <p style="margin: 2px 0; color: #5d6e7f; font-size: 9pt;">{{ $practice?->name ?? 'Healthcare Practice' }}</p>
        </td>
        <td style="border: none; padding: 0; text-align: right; vertical-align: top; font-size: 8pt; color: #5d6e7f;">
            Generated: {{ $generatedAt->format('F j, Y') }}<br>
            Package: {{ $order->package?->name ?? '' }}
        </td>
    </tr>
</table>
