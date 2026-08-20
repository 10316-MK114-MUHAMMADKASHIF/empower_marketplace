@include('documents._header')

<div class="section">
    <h2>Practice Information</h2>
    <table>
        <tr><td class="label">Practice Name</td><td>{{ $practice?->name ?? 'N/A' }}</td></tr>
        <tr><td class="label">Address</td><td>{{ $practice?->address ?: 'N/A' }}</td></tr>
        <tr><td class="label">Specialty</td><td>{{ $practice?->specialty ?? 'N/A' }}</td></tr>
        <tr><td class="label">Package</td><td>{{ $order->package?->name ?? 'Complete Compliance' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2>Custom Compliance Document</h2>
    <p>This document has been generated as part of your Complete Compliance package. It is tailored to the specific compliance needs identified during the intake process for {{ $practice?->name ?? 'your practice' }}.</p>

    @if(!empty($aiData))
        <h3>Key Findings from Intake Review</h3>
        <table>
            @foreach($aiData as $key => $value)
                @if(is_string($value) && $value !== '')
                    <tr>
                        <td class="label">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                @endif
            @endforeach
        </table>
    @endif
</div>

<div class="section">
    <h2>Practice-Specific Compliance Recommendations</h2>
    <p>Based on the information provided during intake, the following compliance areas have been identified as priorities for {{ $practice?->name ?? 'your practice' }}:</p>

    <h3>Regulatory Compliance</h3>
    <p>Ensure all state and federal healthcare regulations applicable to your specialty are addressed. Regular internal audits should be scheduled at least quarterly to identify and remediate compliance gaps before regulatory inspections.</p>

    <h3>Documentation and Record-Keeping</h3>
    <p>Maintain complete, accurate, and timely medical records. Implement a document retention schedule consistent with state requirements and HIPAA minimum necessary standards. Electronic health records must be backed up regularly and access logs audited monthly.</p>

    <h3>Staff Training and Credentialing</h3>
    <p>Maintain up-to-date training records for all employees. Credential verification should occur prior to hire and at each license renewal. Consider a learning management system (LMS) to automate training delivery and tracking.</p>

    <h3>Quality Improvement</h3>
    <p>Establish a formal Quality Improvement (QI) program with defined metrics, regular reporting, and actionable improvement plans. Patient satisfaction data should be collected and reviewed at least quarterly.</p>
</div>

<div class="section">
    <h2>Next Steps</h2>
    <p>Your compliance advisor will contact you to review this document and provide guidance on implementation. Please have this document available for that consultation. Additional support resources are available through your Empower Marketplace portal.</p>
</div>

<div style="margin-top: 30px;">
    <div class="signature-line"></div>
    <p class="signature-label">Authorized Representative &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</p>
</div>

<div class="footer">
    {{ $practice?->name ?? '' }} &bull; Custom Compliance Document &bull; Generated {{ $generatedAt->format('m/d/Y') }} &bull; CONFIDENTIAL &bull; Prepared by Empower Marketplace
</div>
