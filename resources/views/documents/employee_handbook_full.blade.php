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
    <h2>1. Introduction and Mission Statement</h2>
    <p>Welcome to {{ $practice?->name ?? 'our practice' }}. This comprehensive Employee Handbook governs all employment matters and is provided to each employee upon hire. Please read it thoroughly and direct any questions to Human Resources.</p>
    @if(!empty($handbookAnswers['about']))
        <p>{{ $handbookAnswers['about'] }}</p>
    @endif
</div>

<div class="section">
    <h2>2. Equal Employment Opportunity</h2>
    <p>{{ $practice?->name ?? 'Our practice' }} is an equal opportunity employer committed to a diverse and inclusive workplace. We comply with all applicable federal, state, and local laws prohibiting discrimination and harassment.</p>
</div>

<div class="section">
    <h2>3. Employment Classification</h2>
    <p>Employees are classified as full-time, part-time, or temporary. Classification determines eligibility for benefits and other employment conditions. All employees are employed at-will unless otherwise specified in a written agreement.</p>
</div>

<div class="section">
    <h2>4. Compensation and Payroll</h2>
    @if(!empty($handbookAnswers['paycheck_schedule']))
        <p>{{ $handbookAnswers['paycheck_schedule'] }}</p>
    @else
        <p>Compensation is reviewed annually. Payroll is processed bi-weekly, with direct deposit available. Employees must report any discrepancies within five (5) business days.</p>
    @endif
</div>

<div class="section">
    <h2>5. Work Hours and Schedule</h2>
    @if(!empty($handbookAnswers['business_hours']))
        <p>{{ $handbookAnswers['business_hours'] }}</p>
    @else
        <p>Standard hours are 8:00 AM to 5:00 PM, Monday through Friday. Flexible scheduling may be available with supervisor approval. Overtime must be pre-approved in writing.</p>
    @endif
</div>

<div class="section">
    <h2>6. Paid Time Off and Leave</h2>
    @if(!empty($handbookAnswers['time_off']))
        <p>{{ $handbookAnswers['time_off'] }}</p>
    @else
        <p>Full-time employees accrue PTO at the rate of 1.25 days per month (15 days per year). Additional accrual applies after 3 and 5 years of service. PTO must be approved by a supervisor in advance. Emergency leave will be reviewed case-by-case.</p>
    @endif
    <p>The practice also provides FMLA leave, military leave, jury duty leave, and bereavement leave in accordance with applicable law.</p>
</div>

<div class="section">
    <h2>7. Employee Benefits</h2>
    <p>Eligible employees may participate in medical, dental, and vision insurance plans. Details of available plans are provided in the benefits package distributed separately. The practice may contribute toward premiums as outlined in the benefits summary.</p>
</div>

<div class="section">
    <h2>8. Performance Management</h2>
    <p>Performance reviews are conducted annually. Employees are evaluated on clinical competency, patient care quality, teamwork, communication, and adherence to practice policies. Performance improvement plans (PIPs) may be issued for below-standard performance.</p>
</div>

<div class="section">
    <h2>9. Workplace Conduct and Professionalism</h2>
    <p>All employees must maintain the highest standards of professional conduct. This includes respectful communication with patients and colleagues, appropriate dress and hygiene, and adherence to confidentiality requirements under HIPAA. Prohibited conduct includes harassment, discrimination, substance abuse, and misuse of practice property.</p>
</div>

<div class="section">
    <h2>10. HIPAA Compliance</h2>
    <p>All employees handling protected health information (PHI) must complete HIPAA training upon hire and annually thereafter. Unauthorized disclosure of PHI may result in disciplinary action up to and including termination and may expose both the practice and the individual to legal liability.</p>
</div>

<div class="section">
    <h2>11. Social Media and Technology Use</h2>
    <p>Practice-owned technology must be used for business purposes only. Employees must not post patient information, practice details, or colleague information on social media platforms. Violations may result in disciplinary action.</p>
</div>

<div class="section">
    <h2>12. Disciplinary Procedures</h2>
    <p>Disciplinary action may include verbal counseling, written warning, suspension, or termination depending on the severity of the conduct. The practice reserves the right to bypass progressive discipline steps for serious violations.</p>
</div>

<div class="section">
    <h2>13. HR Contact</h2>
    @if(!empty($handbookAnswers['hr_contact']))
        <p>{{ $handbookAnswers['hr_contact'] }}</p>
    @else
        <p>For HR inquiries, contact the practice administrator or office manager during business hours.</p>
    @endif
</div>

<div style="margin-top: 30px;">
    <p><strong>Acknowledgment of Receipt:</strong> I acknowledge receipt of this Employee Handbook and agree to comply with its policies.</p>
    <div class="signature-line"></div>
    <p class="signature-label">Employee Signature &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</p>
    <div class="signature-line" style="margin-top: 20px;"></div>
    <p class="signature-label">Printed Name &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Title</p>
</div>

<div class="footer">
    {{ $practice?->name ?? '' }} &bull; Employee Handbook (Full) &bull; Generated {{ $generatedAt->format('m/d/Y') }} &bull; CONFIDENTIAL
</div>
