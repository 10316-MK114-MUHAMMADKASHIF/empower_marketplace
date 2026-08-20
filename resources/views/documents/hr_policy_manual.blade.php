@include('documents._header')

<div class="section">
    <h2>Practice Information</h2>
    <table>
        <tr><td class="label">Practice Name</td><td>{{ $practice?->name ?? 'N/A' }}</td></tr>
        <tr><td class="label">Address</td><td>{{ $practice?->address ?: 'N/A' }}</td></tr>
        <tr><td class="label">Specialty</td><td>{{ $practice?->specialty ?? 'N/A' }}</td></tr>
        <tr><td class="label">Billable Providers</td><td>{{ $practice?->billable_providers_count ?? 'N/A' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2>1. Purpose</h2>
    <p>This HR Policy Manual establishes policies and procedures governing all aspects of human resources management at {{ $practice?->name ?? 'this practice' }}. These policies supplement the Employee Handbook and are binding on all employees.</p>
</div>

<div class="section">
    <h2>2. Recruitment and Hiring</h2>
    <p>All open positions must be approved by the practice administrator before posting. Hiring decisions are based on qualifications, skills, and fit with practice culture. Background checks and reference verification are required for all new hires in clinical roles. All required licenses and credentials must be verified prior to start date.</p>
</div>

<div class="section">
    <h2>3. Onboarding and Orientation</h2>
    <p>New employees complete a formal orientation program covering: practice policies, HIPAA training, OSHA safety training, EMR/EHR system access, and role-specific clinical protocols. Competency checklists must be completed within 90 days of hire.</p>
</div>

<div class="section">
    <h2>4. Compensation Policy</h2>
    <p>The practice maintains a structured compensation system based on market benchmarks, experience, and performance. Salary grades and bands are reviewed annually. Merit increases are tied to performance review outcomes. Off-cycle adjustments require senior management approval.</p>

    <h3>Overtime</h3>
    <p>Non-exempt employees are entitled to overtime pay at 1.5× their regular hourly rate for hours worked in excess of 40 per week, in accordance with the Fair Labor Standards Act (FLSA). All overtime must be pre-authorized by a supervisor.</p>
</div>

<div class="section">
    <h2>5. Benefits Administration</h2>
    <p>The practice offers a comprehensive benefits package. Eligible employees must enroll within 30 days of hire. Qualifying life events allow mid-year changes. Open enrollment occurs annually in the fourth quarter. Employees are responsible for notifying HR of any changes in dependent status.</p>
</div>

<div class="section">
    <h2>6. Leave Management</h2>
    <table>
        <tr><th>Leave Type</th><th>Eligibility</th><th>Duration</th></tr>
        <tr><td>FMLA</td><td>12 months tenure / 1,250 hours</td><td>Up to 12 weeks unpaid</td></tr>
        <tr><td>PTO / Vacation</td><td>All full-time employees</td><td>Per accrual schedule</td></tr>
        <tr><td>Sick Leave</td><td>All employees</td><td>Per state law + practice policy</td></tr>
        <tr><td>Bereavement</td><td>All employees</td><td>3–5 days depending on relation</td></tr>
        <tr><td>Jury Duty</td><td>All employees</td><td>Duration of service</td></tr>
        <tr><td>Military Leave</td><td>All employees</td><td>Per USERRA</td></tr>
    </table>
</div>

<div class="section">
    <h2>7. Performance Management and Disciplinary Action</h2>
    <p>Performance reviews are conducted annually using standardized evaluation forms. Corrective action follows a progressive discipline process: verbal warning → written warning → final written warning → termination. Documentation must be retained in the employee's personnel file. Immediate termination is reserved for gross misconduct.</p>
</div>

<div class="section">
    <h2>8. Harassment and Non-Discrimination Policy</h2>
    <p>The practice maintains a zero-tolerance policy toward harassment and discrimination in any form. Complaints should be directed to the practice administrator or, if the administrator is the subject of the complaint, to legal counsel. Retaliation against complainants is strictly prohibited and may result in termination.</p>
</div>

<div class="section">
    <h2>9. Credential Verification and Continuing Education</h2>
    <p>All licensed employees must maintain current, valid credentials. The practice may require periodic verification of licensure. Employees are responsible for meeting continuing education (CE) requirements for their respective licenses. The practice may provide financial support for approved CE activities.</p>
</div>

<div class="section">
    <h2>10. Separation of Employment</h2>
    <p>Employees who resign are requested to provide at least two weeks' notice. The practice conducts an exit interview for all departing employees. Final pay is issued on the next regular pay date or earlier if required by state law. COBRA continuation coverage information is provided within 14 days of separation.</p>
</div>

<div class="section">
    <h2>11. HR Contact</h2>
    @if(!empty($handbookAnswers['hr_contact']))
        <p>{{ $handbookAnswers['hr_contact'] }}</p>
    @else
        <p>All HR matters should be directed to the practice administrator or designated HR representative.</p>
    @endif
</div>

<div class="footer">
    {{ $practice?->name ?? '' }} &bull; HR Policy Manual &bull; Generated {{ $generatedAt->format('m/d/Y') }} &bull; CONFIDENTIAL
</div>
