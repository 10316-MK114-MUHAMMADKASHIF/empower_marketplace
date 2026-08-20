@include('documents._header')

<div class="section">
    <h2>Practice Information</h2>
    <table>
        <tr><td class="label">Practice Name</td><td>{{ $practice?->name ?? 'N/A' }}</td></tr>
        <tr><td class="label">Address</td><td>{{ $practice?->address ?: 'N/A' }}</td></tr>
        <tr><td class="label">NPI Number</td><td>{{ $practice?->npi_number ?? 'N/A' }}</td></tr>
        <tr><td class="label">Specialty</td><td>{{ $practice?->specialty ?? 'N/A' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2>1. Notice of Privacy Practices</h2>
    <p>THIS NOTICE DESCRIBES HOW MEDICAL INFORMATION ABOUT YOU MAY BE USED AND DISCLOSED AND HOW YOU CAN GET ACCESS TO THIS INFORMATION. PLEASE REVIEW IT CAREFULLY.</p>
    <p>{{ $practice?->name ?? 'This practice' }} is required by law to maintain the privacy of your protected health information (PHI) and to provide you with this Notice of Privacy Practices. We are required to abide by the terms of this notice currently in effect.</p>
</div>

<div class="section">
    <h2>2. How We Use and Disclose Your Health Information</h2>
    <h3>Treatment</h3>
    <p>We may use or disclose your PHI to provide, coordinate, or manage your healthcare. For example, we may share information with other healthcare providers involved in your treatment.</p>

    <h3>Payment</h3>
    <p>We may use or disclose your PHI to obtain payment for services provided to you, including billing insurers, Medicare, and Medicaid.</p>

    <h3>Healthcare Operations</h3>
    <p>We may use or disclose your PHI for our internal operations such as quality assessment, employee training, licensing, and accreditation.</p>
</div>

<div class="section">
    <h2>3. Your Rights Regarding Your Health Information</h2>
    <table>
        <tr><th>Right</th><th>Description</th></tr>
        <tr><td>Right to Access</td><td>You may request a copy of your medical records within 30 days of request.</td></tr>
        <tr><td>Right to Amend</td><td>You may request corrections to inaccurate information in your records.</td></tr>
        <tr><td>Right to Restriction</td><td>You may request that we limit how we use or share your information.</td></tr>
        <tr><td>Right to Accounting</td><td>You may request a list of certain disclosures we have made.</td></tr>
        <tr><td>Right to Confidential Communication</td><td>You may request communications through alternative means or locations.</td></tr>
        <tr><td>Right to a Paper Copy</td><td>You may request a paper copy of this notice at any time.</td></tr>
    </table>
</div>

<div class="section">
    <h2>4. Our Responsibilities</h2>
    <p>We are required to maintain the privacy of your PHI, provide you with this notice, follow the terms of the notice currently in effect, and notify you if there is a breach of your unsecured PHI. We may change our privacy practices at any time. Changes will be effective for PHI we already maintain as well as PHI we receive in the future.</p>
</div>

<div class="section">
    <h2>5. Employee HIPAA Obligations</h2>
    <p>All workforce members are required to:</p>
    <ul>
        <li>Access PHI only on a need-to-know basis for job performance</li>
        <li>Never share login credentials or access PHI using another's credentials</li>
        <li>Report any suspected privacy breaches immediately to the Privacy Officer</li>
        <li>Complete annual HIPAA training</li>
        <li>Sign and comply with the Workforce Confidentiality Agreement</li>
    </ul>
    <p>Violations of HIPAA may result in disciplinary action up to termination, civil penalties of $100–$50,000 per violation, and criminal penalties including imprisonment.</p>
</div>

<div class="section">
    <h2>6. Breach Notification</h2>
    <p>In the event of a breach of unsecured PHI, the practice will notify affected individuals within 60 days of discovery, notify the Secretary of HHS as required, and notify local media outlets if the breach affects more than 500 residents of a state or jurisdiction.</p>
</div>

<div class="section">
    <h2>7. Privacy Officer</h2>
    <p>The Privacy Officer is responsible for developing and implementing this HIPAA Privacy Policy. For questions, complaints, or to exercise your rights, contact the practice administrator at the address listed above.</p>
    <p>You also have the right to complain to the Secretary of HHS at: <strong>www.hhs.gov/ocr/privacy</strong></p>
</div>

<div class="section">
    <h2>8. Effective Date</h2>
    <p>This policy is effective as of {{ $generatedAt->format('F j, Y') }}.</p>
</div>

<div class="footer">
    {{ $practice?->name ?? '' }} &bull; HIPAA Privacy Policy &bull; Generated {{ $generatedAt->format('m/d/Y') }} &bull; CONFIDENTIAL
</div>
