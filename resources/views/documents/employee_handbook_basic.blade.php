@include('documents._header')

<div class="section">
    <h2>Practice Information</h2>
    <table>
        <tr><td class="label">Practice Name</td><td>{{ $practice?->name ?? 'N/A' }}</td></tr>
        <tr><td class="label">Address</td><td>{{ $practice?->address ?: 'N/A' }}</td></tr>
        <tr><td class="label">NPI Number</td><td>{{ $practice?->npi_number ?? 'N/A' }}</td></tr>
        <tr><td class="label">Specialty</td><td>{{ $practice?->specialty ?? 'N/A' }}</td></tr>
        <tr><td class="label">Billable Providers</td><td>{{ $practice?->billable_providers_count ?? 'N/A' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2>1. Introduction and Welcome</h2>
    <p>Welcome to {{ $practice?->name ?? 'our practice' }}. This Employee Handbook has been prepared to provide you with information about our practice, its policies, and the standards we expect of all employees. Please read this handbook carefully and retain it for future reference.</p>
    @if(!empty($handbookAnswers['about']))
        <p>{{ $handbookAnswers['about'] }}</p>
    @else
        <p>This handbook applies to all employees of the practice and supersedes any previously issued handbooks or policy statements. The policies in this handbook may be modified, supplemented, or rescinded at any time with or without notice, at the discretion of management.</p>
    @endif
</div>

<div class="section">
    <h2>2. Employment Policies</h2>
    <h3>Equal Opportunity Employment</h3>
    <p>{{ $practice?->name ?? 'Our practice' }} is an equal opportunity employer. We do not discriminate on the basis of race, color, religion, sex, national origin, age, disability, genetic information, or any other characteristic protected by applicable law.</p>

    <h3>Work Hours</h3>
    @if(!empty($handbookAnswers['business_hours']))
        <p>{{ $handbookAnswers['business_hours'] }}</p>
    @else
        <p>Standard business hours are Monday through Friday. Employees are expected to be at work on time and to notify their supervisor if they will be late or absent.</p>
    @endif

    <h3>Payroll</h3>
    @if(!empty($handbookAnswers['paycheck_schedule']))
        <p>{{ $handbookAnswers['paycheck_schedule'] }}</p>
    @else
        <p>Employees are paid on a bi-weekly schedule. Paychecks are distributed on alternating Fridays.</p>
    @endif
</div>

<div class="section">
    <h2>3. Time Off and Leave</h2>
    @if(!empty($handbookAnswers['time_off']))
        <p>{{ $handbookAnswers['time_off'] }}</p>
    @else
        <p>Full-time employees accrue paid time off (PTO) according to the following schedule: 0–2 years of service: 10 days per year; 2–5 years: 15 days per year; 5+ years: 20 days per year. PTO requests must be submitted to your supervisor at least two weeks in advance.</p>
    @endif
</div>

<div class="section">
    <h2>4. Workplace Conduct</h2>
    <p>All employees are expected to maintain professional behavior at all times. Harassment, discrimination, or violence of any kind will not be tolerated and may result in immediate termination. All employees must comply with HIPAA privacy and security rules when handling protected health information (PHI).</p>
</div>

<div class="section">
    <h2>5. HR Contact</h2>
    @if(!empty($handbookAnswers['hr_contact']))
        <p>For HR-related inquiries, please contact: {{ $handbookAnswers['hr_contact'] }}</p>
    @else
        <p>For questions regarding this handbook or HR matters, please contact your direct supervisor or practice administrator.</p>
    @endif
</div>

<div style="margin-top: 30px;">
    <p><strong>Acknowledgment:</strong> By signing below, I acknowledge that I have received, read, and understand the contents of this Employee Handbook.</p>
    <div class="signature-line"></div>
    <p class="signature-label">Employee Signature &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</p>
</div>

<div class="footer">
    {{ $practice?->name ?? '' }} &bull; Employee Handbook (Basic) &bull; Generated {{ $generatedAt->format('m/d/Y') }} &bull; CONFIDENTIAL
</div>
