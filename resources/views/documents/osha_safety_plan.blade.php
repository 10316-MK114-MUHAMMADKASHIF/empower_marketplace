@include('documents._header')

<div class="section">
    <h2>Practice Information</h2>
    <table>
        <tr><td class="label">Practice Name</td><td>{{ $practice?->name ?? 'N/A' }}</td></tr>
        <tr><td class="label">Address</td><td>{{ $practice?->address ?: 'N/A' }}</td></tr>
        <tr><td class="label">Specialty</td><td>{{ $practice?->specialty ?? 'N/A' }}</td></tr>
        <tr><td class="label">Total Providers</td><td>{{ $practice?->billable_providers_count ?? 'N/A' }}</td></tr>
        @if(!empty($aiData['employees_per_year']))
            <tr><td class="label">Total Employees</td><td>{{ $aiData['employees_per_year'] }}</td></tr>
        @endif
    </table>
</div>

<div class="section">
    <h2>1. Purpose and Scope</h2>
    <p>This OSHA Safety Plan has been developed to comply with all applicable Occupational Safety and Health Administration (OSHA) regulations. It applies to all employees, contractors, and visitors at {{ $practice?->name ?? 'this practice' }}.</p>
</div>

<div class="section">
    <h2>2. Management Commitment and Employee Involvement</h2>
    <p>Practice management is committed to providing a safe and healthful workplace. We will comply with all OSHA standards, rules, and regulations applicable to our practice. Employees are encouraged to report unsafe conditions to their supervisor without fear of retaliation.</p>
</div>

<div class="section">
    <h2>3. Hazard Communication (HazCom)</h2>
    <p>The practice maintains a written Hazard Communication Program. All chemical products used in the workplace are listed in our Safety Data Sheet (SDS) binder, which is accessible to all employees. Employees must complete HazCom training before working with any hazardous chemicals.</p>
    @if(!empty($aiData['hazardous_materials']))
        <h3>Identified Hazardous Materials</h3>
        <ul>
            @foreach((array) $aiData['hazardous_materials'] as $material)
                <li>{{ $material }}</li>
            @endforeach
        </ul>
    @endif
</div>

<div class="section">
    <h2>4. Bloodborne Pathogens (BBP)</h2>
    <p>All employees with occupational exposure to blood or other potentially infectious materials (OPIM) are covered by this plan. The practice provides:</p>
    <ul>
        <li>Hepatitis B vaccination at no cost to exposed employees</li>
        <li>Personal protective equipment (PPE) appropriate to each task</li>
        <li>Post-exposure evaluation and follow-up</li>
        <li>Annual BBP training</li>
    </ul>
</div>

<div class="section">
    <h2>5. Personal Protective Equipment (PPE)</h2>
    <p>The practice provides appropriate PPE for all employees whose duties require it. PPE must be worn as required by task-specific safety protocols. Damaged or defective PPE must be reported and replaced immediately.</p>
</div>

<div class="section">
    <h2>6. Emergency Action Plan</h2>
    <p>In the event of an emergency, employees should follow posted evacuation routes and assemble at designated muster points. Emergency contacts are posted at each workstation. The practice conducts evacuation drills at least once per year.</p>
    <p><strong>Emergency Contact:</strong> 911 &nbsp;&nbsp; <strong>Poison Control:</strong> 1-800-222-1222</p>
</div>

<div class="section">
    <h2>7. Required Training Programs</h2>
    <table>
        <tr><th>Training Topic</th><th>Frequency</th><th>Applicable Employees</th></tr>
        <tr><td>Bloodborne Pathogens</td><td>Annual</td><td>All clinical staff</td></tr>
        <tr><td>Hazard Communication</td><td>Upon hire + annually</td><td>All employees</td></tr>
        <tr><td>Fire Safety / Evacuation</td><td>Annual</td><td>All employees</td></tr>
        <tr><td>PPE Use and Care</td><td>Upon hire + as needed</td><td>All clinical staff</td></tr>
        <tr><td>Infection Control</td><td>Annual</td><td>All clinical staff</td></tr>
        @if(!empty($aiData['training_requirements']))
            @foreach((array) $aiData['training_requirements'] as $training)
                <tr><td>{{ $training }}</td><td>Per OSHA requirement</td><td>Applicable staff</td></tr>
            @endforeach
        @endif
    </table>
</div>

<div class="section">
    <h2>8. Recordkeeping</h2>
    <p>The practice maintains OSHA 300, 300A, and 301 logs as required. Work-related injuries and illnesses must be reported to a supervisor immediately and documented within 7 days. Certain severe injuries must be reported to OSHA within 24 hours.</p>
</div>

<div class="section">
    <h2>9. Plan Review</h2>
    <p>This OSHA Safety Plan is reviewed and updated annually, or whenever there is a change in work processes, facilities, or regulations. The plan administrator is responsible for maintaining current copies of all referenced documents.</p>
</div>

<div style="margin-top: 30px;">
    <div class="signature-line"></div>
    <p class="signature-label">Practice Administrator Signature &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</p>
</div>

<div class="footer">
    {{ $practice?->name ?? '' }} &bull; OSHA Safety Plan &bull; Generated {{ $generatedAt->format('m/d/Y') }} &bull; CONFIDENTIAL
</div>
